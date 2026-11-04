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
