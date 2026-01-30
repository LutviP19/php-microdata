<?php 

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: sample.php

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}

/**
 * Require Core init File.
 */
require_once BASEPATH .'/app/Core/init.php';

$interval = 60; // Jalankan setiap 60 detik (1 menit)
$logFile = logs_path('cron_log.txt');
$lastRunFile = logs_path('last_run.txt');

$lastRun = file_exists($lastRunFile) ? (int)file_get_contents($lastRunFile) : 0;
$currentTime = time();

if (($currentTime - $lastRun) >= $interval) {
    // SIMULASI TUGAS YANG DIJALANKAN
    $message = "[" . date('Y-m-d H:i:s') . "] Tugas dijalankan: Membersihkan Cache setiap 1 menit..." . PHP_EOL;
    file_put_contents($logFile, $message, FILE_APPEND);
    
    // Perbarui waktu terakhir dijalankan
    file_put_contents($lastRunFile, $currentTime);
    
    echo "Sukses: " . $message;
} else {
    $sisa = $interval - ($currentTime - $lastRun);
    echo "Belum waktunya. Tunggu $sisa detik lagi." . PHP_EOL;
}
