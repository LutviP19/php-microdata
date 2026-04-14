<?php
/**
 * Bootstrap for Worker
 * @author LutviP19 <lutvip19@gmail.com>
 */


define('APP_START', microtime(true));

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}

if (!defined('BASEPATH_FFI')) {
    define('BASEPATH_FFI', __DIR__ . '/../ffi');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Require the composer autoload File.
 */
require_once BASEPATH .'/vendor/autoload.php';

// Muat file .env
load_env();

// Set defaut konfigurasi timezone PHP
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

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
    write_log($logEntry, 'Worker.Cron', 'error', 'worker-error.log');
    // die();
});

// Mendaftarkan konfigurasi ke aplikasi.
use App\Core\Support\App;
App::register('config', require BASEPATH . '/config/app.php');
App::register('routing_external_api', require BASEPATH . '/config/external-api.php');
// dd(App::get('routing_external_api'));
