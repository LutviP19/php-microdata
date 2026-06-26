<?php

namespace App\Core\Support;

use FFI;
use Exception;

/**
 * Handling RabbitMQ Broker with FFI.
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */
class RabbitFFI
{
    private $ffi;
    private $url;

    public function __construct(?string $url = null)
    {
        $default_mb = Config::get("default_mb");
        $defaultUrl =
            Config::get("broker.{$default_mb}.scheme") .
            "://" .
            Config::get("broker.{$default_mb}.username") .
            ":" .
            Config::get("broker.{$default_mb}.password") .
            "@" .
            Config::get("broker.{$default_mb}.host") .
            ":" .
            Config::get("broker.{$default_mb}.port") .
            "/";
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
        if (!in_array($scheme, ["amqp", "amqps"])) {
            $messageErr = "Skema URL tidak didukung: {$scheme}. Gunakan 'amqp://' atau 'amqps://'.";
            if (config("app.debug")) {
                \write_log(
                    [
                        "url" => $url,
                        "message" => $messageErr,
                    ],
                    "App\Core\Support\RabbitFFI.validateAmqpUrl",
                    "error",
                    "error_RabbitFFI.log",
                );
            }
            throw new \Exception($messageErr);
        }

        // 3. Validasi Komponen Penting (Host)
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            throw new \Exception("Host RabbitMQ tidak ditemukan dalam URL.");
        }

        // 4. Validasi Regex (Opsional: Memastikan User & Pass ada jika diperlukan)
        // Pola: amqp(s)://user:pass@host:port/path
        $pattern = "/^amqps?:\/\/.+:.+@.+/i";
        if (!preg_match($pattern, $url)) {
            // Berikan peringatan jika kredensial kosong pada URL non-default
            $messageErr = "RabbitFFI Warning: URL AMQP mungkin tidak memiliki kredensial lengkap.";
            if (config("app.debug")) {
                \write_log(
                    [
                        "url" => $url,
                        "message" => $messageErr,
                    ],
                    "App\Core\Support\RabbitFFI.validateAmqpUrl",
                    "error",
                    "error_RabbitFFI.log",
                );
            }

            throw new \Exception($messageErr);
        }
    }

    private function loadFFI()
    {
        $libPath = path_join(BASEPATH_FFI, 'lib', 'mq.so');

        if (!file_exists($libPath)) {
            throw new \Exception("Library Shared Object tidak ditemukan di: " . $libPath);
        }

        // Definisikan signature fungsi C dari Go
        $header = "
            char* Publish(char* url, char* queueName, char* body);
            char* Consume(char* url, char* queueName);
            void free(void* ptr);
        ";

        try {
            $this->ffi = FFI::cdef($header, $libPath);
        } catch (Exception $e) {
            $message = "Gagal memuat Shared Object: " . $e->getMessage();
            if (config("app.debug")) {
                \write_log(
                    [
                        "libPath" => $libPath,
                        "message" => $message,
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                        // 'trace' => $e->getTraceAsString(),
                    ],
                    "App\Core\Support\RabbitFFI.loadFFI",
                    "error",
                    "error_RabbitFFI.log",
                );
            }

            throw new Exception($message);
        }
    }

    public function send($queue, $message)
    {
        // Konversi string ke key array format jika perlu
        $payload = is_array($message) ? json_encode($message) : $message;

        $err = $this->ffi->Publish($this->url, $queue, $payload);

        if ($err !== null) {
            $errorMsg = FFI::string($err);
            // Jangan lupa free memory yang dialokasikan C.CString di Go
            $this->ffi->free($err);

            $messageErr = "RabbitMQ Error: " . $errorMsg;
            if (config("app.debug")) {
                \write_log(
                    [
                        "payload" => $payload,
                        "message" => $messageErr,
                    ],
                    "App\Core\Support\RabbitFFI.send",
                    "error",
                    "error_RabbitFFI.log",
                );
            }

            throw new \Exception($messageErr);
        }

        return true;
    }

    public function receive($queue)
    {
        $result = $this->ffi->Consume($this->url, $queue);
        if ($result === null) {
            return null;
        }

        $str = FFI::string($result);
        // Kita asumsikan jika string mulai dengan ERROR: itu adalah kegagalan koneksi
        if (str_starts_with($str, "ERROR:")) {
            if (config("app.debug")) {
                \write_log(
                    [
                        "queue" => $queue,
                        "message" => $str,
                    ],
                    "App\Core\Support\RabbitFFI.receive",
                    "error",
                    "error_RabbitFFI.log",
                );
            }

            throw new \Exception($str);
        }
        return $str;
    }
}
