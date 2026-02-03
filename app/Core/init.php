<?php
/**
 * Init the Application
 * @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../..');
}

// Set defaut PHP conf
date_default_timezone_set('Asia/Jakarta');

/**
 * Require the composer autoload File.
 */
require_once BASEPATH .'/vendor/autoload.php';

// Gunakan Throwable untuk menangkap Error.
set_exception_handler(function (Throwable $exception) {
    // 1. Ambil detail error
    $message = $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $trace = $exception->getTraceAsString(); // Ini untuk mengambil stack trace

    // 2. Format pesan agar mudah dibaca di file log
    $logEntry = "Message: $message\n";
    $logEntry .= "Location: $file on line $line\n";
    // $logEntry .= "Stack Trace:\n$trace\n";
    $logEntry .= str_repeat('-', 50); // Garis pemisah antar log

    // Tulis ke log (menggunakan helper write_log)
    // write_log("Uncaught exception: " . $exception->getMessage(), 'App.Core', 'error', 'app-error.log');
    write_log($logEntry, 'App.Core', 'error', 'app-error.log');

    // Handler JSON Output
    if (is_json_request()) { 
        $status = 500;
        $message = 'An internal error occurred. Please try again later.';
        json_response([], $status, $message);
    }
    
    // Present a user-friendly view/response
    // http_response_code(500);
    // echo "<h1>An internal error occurred. Please try again later.</h1>";
    // // In a production environment, avoid echoing the raw message
    include BASEPATH . "/views/error/500.php";
    die();
});

// Muat file .env
load_env();

use App\Core\Support\App;

// Mendaftarkan konfigurasi ke aplikasi.
App::register('config', require BASEPATH . '/config/app.php');



