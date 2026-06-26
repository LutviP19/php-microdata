<?php
/**
 * @author LutviP19 <lutvip19@gmail.com>
 * FrankenPHP Worker Mode Implementation
 */

// file: public/api.php

// if (!defined("BASEPATH")) {
//     define("BASEPATH", __DIR__ . "/..");
// }

// if (!defined("BASEPATH")) {
//     // === PERBAIKAN 1: Gunakan realpath() untuk membersihkan path dari awal ===
//     $calculatedPath = realpath(__DIR__ . "/..");
    
//     // === PERBAIKAN 2: Jika terdeteksi double /app/app/, bersihkan menjadi /app/ ===
//     if (str_contains($calculatedPath, '/app/app')) {
//         $calculatedPath = str_replace('/app/app', '/app', $calculatedPath);
//     }
    
//     define("BASEPATH", $calculatedPath);
// }

if (!defined('BASEPATH')) {
    // __DIR__ adalah /app/public, naik 1 tingkat menjadi /app
    define('BASEPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// echo BASEPATH;
// exit(0);

// --- 1. INITIALIZATION (BOOTSTRAP) ---
// Bagian ini hanya dijalankan SEKALI saat worker pertama kali naik.
// Sangat efisien untuk i5-3210M karena load file init & composer dilakukan sekali saja.
require_once BASEPATH . "/app/Core/init.php";
// include BASEPATH . "/config/router.php";

// write_log("Api server run", 'api.php', 'debug', 'debug_API.log');

// load router
$router = require BASEPATH . "/config/router.php";

// Mapping Models
$models = require BASEPATH . "/config/models.php";

$loader = new \App\Core\Support\RecursiveModelLoader($models);

// Auto Select middleware
$privateNetwork = true; // set false jika ingin di publish ke internet
dd($privateNetwork);

// --- 2. THE HANDLER FUNCTION ---
// Fungsi ini dipanggil setiap ada request masuk.
// Superglobals ($_GET, $_POST, $_SERVER) otomatis di-reset oleh FrankenPHP.
$handler = static function () use ($loader, $router, $models, $privateNetwork) {
    try {
        // Flag if using microdata_worker
        if (!function_exists("microdata_worker")) {
            function microdata_worker()
            {
                return true;
            }
        }

        // Menggunakan Class Identity yang bersifat per-request, pengganti Session
        \App\Core\Auth\Identity::clear();
        $userData = [
            // Identitas selalu diambil segar dari $_SERVER di setiap loop
            "ip" => clientIP(),
            "ua" => get_short_ua(),
            "fingerprint" => get_device_fingerprint(false), // readable
        ];
        \App\Core\Auth\Identity::set($userData);

        //====== Middleware
        if ($privateNetwork) {
            // Jalankan fungsi CORS
            handle_cors();

            // Filter IP - API privateNetwork Only
            check_ip_access(clientIP());
        }

        // Only Accept Valid JSON
        if (!expects_json() || !handle_json_request()) {
            json_response([], 406, "Invalid JSON", ["input" => "Invalid JSON format."]);
        } else {
            // Jalankan Middleware Bisnis Logic
            if ($privateNetwork) {
                // Validasi API Key
                if (!validateApiKey()) {
                    json_response([], 401, "Unauthorized", ["auth" => "Unauthorized: Invalid API Key"]);
                }
            }

            //===== Middleware Global akses
            // Identity::check
            if (is_null(\App\Core\Auth\Identity::check())) {
                json_response([], 403, "Forbidden", ["auth" => "Invalid credentials!"]);
            }
        }
        //====== End Middleware

        // Pastikan variabel di-reset setiap request
        http_response_code(200);
        $isJson = is_json_request();
        $isValidApiKey = validateApiKey();

        // Filter IP - API Only
        if ($isJson) {
            check_ip_access(clientIP());
        }

        // Validasi API Key
        if (!$isValidApiKey) {
            json_response([], 401, "Unauthorized", ["auth" => "Unauthorized: Invalid API Key"]);
            return;
        }

        // --- SEO ROUTING LOGIC ---
        $requestPath = parse_url((string) $_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $lastSegment = basename($requestPath);
        $urlSegments = explode("/", trim($requestPath, "/"));
        $page = $urlSegments[0] !== "" ? $urlSegments[0] : "dashboard";

        $resolved = null;

        // Static router for execute single php file
        include BASEPATH . "/config/static-router.php";

        // Cek router.php (Main Route to Models)
        if (isset($router[$page])) {
            $normalPath = $router[$page];
            $resolved = $loader->resolve(formatRoutePath($normalPath));
        } else {
            $customPath = $router[$requestPath] ?? null;
            if (is_numeric($lastSegment)) {
                $fixReqPath = str_replace("/{$lastSegment}", "", $requestPath);
                $customPath = $router[$fixReqPath] ?? null;
            }

            if ($customPath && ($resolved = $loader->resolve(formatRoutePath($customPath)))) {
                $resolved["page"] = ltrim($requestPath, "/");
            }
        }

        // Fallback default
        if (!$resolved) {
            $resolved = $loader->resolve($page);
        }

        if ($resolved && file_exists($resolved["modelPath"])) {
            // Load Model Utama
            extract($resolved);

            if ($model && file_exists($modelPath)) {
                // Parameter ID untuk edit/detail
                if (is_numeric($lastSegment)) {
                    $_GET["id"] = (int) $lastSegment;
                }
                // dd($_GET);

                // Jalankan proses request
                $page = $resolved["page"];

                // Middleware Model for API Only
                if (!$isJson || !expects_json()) {
                    if (!file_exists(realpath(BASEPATH . "/views/partial/" . $page . ".php"))) {
                        $loader->notFoundHandler($page, $modelPath);
                    }
                }

                include BASEPATH . "/app/Core/process_request.php";
            } else {
                // Handle 404
                $loader->notFoundHandler($page, $modelPath);
                return;
            }
        } else {
            // Handle 404 - API JSON Only
            $model = $router[$page] ?? $page;
            $modelPath = "/app/Models/" . $model . "Model.php";
            $loader->notFoundHandler($model, $modelPath, true);
            return;
        }
    } catch (\Throwable $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(500);
        echo "Worker Error: " . $e->getMessage();
        // Re-throw agar error detail muncul di log global
        throw $e;
    } finally {
        // KUNCI STABILITAS: Selalu bersihkan identitas di akhir request
        // Apapun yang terjadi (error/sukses), memori dibersihkan
        \App\Core\Auth\Identity::clear();
    }
};

// Worker Loop (Kunci Utama)
if (function_exists("frankenphp_handle_request")) {
    $maxRequests = (int) ($_SERVER["MAX_REQUESTS"] ?? 500);
    for ($nbRequests = 0; $nbRequests < $maxRequests; ++$nbRequests) {
        // Skrip HARUS mencapai baris ini agar Worker dianggap berhasil
        $keepRunning = \frankenphp_handle_request($handler);

        if (function_exists("gc_collect_cycles")) {
            gc_collect_cycles();
        }
        if (!$keepRunning) {
            break;
        }
    }
} else {
    $handler();
}