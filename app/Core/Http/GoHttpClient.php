<?php
/**
 * GoHttpClient for FFI
 * @package PHP-Microdata
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Http;

use FFI;
use Exception;
use RuntimeException;

class GoHttpClient
{
    private $ffi;
    private $libPath;
    private $debug = true;

    public function __construct(string $libPath = null, $debug = null)
    {
        $this->debug = $debug ?? config('app.debug');
        $this->libPath = $libPath ?? realpath(BASEPATH_FFI . '/lib/curlgo.so');

        if (!file_exists($this->libPath)) {
            $this->logError("Shared Library (.so) not found at: " . $this->libPath);
            throw new RuntimeException("HTTP Bridge Library missing.");
        }

        try {
            $cdef = "
                char* ExecuteRequest(char* jsonInput);
                char* ExecuteMultiRequest(char* jsonInput);
                void free(void* ptr);
            ";
            $this->ffi = FFI::cdef($cdef, $this->libPath);
        } catch (\FFI\Exception $e) {
            $this->logError("FFI Initialization Failed: " . $e->getMessage());
            throw new RuntimeException("FFI Error: Check your CDEF or .so architecture.");
        }
    }

    /**
     * Wrapper dengan Error Trace
     */
    public function request(array $options = [], $method = null, $url = null): array
    {
        try {
            $payload = json_encode([
                'method'  => strtoupper($method) ?? $options['method'],
                'url'     => $url ?? $options['url'],
                'headers' => $options['headers'] ?? [],
                'body'    => $options['body'] ?? '',
                'timeout' => $options['timeout'] ?? 30,
            ]);
            // dd($payload);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON Encode Error: " . json_last_error_msg());
            }

            $resPtr = $this->ffi->ExecuteRequest($payload);
            $result = $this->processResult($resPtr);

            // Trace error jika engine Go mengirim pesan error
            if (!empty($result['error'])) {
                $this->logError("Go Engine Exception on $url: " . $result['error']);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError("Request Exception: " . $e->getMessage());
            return ['status' => 0, 'body' => '', 'error' => $e->getMessage()];
        }
    }

    public function multiRequest(array $requests): array
    {
        try {
            $payload = json_encode(['requests' => $requests]);
            $resPtr = $this->ffi->ExecuteMultiRequest($payload);
            return $this->processResult($resPtr);
        } catch (Exception $e) {
            $this->logError("MultiRequest Exception: " . $e->getMessage());
            return [];
        }
    }

    private function processResult($ptr): array
    {
        if ($ptr === null) {
            throw new Exception("FFI returned null pointer.");
        }

        $json = FFI::string($ptr);
        $this->ffi->free($ptr);
        
        // Gunakan flag bitwise untuk keamanan extra pada data besar
        $data = json_decode($json, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Decode Error: " . json_last_error_msg());
        }

        return $data;
    }

    private function logError(string $message)
    {
        if ($this->debug) {
            // Gunakan fungsi log internal Anda atau error_log bawaan
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($trace[1]) ? "{$trace[1]['class']}::{$trace[1]['function']}" : 'Global';
            
            write_log("[$caller] $message", 'App\Core\Http\GoHttpClient', 'error', 'error_GoHttpClient.log');
        }
    }
}