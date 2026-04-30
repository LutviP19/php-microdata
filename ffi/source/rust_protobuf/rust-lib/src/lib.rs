use prost::Message;
use std::collections::HashMap;
use std::ffi::{CStr, CString};
use libc::{c_char, size_t, c_uchar};

// Import file yang di-generate. 
// Jika di .proto ada 'package api', maka file ini berisi 'pub mod api { ... }'
pub mod proto_generated {
    include!("api.rs");
}

// Buat alias agar kode di bawah lebih bersih
// Sesuaikan: proto_generated::api jika di .proto ada package api
// Atau: proto_generated jika tidak ada package
use proto_generated::GenericPayload;

#[repr(C)]
pub struct ProtoBuffer {
    pub data: *mut u8,
    pub len: size_t,
}

#[no_mangle]
pub extern "C" fn encode_generic(
    content: *const c_char,
    json_metadata: *const c_char,
    payload_ptr: *const c_uchar,
    payload_len: size_t
) -> ProtoBuffer {
    // 1. Endpoint
    let content_str = unsafe { 
        if content.is_null() { "unknown".to_string() } 
        else { CStr::from_ptr(content).to_string_lossy().into_owned() }
    };

    // 2. Metadata - Perbaikan Parsing
    let metadata: HashMap<String, String> = unsafe {
        if json_metadata.is_null() {
            HashMap::new()
        } else {
            // Gunakan CStr untuk validasi string dari PHP
            let c_str = CStr::from_ptr(json_metadata);
            
            // Parsing menggunakan from_slice lebih efisien dan aman untuk bytes
            serde_json::from_slice(c_str.to_bytes()).unwrap_or_else(|_| {
                // Fallback ke HashMap kosong jika JSON tidak valid
                HashMap::new()
            })
        }
    };

    // 3. Payload
    let payload_data = unsafe {
        if payload_ptr.is_null() || payload_len == 0 {
            Vec::new()
        } else {
            std::slice::from_raw_parts(payload_ptr, payload_len).to_vec()
        }
    };

    let mut payload = GenericPayload::default();
    payload.content = content_str;
    payload.metadata = metadata;
    payload.payload = payload_data;

    let mut buf = Vec::new();
    buf.reserve(payload.encoded_len());
    payload.encode(&mut buf).unwrap();

    let len = buf.len();
    let data = buf.as_mut_ptr();
    std::mem::forget(buf); 

    ProtoBuffer { data, len }
}

#[no_mangle]
pub extern "C" fn decode_generic(binary_ptr: *const c_uchar, len: size_t) -> *mut c_char {
    // Validasi pointer null untuk keamanan
    if binary_ptr.is_null() || len == 0 {
        return CString::new("{\"error\":\"empty_input\"}").unwrap().into_raw();
    }

    let slice = unsafe { std::slice::from_raw_parts(binary_ptr, len) };
    
    match GenericPayload::decode(slice) {
        Ok(p) => {
            // Kita bungkus dalam serde_json::Value untuk kontrol lebih presisi
            let res = serde_json::json!({
                "content": p.content,
                // Jika metadata kosong, pastikan JSON tetap menuliskan {} bukan []
                "metadata": if p.metadata.is_empty() { 
                    serde_json::Value::Object(serde_json::Map::new()) 
                } else { 
                    serde_json::to_value(&p.metadata).unwrap() 
                },
                "payload_size": p.payload.len(),
                // Opsi: jika ingin melihat data payload dalam bentuk base64/hex
                "payload_raw": hex::encode(&p.payload) 
            });

            match CString::new(res.to_string()) {
                Ok(c_str) => c_str.into_raw(),
                Err(_) => CString::new("{\"error\":\"serialization_error\"}").unwrap().into_raw(),
            }
        }
        Err(e) => {
            let err_msg = format!("{{\"error\":\"decode_failed\", \"details\":\"{}\"}}", e);
            CString::new(err_msg).unwrap().into_raw()
        },
    }
}

// --- MEMORY CLEANUP (CRITICAL) ---
#[no_mangle]
pub extern "C" fn free_proto_buffer(buf: ProtoBuffer) {
    if !buf.data.is_null() {
        unsafe {
            // Mengubah kembali pointer menjadi Vec dan membiarkannya keluar dari scope untuk di-drop
            drop(Vec::from_raw_parts(buf.data, buf.len, buf.len));
        }
    }
}

#[no_mangle]
pub extern "C" fn free_string(s: *mut c_char) {
    if !s.is_null() {
        unsafe {
            // Mengambil kembali kepemilikan memori dan langsung menghapusnya (drop)
            drop(CString::from_raw(s));
        }
    }
}