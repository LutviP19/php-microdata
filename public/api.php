<?php
/**
 * @author LutviP19 <lutvip19@gmail.com>
 * FrankenPHP Worker Mode Implementation
 */

// file: public/api.php

if (!defined('BASEPATH')) {
    // __DIR__ is /app/public, up 1 level to /app
    define('BASEPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

require_once BASEPATH . "app/Core/init.php";

// load router
$router = require BASEPATH . "config/router.php";

// Mapping Models
$models = require BASEPATH . "config/models.php";

$loader = new \App\Core\Support\RecursiveModelLoader($models);

// Auto Select middleware
$privateNetwork = true; // set false if you want to publish it to the internet
// dd($privateNetwork);

// ---2. THE HANDLER FUNCTION ---
// This function is called every time there is an incoming request.
// Superglobals ($_GET, $_POST, $_SERVER) are automatically reset by FrankenPHP.
$handler = static function () use ($loader, $router, $models, $privateNetwork) {
    try {
        // Flag if using microdata_worker
        if (!function_exists("microdata_worker")) {
            function microdata_worker()
            {
                return true;
            }
        }

        // Using a per-request Identity Class, a replacement for Session
        \App\Core\Auth\Identity::clear();
        $userData = [
            // The identity is always fetched fresh from $_SERVER in each loop
            "IPaddress" => clientIP(),
            "userAgent" => get_short_ua(),
            "fingerprint" => get_device_fingerprint(!config('app.debug')),
        ];
        \App\Core\Auth\Identity::set($userData);

        //====== Middleware
        if ($privateNetwork) {
            // Run the CORS function
            handle_cors();

            // IP Filter -privateNetwork Only API
            check_ip_access(clientIP());
        }

        // Only Accept Valid JSON
        if (!expects_json() || !handle_json_request()) {
            json_response([], 406, "Invalid JSON", ["input" => "Invalid JSON format."]);
        } else {
            // Run Business Logic Middleware
            if ($privateNetwork) {
                // Validate API Key
                if (!validateApiKey()) {
                    json_response([], 401, "Unauthorized", ["auth" => "Unauthorized: Invalid API Key"]);
                }
            }

            //===== Middleware Global access
            // Identity::check
            if (is_null(\App\Core\Auth\Identity::check())) {
                json_response([], 403, "Forbidden", ["auth" => "Invalid credentials!"]);
            }
        }
        //====== End Middleware

        // Make sure the variable is reset on every request
        http_response_code(200);
        $isJson = is_json_request();
        $isValidApiKey = validateApiKey();

        // Filter IP - API Only
        if ($isJson) {
            check_ip_access(clientIP());
        }

        // Validate API Key
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
        include BASEPATH . "config/static-router.php";

        // Check router.php (Main Route to Models)
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
            // Load Main Model
            extract($resolved);

            if ($model && file_exists($modelPath)) {
                // Parameter ID for edit/detail
                if (is_numeric($lastSegment)) {
                    $_GET["id"] = (int) $lastSegment;
                }
                // dd($_GET);

                // Run the request process
                $page = $resolved["page"];

                // Middleware Model for API Only
                if (!$isJson || !expects_json()) {
                    if (!file_exists(realpath(BASEPATH . "views/partial/" . $page . ".php"))) {
                        $loader->notFoundHandler($page, $modelPath);
                    }
                }

                include BASEPATH . "app/Core/process_request.php";
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
        // STABILITY KEY: Always clear the identity at the end of the request
        // Whatever happens (error/success), the memory is cleared
        \App\Core\Auth\Identity::clear();
    }
};

// Worker Loop (Primary Key)
if (function_exists("frankenphp_handle_request")) {
    $maxRequests = (int) ($_SERVER["MAX_REQUESTS"] ?? 500);
    for ($nbRequests = 0; $nbRequests < $maxRequests; ++$nbRequests) {
        // The script MUST reach this line for the Worker to be considered successful
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