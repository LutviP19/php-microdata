<?php

/**
 * Init the Application
 * 
 * @author LutviP19 <lutvip19@gmail.com>
 * @package PHP-Microdata(PMD)
 */

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
    write_log($logEntry, "App.Core", "error", "app-error.log");

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

// INI Set Session
if (session_status() == PHP_SESSION_NONE) {
    try {
        // Starting the session will be the first we do.
        ini_set("session.save_handler", env("SESSION_DRIVER", "files"));

        if (env("SESSION_DRIVER") === "redis") {
            ini_set(
                "session.save_path",
                "tcp://" .
                    config("redis.default.host") .
                    ":" .
                    config("redis.default.port") .
                    "?auth" .
                    config("redis.default.password"),
            );
            ini_set("session.gc_maxlifetime", (int) (config("session.lifetime") * 60)); // Set default to 2 hours
        } else {
            ini_set("session.save_handler", "files");
            ini_set("session.save_path", storage_path("framework/sessions"));
        }
    } catch (\Exception $e) {
        $errLog = "An unexpected error occurred: " . $e->getMessage();
        write_log("error", $errLog, "session.save_path.Redis");

        // Fallback to default driver
        ini_set("session.save_handler", "files");
        ini_set("session.save_path", storage_path("framework/sessions"));
    }

    session_name("PMDSESSID"); // Set a custom session name
}