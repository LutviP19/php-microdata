<?php

namespace App\Core\Support;

use FFI;
use Exception;

/**
 * Handling RabbitMQ Broker with FFI.
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */
class RabbitFFI {
    private $ffi;
    private $url;

    public function __construct($url = null) {
        $default_mb = Config::get('default_mb');
        $defaultUrl = "amqp://".Config::get("broker.{$default_mb }.username").":".Config::get("broker.{$default_mb }.password")."@".Config::get("broker.{$default_mb }.host").":".Config::get("broker.{$default_mb }.port")."/";
        $this->url = $url ?: $defaultUrl;
        // dd($this->url);

        // Jalankan validasi
        $this->validateAmqpUrl($this->url);

        $this->loadFFI();
    }

    /**
     * Validasi format URL AMQP
     * @param string $url
     * @throws \Exception
     */
    private function validateAmqpUrl($url) 
    {
        // 1. Validasi dasar menggunakan filter_var
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception("Format URL tidak valid secara umum.");
        }

        // 2. Validasi Skema (Harus amqp atau amqps)
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['amqp', 'amqps'])) {
            throw new \Exception("Skema URL tidak didukung: {$scheme}. Gunakan 'amqp://' atau 'amqps://'.");
        }

        // 3. Validasi Komponen Penting (Host)
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            throw new \Exception("Host RabbitMQ tidak ditemukan dalam URL.");
        }

        // 4. Validasi Regex (Opsional: Memastikan User & Pass ada jika diperlukan)
        // Pola: amqp(s)://user:pass@host:port/path
        $pattern = '/^amqps?:\/\/.+:.+@.+/i';
        if (!preg_match($pattern, $url)) {
            // Berikan peringatan jika kredensial kosong pada URL non-default
            $message = "RabbitFFI Warning: URL AMQP mungkin tidak memiliki kredensial lengkap.";
            throw new \Exception($message);
        }
    }

    private function loadFFI() {
        // Definisikan signature fungsi C dari Go
        $header = "
            char* Publish(char* url, char* queueName, char* body);
            char* Consume(char* url, char* queueName);
            void free(void* ptr);
        ";
        
        try {
            $this->ffi = FFI::cdef($header, BASEPATH . '/ffi/lib/mq.so');
        } catch (Exception $e) {
            throw new Exception("Gagal memuat Shared Object: " . $e->getMessage());
        }
    }

    public function send($queue, $message) {
        // Konversi string ke key array format jika perlu
        $payload = is_array($message) ? json_encode($message) : $message;

        $err = $this->ffi->Publish($this->url, $queue, $payload);

        if ($err !== null) {
            $errorMsg = FFI::string($err);
            // Jangan lupa free memory yang dialokasikan C.CString di Go
            $this->ffi->free($err);
            throw new Exception("RabbitMQ Error: " . $errorMsg);
        }

        return true;
    }

    public function receive($queue) {
        $result = $this->ffi->Consume($this->url, $queue);
        if ($result === null) return null;

        $str = FFI::string($result);
        // Kita asumsikan jika string mulai dengan ERROR: itu adalah kegagalan koneksi
        if (strpos($str, 'ERROR:') === 0) {
            throw new Exception($str);
        }
        return $str;
    }    
}
