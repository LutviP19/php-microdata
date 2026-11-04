<?php
/**
 * @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    // __DIR__ adalah /app/public, naik 1 tingkat menjadi /app
    define('BASEPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

require_once BASEPATH . "app/Core/init.php";

// Check Cookie
if (!isset($_COOKIE[config("session.sess_name")])) { 
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        try {
            $driver = env('SESSION_DRIVER', 'files');
            ini_set('session.save_handler', $driver);

            if ($driver === "redis") {
                $redisHost = config("redis.default.host", "127.0.0.1");
                $redisPort = config("redis.default.port", 6379);
                $redisPass = config("redis.default.password");
                $lifetime  = (int) config("session.lifetime", 120) * 60;


                $redisPath = "tcp://{$redisHost}:{$redisPort}";
                if (!is_null($redisPass) && $redisPass !== '') {
                    $redisPath .= "?auth=" . urlencode((string)$redisPass);
                }

                ini_set("session.save_path", $redisPath);
                ini_set("session.gc_maxlifetime", $lifetime);
            } else {
                
                ini_set('session.save_handler', 'files');
                ini_set('session.save_path', storage_path('framework/sessions'));
            }
        } catch (\Exception $e) {
            $errLog = "An unexpected error occurred during session init: " . $e->getMessage();
            if (function_exists('write_log')) {
                write_log('error', $errLog, 'session.save_path.Setup');
            }

            // Safe fallback to driver files if Redis has problems
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', storage_path('framework/sessions'));
        }

        // Set custom session name
        session_name(config("session.sess_name"));

        // Run the optimized built-in session opening function of the helper
        if (function_exists('bp_session_start')) {
            bp_session_start();
        } else {
            ini_set('session.use_strict_mode', 1);
            @session_start();
        }
    }

    // // The handler after a successful session is active
    // if (session_status() === PHP_SESSION_ACTIVE) {
    //     if (!\App\Core\Support\Session::has("IPaddress")) {
    //         \App\Core\Support\Session::set("IPaddress", clientIP());
    //     }

    //     if (!\App\Core\Support\Session::has("userAgent")) {
    //         $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "Unknown";
    //         \App\Core\Support\Session::set("userAgent", $userAgent);
    //     }
    // }

} else {
    // Set session from cache
    $userData = [
        "IPaddress" => clientIP(),
        "userAgent" => $_SERVER["HTTP_USER_AGENT"] ?? "Unknown",
        "fingerprint" => get_device_fingerprint(!config('app.debug')),
    ];
    \App\Core\Auth\Identity::set($userData);
    // dd($_SESSION, true);
}

use App\Core\Support\Session;

if (session_status() === PHP_SESSION_NONE) {
    bp_session_start();

    // Set Client Identity
    Session::set("IPaddress", clientIP());
    Session::set("userAgent", $_SERVER["HTTP_USER_AGENT"] ?? "Unknown");
}

//====== Middleware
// Run the CORS function
handle_cors();

// Filter IP - API Only
if (is_json_request()) {
    check_ip_access(clientIP());
}

// Validate API Key
if (!validateApiKey()) {
    json_response([], 401, "Unauthorized", ["auth" => "Unauthorized: Invalid API Key"]);
}

// Auth Session
if (isset($_COOKIE["PMDSESSID"])) {
    // // Test Invalid
    // $_SESSION['IPaddress'] = '192.168.0.101';

    if (!checkSession()) {
        if (is_json_request()) {
            json_response([], 403, "Forbidden", ["auth" => "Invalid credentials!"]);
        } else {
            http_response_code(isHtmx() ? 200 : 403);
            include BASEPATH . "views/error/403.php";
        }
        die();
    }
}
//====== End Middleware

// ---SEO ROUTING LOGIC ---
// Retrieves the path from the URL (eg: /dashboard or /dashboard/profile)
$requestPath = parse_url((string) $_SERVER["REQUEST_URI"], PHP_URL_PATH);
// dd($requestPath);
$lastSegment = basename($requestPath);
$urlSegments = explode("/", trim($requestPath, "/"));
$page = $urlSegments[0] !== "" ? $urlSegments[0] : "dashboard";
// dd($page);
$resolved = null;
$isPageExists = true;
$isAllowAccess = true;
// -------------------------

// // --- Test Auth Gate
// // Set Perrmissions 1: Admin, 2: Manager, 3: Staff
// \App\Core\Auth\Gate::loadAbilities(1, 1);
// \App\Core\Auth\Gate::loadAbilities(2, 2);
// $permission = 'asset-delete';
// \App\Core\Auth\Gate::authorize($permission);
// // ===========================================

// Static router for execute single php file
include BASEPATH . "config/static-router.php";

// Load Router and Mapping Models
$router = require BASEPATH . "config/router.php";
$models = require BASEPATH . "config/models.php";
// dd($models);
$loader = new \App\Core\Support\RecursiveModelLoader($models);

// Cek router.php (Main Route to Models)
if (isset($router[$page])) {
    $normalPath = $router[$page];
    $resolved = $loader->resolve(formatRoutePath($normalPath)); // Changing the "v1/Dashboard" format to "v1-dashboard"
} else {
    $customPath = $router[$requestPath] ?? null;
    if (is_numeric($lastSegment)) {
        $fixReqPath = str_replace("/{$lastSegment}", "", $requestPath);
        $customPath = $router[$fixReqPath];
    }

    if ($customPath && ($resolved = $loader->resolve(formatRoutePath($customPath)))) {
        $resolved["page"] = ltrim($requestPath, "/");
    }
}
// dd($resolved, true); //debug: $resolved | $page

// Otherwise resolve fallback to default $page
if (!$resolved) {
    $resolved = $loader->resolve($page);
}
// dd($resolved, true);

if ($resolved && file_exists($resolved["modelPath"])) {
    // Load Model Utama
    extract($resolved);
    
    if ($model && file_exists($modelPath)) {
        // Parameter ID for edit/detail
        if (is_numeric($lastSegment)) {
            $_GET["id"] = (int) $lastSegment;
        }
        // dd($_GET);

        // Run the request process
        $page = $resolved["page"];
        // dd($page);

        // Middleware Model for API Only
        if (!is_json_request() || !expects_json()) {
            if (!file_exists(realpath(BASEPATH . "views/partial/" . $page . ".php"))) {
                $loader->notFoundHandler($page, $modelPath);
            }
        }

        include_once BASEPATH . "app/Core/process_request.php";
    } else {
        // Handle 404
        $loader->notFoundHandler($page, $modelPath);
    }
} else {
    // Handle 404 - API JSON Only
    $model = $router[$page] ?? $page;
    $modelPath = "/app/Models/" . $model . "Model.php";
    $loader->notFoundHandler($model, $modelPath, true);
}

// Load HTML View
// Determine whether to go to the login page
$isLoginPage = $page === "login";
$isPageExists = $viewPath = realpath(BASEPATH . "views/partial/" . $page . ".php");
// dd($viewPath);

// Handle 404 - Non HTMX
if (!isHtmx() && !$isPageExists) {
    http_response_code(isHtmx() ? 200 : 404);
    include BASEPATH . "views/error/404.php";
    exit();
}

if (isHtmx()) {
    if ($viewPath && file_exists($viewPath) && str_starts_with($viewPath, realpath(BASEPATH . "views/"))) {
        include $viewPath;
    } else {
        if ($isLoginPage) {
            // Render only login page (no sidebar/nav)
            include BASEPATH . "views/login.php";
        } else {
            http_response_code(isHtmx() ? 200 : 404);
            include BASEPATH . "views/error/404.php";
        }
    }
} else {
    if ($isLoginPage) {
        // Render only login page (no sidebar/nav)
        include BASEPATH . "views/login.php";
    } else {
        // If direct access (not AJAX), load the main wrapper (index.php)
        include BASEPATH . "views/index.php";
    }
}