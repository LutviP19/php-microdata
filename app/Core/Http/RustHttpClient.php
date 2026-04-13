<?php 
/**
 * RustHttpClient for FFI
 * @package PHP-Microdata
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Http;

use FFI;
use Exception;
use RuntimeException;

class RustHttpClient
{
    private $ffi;
    private $libPath;

    public function __construct(string $libPath = null)
    {
        // Pastikan path menunjuk ke file .so hasil cargo build --release
        $this->libPath = $libPath ?? realpath(BASEPATH_FFI . '/lib/librust_curl_ffi.so');

        if (!file_exists($this->libPath)) {
            throw new RuntimeException("Rust Library (.so) not found at: " . $this->libPath);
        }

        $this->ffi = FFI::cdef("
            char* ExecuteRequest(const char* jsonInput);
            char* ExecuteMultiRequest(const char* jsonInput);
            void free_rust_string(char* ptr);
        ", $this->libPath);
    }

    public function request(array $options): array
    {
        $payload = json_encode($options);
        $ptr = $this->ffi->ExecuteRequest($payload);
        return $this->processResult($ptr);
    }

    public function multiRequest(array $requests): array
    {
        $payload = json_encode(['requests' => $requests]);
        $ptr = $this->ffi->ExecuteMultiRequest($payload);
        return $this->processResult($ptr);
    }

    private function processResult($ptr): array
    {
        if ($ptr === null) {
            throw new Exception("Rust FFI returned null pointer.");
        }

        $json = FFI::string($ptr);
        
        // Membebaskan memori di sisi Rust
        $this->ffi->free_rust_string($ptr);
        
        $data = json_decode($json, true);
        return $data ?? [];
    }
}