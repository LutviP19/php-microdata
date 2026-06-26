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

    public function __construct(?string $libPath = null)
    {
        // Pastikan path menunjuk ke file .so hasil cargo build --release
        $this->libPath = $libPath ?? path_join(BASEPATH_FFI, 'lib', 'librust_curl_ffi.so');

        if (!file_exists($this->libPath)) {
            $errMessage = "Rust Library (.so) not found at: " . $this->libPath;
            $this->logError($errMessage);
            throw new RuntimeException($errMessage);
        }

        $this->ffi = \FFI::cdef(
            "
            char* ExecuteRequest(const char* jsonInput);
            char* ExecuteMultiRequest(const char* jsonInput);
            void free_rust_string(char* ptr);
        ",
            $this->libPath,
        );
    }

    public function request(array $options): array
    {
        try {
            $payload = json_encode($options);
            $ptr = $this->ffi->ExecuteRequest($payload);
            return $this->processResult($ptr);
        } catch (Exception $e) {
            $this->logError("Request Exception: " . $e->getMessage());
            return ["status" => 0, "body" => "", "error" => $e->getMessage()];
        }
    }

    public function multiRequest(array $requests): array
    {
        try {
            $payload = json_encode(["requests" => $requests]);
            $ptr = $this->ffi->ExecuteMultiRequest($payload);
            return $this->processResult($ptr);
        } catch (Exception $e) {
            $this->logError("MultiRequest Exception: " . $e->getMessage());
            return [];
        }
    }

    private function processResult($ptr): array
    {
        if ($ptr === null) {
            throw new Exception("Rust FFI returned null pointer.");
        }

        $json = \FFI::string($ptr);

        // Membebaskan memori di sisi Rust
        $this->ffi->free_rust_string($ptr);

        // $data = json_decode($json, true);
        // Gunakan flag bitwise untuk keamanan extra pada data besar
        $data = json_decode($json, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Decode Error: " . json_last_error_msg());
        }

        return $data ?? [];
    }

    private function logError(string $message)
    {
        if ($this->debug) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($trace[1]) ? "{$trace[1]["class"]}::{$trace[1]["function"]}" : "Global";

            write_log("[$caller] $message", \App\Core\Http\RustHttpClient::class, "error", "error_RustHttpClient.log");
        }
    }
}
