use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use std::collections::HashMap;
use serde::{Serialize, Deserialize};
use reqwest::blocking::Client;
use std::time::Duration;

#[derive(Deserialize)]
struct RequestConfig {
    method: String,
    url: String,
    headers: HashMap<String, String>,
    body: String,
    timeout: u64,
}

#[derive(Deserialize)]
struct MultiRequestConfig {
    requests: Vec<RequestConfig>,
}

#[derive(Serialize)]
struct ResponseData {
    status: u16,
    body: String,
    error: String,
}

// Helper untuk mengubah Result menjadi C-String
fn create_c_string_response<T: Serialize>(data: &T) -> *mut c_char {
    let json = serde_json::to_string(data).unwrap_or_else(|_| "{}".to_string());
    CString::new(json).unwrap().into_raw()
}

#[no_mangle]
pub extern "C" fn ExecuteRequest(json_input: *const c_char) -> *mut c_char {
    let c_str = unsafe { CStr::from_ptr(json_input) };
    let cfg: RequestConfig = match serde_json::from_str(c_str.to_str().unwrap_or("")) {
        Ok(c) => c,
        Err(_) => return create_c_string_response(&ResponseData { status: 0, body: "".to_string(), error: "Invalid JSON Input".to_string() }),
    };

    let result = perform_request(cfg);
    create_c_string_response(&result)
}

#[no_mangle]
pub extern "C" fn ExecuteMultiRequest(json_input: *const c_char) -> *mut c_char {
    let c_str = unsafe { CStr::from_ptr(json_input) };
    let config: MultiRequestConfig = match serde_json::from_str(c_str.to_str().unwrap_or("")) {
        Ok(c) => c,
        Err(_) => return create_c_string_response(&vec![ResponseData { status: 0, body: "".to_string(), error: "Invalid JSON Input".to_string() }]),
    };

    // Menggunakan Rayon untuk paralelisme sejati (seperti goroutines tapi lebih CPU-efficient)
    use rayon::prelude::*;
    let results: Vec<ResponseData> = config.requests
        .into_par_iter()
        .map(perform_request)
        .collect();

    create_c_string_response(&results)
}

fn perform_request(cfg: RequestConfig) -> ResponseData {
    let client = Client::builder()
        .timeout(Duration::from_secs(if cfg.timeout == 0 { 30 } else { cfg.timeout }))
        .build()
        .unwrap_or_default();

    let method = match cfg.method.to_uppercase().as_str() {
        "POST" => reqwest::Method::POST,
        "PUT" => reqwest::Method::PUT,
        _ => reqwest::Method::GET,
    };

    let mut rb = client.request(method, &cfg.url);
    for (k, v) in cfg.headers {
        rb = rb.header(k, v);
    }
    
    if !cfg.body.is_empty() {
        rb = rb.body(cfg.body);
    }

    match rb.send() {
        Ok(resp) => {
            let status = resp.status().as_u16();
            
            let mut filtered_results: Vec<serde_json::Value> = Vec::new();
            let reader = std::io::BufReader::new(resp);
            
            // Membuat stream deserializer
            let stream = serde_json::Deserializer::from_reader(reader).into_iter::<serde_json::Value>();

            // Looping yang benar untuk iterator Result
            for item in stream {
                match item {
                    Ok(val) => {
                        // Di sini Anda bisa menambahkan logika filter
                        // Misal: if val["status"] == "active" { ... }
                        filtered_results.push(val);
                    }
                    Err(_) => break, // Berhenti jika ada JSON yang tidak valid di tengah stream
                }
            }

            ResponseData {
                status,
                body: serde_json::to_string(&filtered_results).unwrap_or_else(|_| "[]".to_string()),
                error: "".to_string(),
            }
        }
        Err(e) => ResponseData {
            status: 0,
            body: "".to_string(),
            error: format!("[Rust Engine] Connection Error: {}", e),
        },
    }
}

// Fungsi wajib untuk membebaskan memori di sisi C
#[no_mangle]
pub extern "C" fn free_rust_string(ptr: *mut c_char) {
    if ptr.is_null() { return; }
    unsafe {
        let _ = CString::from_raw(ptr);
    }
}