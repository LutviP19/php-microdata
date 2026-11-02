<?php

/**
 * Init the Application
 * 
 * @author LutviP19 <lutvip19@gmail.com>
 * @package PHP-Microdata(PMD)
 */

define('APP_SESSID', 'PMDSESSID');

if (!defined('BASEPATH')) {
    // Go up 2 levels from /app/app/Core to reach /app (Project Root)
    define('BASEPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
}

// Optional: If you also define APPPATH (path to internal app folder)
if (!defined('APPPATH')) {
    // Move up 1 level from /app/app/Core to reach /app/app
    define('APPPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

if (!defined("BASEPATH_FFI")) {
    define("BASEPATH_FFI", BASEPATH . DIRECTORY_SEPARATOR . "ffi" . DIRECTORY_SEPARATOR);
}

// echo BASEPATH_FFI;
// exit(0);

// only level Deprecated & User Deprecated
// error_reporting(E_DEPRECATED | E_USER_DEPRECATED);
error_reporting(E_ALL);
ini_set("display_errors", 1);

/**
 * Require the composer autoload File.
 */
require_once BASEPATH . "vendor/autoload.php";

// Min php version
ensure_minimum_php_version(config('app.php_version'));

// Muat file .env
load_env();

// Set defaut konfigurasi timezone PHP
date_default_timezone_set(env("APP_TIMEZONE", "Asia/Jakarta"));

// Use Throwable to catch Errors.
set_exception_handler(function (\Throwable $exception) {
    $message = $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $trace = $exception->getTraceAsString(); // Ini untuk mengambil stack trace

    $logEntry = "Message: $message\n";
    $logEntry .= "Location: $file on line $line\n";
    // $logEntry .= "Stack Trace:\n$trace\n";
    $logEntry .= str_repeat("-", 50); // Garis pemisah antar log
    if(config('app.drbug')) {
        write_log($logEntry, "App.Core", "error", "app-error.log");
    }
    
    // Handler JSON Output
    if (is_json_request()) {
        $status = 500;
        $message = "An internal error occurred. Please try again later.";
        json_response([], $status, $message);
        exit();
    } else {
        // Present a user-friendly view/response
        // http_response_code(500);
        // echo "<h1>An internal error occurred. Please try again later.</h1>";
        // // In a production environment, avoid echoing the raw message
        include BASEPATH . "views/error/500.php";
        die();
    }
});

// Registers the configuration to the application.
use App\Core\Support\App;
App::register("config", require BASEPATH . "config/app.php");
App::register("routing_external_api", require BASEPATH . "config/external-api.php");


// Check Cookie
if (!isset($_COOKIE[APP_SESSID])) { 
    if (session_status() === PHP_SESSION_NONE) {
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
        session_name(APP_SESSID);

        // Run the optimized built-in session opening function of the helper
        if (function_exists('bp_session_start')) {
            bp_session_start();
        } else {
            ini_set('session.use_strict_mode', 1);
            @session_start();
        }
    }

    // The handler after a successful session is active
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (!\App\Core\Support\Session::has("IPaddress")) {
            \App\Core\Support\Session::set("IPaddress", clientIP());
        }

        if (!\App\Core\Support\Session::has("userAgent")) {
            $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "Unknown";
            \App\Core\Support\Session::set("userAgent", $userAgent);
        }
    }

} else {

    if (!isset($_SESSION)) {
        $_SESSION = [];
    }

    // Set session from cache
    $expTime = config("session.exptime", 7200);
    $_SESSION = array_merge($_SESSION, cacheContent('get', '*'. $_COOKIE[APP_SESSID] .'*', $expTime) ?: []);
    // dd($_SESSION);
}