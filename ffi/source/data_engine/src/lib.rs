use serde_json::Value;
use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use rayon::prelude::*;
use std::sync::Mutex;
use once_cell::sync::Lazy;

// ==========================================
// CORE ENGINE (Scalable Logic)
// ==========================================

trait DataFilter: Send + Sync {
    fn matches(&self, item: &Value) -> bool;
}

struct EqualsFilter { key: String, value: String }

struct GreaterThanFilter { key: String, threshold: f64 }

struct LessThanFilter { key: String, threshold: f64 }

struct ContainsFilter { key: String, pattern: String }


// ==========================================
// ROBUST FILTERS
// ==========================================

impl DataFilter for EqualsFilter {
    fn matches(&self, item: &Value) -> bool {
        item.get(&self.key).map(|v| {
            let s = v.as_str().unwrap_or(""); // Ambil isinya saja tanpa kutip
            if s == "" { // Jika bukan string, mungkin angka
                v.to_string() == self.value
            } else {
                s == self.value
            }
        }).unwrap_or(false)
    }
}

impl DataFilter for GreaterThanFilter {
    fn matches(&self, item: &Value) -> bool {
        let val_num = item.get(&self.key).and_then(|v| {
            v.as_f64().or_else(|| {
                // Jika data berupa string "5000", parse ke float
                v.as_str()?.parse::<f64>().ok()
            })
        });
        val_num.map(|v| v > self.threshold).unwrap_or(false)
    }
}

impl DataFilter for LessThanFilter {
    fn matches(&self, item: &Value) -> bool {
        let val_num = item.get(&self.key).and_then(|v| {
            v.as_f64().or_else(|| {
                v.as_str()?.parse::<f64>().ok()
            })
        });
        val_num.map(|v| v < self.threshold).unwrap_or(false)
    }
}

impl DataFilter for ContainsFilter {
    fn matches(&self, item: &Value) -> bool {
        item.get(&self.key)
            .and_then(|v| v.as_str()) // Contains hanya bekerja untuk string
            .map(|v| v.to_lowercase().contains(&self.pattern.to_lowercase()))
            .unwrap_or(false)
    }
}

// Global variable untuk menyimpan data di memori Rust
static GLOBAL_DATA: Lazy<Mutex<Vec<Value>>> = Lazy::new(|| Mutex::new(Vec::new()));

// ==========================================
// FFI EXPORTS
// ==========================================

#[no_mangle]
pub extern "C" fn get_item_at(index: i32) -> *mut c_char {
    if let Ok(storage) = GLOBAL_DATA.lock() {
        if let Some(item) = storage.get(index as usize) {
            let json = serde_json::to_string(item).unwrap_or_else(|_| "{}".to_string());
            return CString::new(json).unwrap().into_raw();
        }
    }
    std::ptr::null_mut()
}

#[no_mangle]
pub extern "C" fn process_data_scalable(
    json_raw: *const c_char,
    filter_key: *const c_char,
    filter_value: *const c_char,
    mode: *const c_char,
) -> *mut c_char {
    let raw_str = unsafe { CStr::from_ptr(json_raw) }.to_str().unwrap_or("[]");
    let key = unsafe { CStr::from_ptr(filter_key) }.to_str().unwrap_or("");
    let val = unsafe { CStr::from_ptr(filter_value) }.to_str().unwrap_or("");
    let mode_str = unsafe { CStr::from_ptr(mode) }.to_str().unwrap_or("equals");

    let data: Vec<Value> = serde_json::from_str(raw_str).unwrap_or_else(|_| vec![]);

    let filter: Box<dyn DataFilter> = match mode_str {
        "gt" => Box::new(GreaterThanFilter { key: key.to_string(), threshold: val.parse().unwrap_or(0.0) }),
        "lt" => Box::new(LessThanFilter { key: key.to_string(), threshold: val.parse().unwrap_or(0.0) }),
        "contains" => Box::new(ContainsFilter { key: key.to_string(), pattern: val.to_string() }),
        _ => Box::new(EqualsFilter { key: key.to_string(), value: val.to_string() }),
    };

    let filtered: Vec<&Value> = data.par_iter().filter(|item| filter.matches(item)).collect();
    let json_result = serde_json::to_string(&filtered).unwrap_or_else(|_| "[]".to_string());
    CString::new(json_result).unwrap().into_raw()
}

#[no_mangle]
pub extern "C" fn debug_first_item() -> *mut c_char {
    let data = GLOBAL_DATA.lock().unwrap();
    let first = data.get(0).map(|v| v.to_string()).unwrap_or("Empty".to_string());
    CString::new(first).unwrap().into_raw()
}

#[no_mangle]
pub extern "C" fn get_storage_count() -> i32 {
    if let Ok(storage) = GLOBAL_DATA.lock() {
        storage.len() as i32
    } else {
        0
    }
}

#[no_mangle]
pub extern "C" fn load_data(json_raw: *const c_char) -> i32 {
    // load_data sekarang bertindak sebagai 'clear' lalu 'append'
    clear_data();
    append_data(json_raw)
}

#[no_mangle]
pub extern "C" fn append_data(json_chunk: *const c_char) -> i32 {
    let c_str = unsafe { CStr::from_ptr(json_chunk) };
    let json_str = c_str.to_str().unwrap_or("[]");
    
    // Deserialize chunk yang dikirim PHP
    let new_data: Vec<Value> = serde_json::from_str(json_str).unwrap_or_default();
    // let count = new_data.len();

    if let Ok(mut storage) = GLOBAL_DATA.lock() {
        storage.extend(new_data);
        storage.len() as i32
    } else {
        0
    }
}

#[no_mangle]
pub extern "C" fn clear_data() {
    if let Ok(mut storage) = GLOBAL_DATA.lock() {
        storage.clear();
    }
}

#[no_mangle]
pub extern "C" fn filter_loaded_data(
    filter_key: *const c_char,
    filter_value: *const c_char,
    mode: *const c_char, // Pointer mode dari PHP
) -> *mut c_char {
    // 1. Konversi pointer C ke Rust string slices (&str)
    let key = unsafe { CStr::from_ptr(filter_key) }.to_str().unwrap_or("");
    let val = unsafe { CStr::from_ptr(filter_value) }.to_str().unwrap_or("");
    
    // DEFISINISIKAN mode_str DI SINI
    let mode_str = unsafe { CStr::from_ptr(mode) }.to_str().unwrap_or("equals");

    // 2. Inisialisasi Filter berdasarkan mode_str
    let filter: Box<dyn DataFilter> = match mode_str {
        "gt" => Box::new(GreaterThanFilter { 
            key: key.to_string(), 
            threshold: val.parse().unwrap_or(0.0) 
        }),
        "lt" => Box::new(LessThanFilter { 
            key: key.to_string(), 
            threshold: val.parse().unwrap_or(0.0) 
        }),
        "contains" => Box::new(ContainsFilter { 
            key: key.to_string(), 
            pattern: val.to_string() 
        }),
        _ => Box::new(EqualsFilter { 
            key: key.to_string(), 
            value: val.to_string() 
        }),
    };

    // 3. Eksekusi filter secara paralel
    let data = GLOBAL_DATA.lock().unwrap();
    let filtered: Vec<&Value> = data.par_iter().filter(|item| filter.matches(item)).collect();
    
    // 4. Kembalikan hasil sebagai JSON string ke PHP
    let json_result = serde_json::to_string(&filtered).unwrap_or_else(|_| "[]".to_string());
    CString::new(json_result).unwrap().into_raw()
}

#[no_mangle]
pub extern "C" fn free_rust_string(ptr: *mut c_char) {
    if !ptr.is_null() { unsafe { let _ = CString::from_raw(ptr); } }
}

// ==========================================
// UNIT TESTS (Internal Testing)
// ==========================================

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_all_filters() {
        let data = r#"[
            {"name": "Macbook", "price": 2500, "tags": "laptop pro"},
            {"name": "iPhone", "price": 1200, "tags": "mobile ios"},
            {"name": "Mouse", "price": 50, "tags": "acc"}
        ]"#;
        
        let raw = CString::new(data).unwrap();

        // Test Contains
        let res = process_data_scalable(raw.as_ptr(), CString::new("tags").unwrap().as_ptr(), CString::new("pro").unwrap().as_ptr(), CString::new("contains").unwrap().as_ptr());
        let s = unsafe { CStr::from_ptr(res) }.to_str().unwrap();
        assert!(s.contains("Macbook"));
        free_rust_string(res);

        // Test Less Than
        let res = process_data_scalable(raw.as_ptr(), CString::new("price").unwrap().as_ptr(), CString::new("100").unwrap().as_ptr(), CString::new("lt").unwrap().as_ptr());
        let s = unsafe { CStr::from_ptr(res) }.to_str().unwrap();
        assert!(s.contains("Mouse"));
        free_rust_string(res);
    }

    #[test]
    fn test_stateful_chunk_loading() {
        // 1. Pastikan storage bersih di awal
        clear_data();

        // 2. Simulasi Chunk Pertama (Data Elektronik)
        let chunk1 = r#"[
            {"id": 1, "name": "Laptop", "price": 5000, "tags": "premium tech"},
            {"id": 2, "name": "Mouse", "price": 100, "tags": "acc"}
        ]"#;
        let c_chunk1 = CString::new(chunk1).unwrap();
        append_data(c_chunk1.as_ptr());

        // 3. Simulasi Chunk Kedua (Data Fashion)
        let chunk2 = r#"[
            {"id": 3, "name": "T-Shirt", "price": 50, "tags": "local fashion"},
            {"id": 4, "name": "Jaket", "price": 250, "tags": "premium winter"}
        ]"#;
        let c_chunk2 = CString::new(chunk2).unwrap();
        let total_count = append_data(c_chunk2.as_ptr());

        // Verifikasi jumlah data yang tersimpan (2 + 2 = 4)
        assert_eq!(total_count, 4);

        // 4. Test Filter pada data yang sudah di-load
        let key = CString::new("tags").unwrap();
        let val = CString::new("premium").unwrap();
        let mode = CString::new("contains").unwrap();

        let res_ptr = filter_loaded_data(key.as_ptr(), val.as_ptr(), mode.as_ptr());
        let res_str = unsafe { CStr::from_ptr(res_ptr) }.to_str().unwrap();

        // Harus mengandung Laptop dan Jaket karena keduanya punya tag 'premium'
        assert!(res_str.contains("Laptop"));
        assert!(res_str.contains("Jaket"));
        assert!(!res_str.contains("T-Shirt"));

        // Bersihkan memori pointer dari Rust
        free_rust_string(res_ptr);

        // 5. Test Clear Data
        clear_data();
        let final_storage = GLOBAL_DATA.lock().unwrap();
        assert_eq!(final_storage.len(), 0);
    }
}