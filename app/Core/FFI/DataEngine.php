<?php

namespace App\Core\FFI;

use FFI;
use RuntimeException;

class DataEngine
{
    private $ffi;
    private $libPath;

    public function __construct(?string $libPath = null)
    {
        // Gunakan konstanta BASEPATH_FFI jika tersedia, atau fallback ke direktori saat ini
        $defaultPath = defined('BASEPATH_FFI') ? BASEPATH_FFI . '/lib/libdata_engine.so' : __DIR__ . '/lib/libdata_engine.so';
        $this->libPath = $libPath ?? $defaultPath;

        if (!file_exists($this->libPath)) {
            throw new RuntimeException("Rust Library (.so) tidak ditemukan di: " . $this->libPath);
        }

        // Definisi C harus sesuai persis dengan signature di Rust (src/lib.rs)
        $this->ffi = FFI::cdef("
            char* get_item_at(int index);
            int get_storage_count();
            char* debug_first_item();
            int load_data(const char* json_raw);
            int append_data(const char* json_chunk);
            void clear_data();
            char* filter_loaded_data(const char* filter_key, const char* filter_value, const char* mode);
            char* process_data_scalable(const char* json, const char* key, const char* val, const char* mode);
            void free_rust_string(char* ptr);
        ", $this->libPath);
    }

    /**
     * Membersihkan data yang ada di memori Rust
     */
    public function clear(): void
    {
        $this->ffi->clear_data();
    }

    /**
     * Cek count data di memori Rust
     */
    public function getCount(): int
    {
        try {
            $ptr = $this->ffi->get_storage_count();
            if ($ptr !== null) 
                return (int) $ptr;
        } catch (\Throwable $e) {
            return "Error FFI: " . $e->getMessage() . PHP_EOL;
        }
    }    

    // streamAll (Generator)
    public function streamAll(): \Generator
    {
        $count = $this->getCount();

        for ($i = 0; $i < $count; $i++) {
            $ptr = $this->ffi->get_item_at($i);
            
            if ($ptr !== null) {
                $jsonLine = FFI::string($ptr);
                $this->ffi->free_rust_string($ptr); // Langsung bebas!

                yield json_decode($jsonLine, true);
                
                // Opsional: bersihkan memori PHP per baris
                unset($jsonLine);
            }
        }
    }

    /**
     * Cek count data di memori Rust
     */
    public function debugFirstItem()
    {
        try {
            $ptr = $this->ffi->debug_first_item();
            echo "Fungsi dipanggil, pointer: " . ($ptr === null ? 'NULL' : 'Found') . PHP_EOL;
            
            if ($ptr !== null) {
                $content = FFI::string($ptr);
                $this->ffi->free_rust_string($ptr);

                return $content;
            }
        } catch (\Throwable $e) {
            return "Error FFI: " . $e->getMessage() . PHP_EOL;
        }

    }

    /**
     * Menambahkan potongan data (chunk) ke memori Rust secara langsung
     */
    public function appendChunk(array $chunk): int
    {
        $json = json_encode($chunk);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Gagal encode JSON: " . json_last_error_msg());
        }
        
        $count = $this->ffi->append_data($json);
        unset($json); // Segera bebaskan memori string di PHP
        return $count;
    }

    /**
     * Memuat data besar sekaligus dengan cara memecahnya (chunking) 
     * untuk menghindari OOM di sisi PHP.
     */
    public function loadInChunks(array $data, int $chunkSize = 50000): void 
    {
        $this->clear();
        $chunks = array_chunk($data, $chunkSize);
        
        foreach ($chunks as $index => $chunk) {
            $this->appendChunk($chunk);
            echo "Loading chunk " . ($index + 1) . " (Total: " . (($index + 1) * $chunkSize) . " rows)...\r";
            unset($chunks[$index]); // Hapus chunk yang sudah diproses dari memori PHP
        }
        echo PHP_EOL . "Load Complete!" . PHP_EOL;
    }

    /**
     * Melakukan filter pada data yang sudah menetap (loaded) di memori Rust.
     * Sangat cepat karena tidak ada proses kirim ulang data raksasa.
     */
    public function filterOnly(string $key, $value, string $mode = "equals"): array 
    {
        $ptr = $this->ffi->filter_loaded_data($key, (string)$value, $mode);
        
        if ($ptr === null) return [];

        $jsonStr = FFI::string($ptr);
        $res = json_decode($jsonStr, true);
        
        // WAJIB: Bebaskan pointer yang dialokasikan oleh Rust
        $this->ffi->free_rust_string($ptr);
        
        return $res ?? [];
    }

    /**
     * Mode Stateless: Kirim data, filter, lalu lupakan.
     * Cocok untuk data berukuran kecil hingga menengah.
     */
    public function filter(array $data, string $key, $value, string $mode = "equals"): array
    {
        $ptr = $this->ffi->process_data_scalable(
            json_encode($data),
            $key,
            (string)$value,
            $mode
        );

        if ($ptr === null) return [];

        $jsonStr = FFI::string($ptr);
        $result = json_decode($jsonStr, true) ?? [];
        
        $this->ffi->free_rust_string($ptr);

        return $result;
    }
}