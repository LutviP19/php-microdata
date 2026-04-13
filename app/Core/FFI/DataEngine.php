<?php

namespace App\Core\FFI;

use FFI;
use RuntimeException;

class DataEngine
{
    private $ffi;
    private $libPath;

    public function __construct(string $libPath = null)
    {
        // Pastikan path menunjuk ke file .so hasil cargo build --release
        $this->libPath = $libPath ?? realpath(BASEPATH_FFI . '/lib/libdata_engine.so');

        if (!file_exists($this->libPath)) {
            $errMessage = "Rust Library (.so) not found at: " . $this->libPath;
            $this->logError($errMessage);
            throw new RuntimeException($errMessage);
        }

        $this->ffi = FFI::cdef("
            char* process_data_scalable(const char* json, const char* key, const char* val, const char* mode);
            void free_rust_string(char* ptr);
        ", $this->libPath);
    }

    /**
     * @param array $data Data mentah
     * @param string $key Kolom yang difilter
     * @param mixed $value Nilai pembanding
     * @param string $mode "equals" atau "gt" (Greater Than)
     */
    public function filter(array $data, string $key, $value, string $mode = "equals"): array
    {
        $ptr = $this->ffi->process_data_scalable(
            json_encode($data),
            $key,
            (string)$value,
            $mode
        );

        $json = FFI::string($ptr);
        $this->ffi->free_rust_string($ptr);

        return json_decode($json, true) ?? [];
    }
}