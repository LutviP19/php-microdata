<?php
/**
 * NativeCurlStreamer pure PHP
 * @package PHP-Microdata
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Http;

class NativeCurlStreamer
{
    /**
     * SINGLE STREAMING -returns an Array of results
     */
    public function singleStream(array $params): array
    {
        $ch = curl_init();
        $this->applyDefaultOpts($ch, $params);

        $responseBody = "";
        $headerContent = "";

        // Capture Header (Optional, useful for checking statusCode)
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (
            &$headerContent,
        ) {
            $headerContent .= $header;
            return strlen($header);
        });

        // Capturing Body via Streaming
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (
            &$responseBody,
        ) {
            $responseBody .= $chunk;
            
            $this->flushOutput();

            return strlen($chunk);
        });

        curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Get the header size for data splitting
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        // Function curl_close() is deprecated since 8.5
        if (version_compare(PHP_VERSION, "8.5.0", "<")) {
            curl_close($ch);
        }

        return [            
            "body" => $responseBody,
            "error" => $error ?: null,
            "statusCode" => $statusCode,
            "header" => $headerContent,
            "headerSize" => $headerSize,
        ];
    }

    /**
     * MULTI STREAMING -Returns an Array of Results
     */
    public function multiStream(array $tasks): array
    {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        // echo "[START] Multi Streaming (" . count($tasks) . " tasks)" . PHP_EOL;

        foreach ($tasks as $i => $params) {
            $ch = curl_init();
            $this->applyDefaultOpts($ch, $params);

            $results[$i] = ["body" => "", "error" => null, "statusCode" => 0];

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (
                &$results,
                $i,
            ) {
                $results[$i]["body"] .= $chunk;
                // echo "($i)"; // Indikator task mana yang sedang menerima data
                $this->flushOutput();
                return strlen($chunk);
            });

            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh, 0.1);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($handles as $i => $ch) {
            $results[$i]["error"] = curl_error($ch) ?: null;
            $results[$i]["statusCode"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);

            // Function curl_close() is deprecated since 8.5
            if (version_compare(PHP_VERSION, "8.5.0", "<")) {
                curl_close($ch);
            }
        }

        curl_multi_close($mh);
        // echo PHP_EOL . "[DONE] All multi-tasks finished." . PHP_EOL;

        return $results;
    }

    private function applyDefaultOpts($ch, array $params): void
    {
        $url = $params["url"];
        $method = strtoupper($params["method"] ?? "GET");
        $headers = $params["headers"] ?? [];
        $body = $params["body"] ?? "";

        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            if (is_string($key)) {
                // Changed "X-API-KEY": "value" to "X-API-KEY: value"
                $formattedHeaders[] = "{$key}: {$value}";
            } else {
                // If the format is "Name: Value", enter it directly
                $formattedHeaders[] = $value;
            }
        }
        
        if (is_array($body)) {
            $body = json_encode($body);
        }
        // dd($formattedHeaders, true);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $params["timeout"] ?? 60);
    }

    private function flushOutput(): void
    {
        if (php_sapi_name() === "cli") {
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }
}
