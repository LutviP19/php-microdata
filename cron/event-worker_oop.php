<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/event-worker.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use App\Core\Support\App;
use App\Core\Events\EventWorker;
use App\Core\Events\ListenerRegistry;
use App\Core\Database\Connection;

// Setup PDO Database
$db = Connection::make();

// Setup Config Redis
$redisConfig = [
    'host'     => config('redis.default.host'),
    'port'     => config('redis.default.port'),
    'database' => config('redis.default.database'),
    'password' => config('redis.default.password'),
];

// Scan semua listener secara otomatis
App::bootListeners();

// Registrasi Listener (Bisa dipindah ke file app/Events/listeners.php)
ListenerRegistry::listen('user.registered', function($data) {
    echo "Sending welcome email to: " . $data['email'] . PHP_EOL;
});

ListenerRegistry::listen('crawler.finished', function($data) {
    echo "Crawler finished for URL: " . $data['url'] . PHP_EOL;
});

//  Jalankan EventWorker
$once = true; // Mode Worker
$lockPath = BASEPATH . 'worker.lock';

// Mencegah Zombie Process
if ($once) {
    $lockFile = fopen($lockPath, 'c');
    
    if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
        echo "[!] Worker is already running. Skipping this execution." . PHP_EOL;
        if ($lockFile) fclose($lockFile);
        exit(0); 
    }
    
    // Pastikan file dikosongkan sebelum diisi PID baru
    ftruncate($lockFile, 0);
    
    // Konversi ke string agar tidak error di PHP 8+
    $pid = (string) getmypid();
    fwrite($lockFile, $pid);
    
    // Pastikan data tertulis ke disk
    fflush($lockFile);
}

// $worker = new EventWorker($db, $redisConfig);
$worker = new EventWorker();

// // Set nama tabel kustom jika perlu
// $worker->setTable('event_queue_b');

// // Contoh: Simpan data selama 7 hari dan bersihkan setiap 12 jam
// // (Opsional, jika Anda menambahkan properti ini di class)
// $worker->retentionDays = 7;

// // Waktu eksekusi x detik per listener default 5 detik (bisa disesuaikan)
// $worker->timePerListener = 3;

// // Timeout dasar minimal default 30 detik (Naikan/Turunkan sesuai kebutuhan)
// $worker->baseTimeout = 10;

$worker->run($once);

// Mencegah Zombie Process
if($once && $lockFile) {
    flock($lockFile, LOCK_UN);
    fclose($lockFile);

    // Opsional: Hapus file jika ingin folder tetap bersih
    if (file_exists($lockPath)) unlink($lockPath);
    
    echo "[*] Lock released and worker finished." . PHP_EOL;
}
