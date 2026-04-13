use serde_json::Value;
use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use rayon::prelude::*;

// ==========================================
// CORE ENGINE (Scalable Logic)
// ==========================================

/// Trait untuk mendefinisikan jenis filter yang bisa dikembangkan
trait DataFilter: Send + Sync {
    fn matches(&self, item: &Value) -> bool;
}

/// Filter untuk pencarian String sederhana (Exact Match)
struct EqualsFilter {
    key: String,
    value: String,
}

impl DataFilter for EqualsFilter {
    fn matches(&self, item: &Value) -> bool {
        item.get(&self.key)
            .map(|v| v.to_string().replace('"', "") == self.value)
            .unwrap_or(false)
    }
}

/// Filter untuk pencarian angka (Greater Than) - Contoh Scalability
struct GreaterThanFilter {
    key: String,
    threshold: f64,
}

impl DataFilter for GreaterThanFilter {
    fn matches(&self, item: &Value) -> bool {
        item.get(&self.key)
            .and_then(|v| v.as_f64())
            .map(|val| val > self.threshold)
            .unwrap_or(false)
    }
}

// ==========================================
// FFI LAYER (Jembatan PHP)
// ==========================================

#[no_mangle]
pub extern "C" fn process_data_scalable(
    json_raw: *const c_char,
    filter_key: *const c_char,
    filter_value: *const c_char,
    mode: *const c_char, // "equals" atau "gt"
) -> *mut c_char {
    let raw_str = unsafe { CStr::from_ptr(json_raw) }.to_str().unwrap_or("[]");
    let key = unsafe { CStr::from_ptr(filter_key) }.to_str().unwrap_or("");
    let val = unsafe { CStr::from_ptr(filter_value) }.to_str().unwrap_or("");
    let mode_str = unsafe { CStr::from_ptr(mode) }.to_str().unwrap_or("equals");

    let data: Vec<Value> = serde_json::from_str(raw_str).unwrap_or_else(|_| vec![]);

    // Pilih filter secara dinamis (Scalability Point)
    let filter: Box<dyn DataFilter> = match mode_str {
        "gt" => Box::new(GreaterThanFilter {
            key: key.to_string(),
            threshold: val.parse::<f64>().unwrap_or(0.0),
        }),
        _ => Box::new(EqualsFilter {
            key: key.to_string(),
            value: val.to_string(),
        }),
    };

    // Eksekusi paralel menggunakan Rayon
    let filtered: Vec<&Value> = data.par_iter()
        .filter(|item| filter.matches(item))
        .collect();

    let json_result = serde_json::to_string(&filtered).unwrap_or_else(|_| "[]".to_string());
    CString::new(json_result).unwrap().into_raw()
}

#[no_mangle]
pub extern "C" fn free_rust_string(ptr: *mut c_char) {
    if !ptr.is_null() {
        unsafe { let _ = CString::from_raw(ptr); }
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_filter_logic() {
        // Simulasi data JSON
        let json_data = r#"[
            {"id": 1, "name": "Laptop", "price": 5000},
            {"id": 2, "name": "Mouse", "price": 100}
        ]"#;
        
        // Kita panggil fungsi internal secara langsung
        let key = std::ffi::CString::new("price").unwrap();
        let val = std::ffi::CString::new("5000").unwrap();
        let mode = std::ffi::CString::new("equals").unwrap();
        let raw = std::ffi::CString::new(json_data).unwrap();

        let result_ptr = process_data_scalable(raw.as_ptr(), key.as_ptr(), val.as_ptr(), mode.as_ptr());
        
        let result_str = unsafe { std::ffi::CStr::from_ptr(result_ptr) }.to_str().unwrap();
        assert!(result_str.contains("Laptop"));
        assert!(!result_str.contains("Mouse"));
        
        // Bersihkan memori
        free_rust_string(result_ptr);
    }
}