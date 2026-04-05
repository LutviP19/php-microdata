<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


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

    // Sample real Task Clean temporary files
    include BASEPATH . '/cron/cleanTmp.php';
    
    echo "Sukses: " . $message;
} else {
    $sisa = $interval - ($currentTime - $lastRun);
    echo "Belum waktunya. Tunggu $sisa detik lagi." . PHP_EOL;
}
