<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: worker-event.php

if (!defined('BASEPATH')) {
    // define('BASEPATH', __DIR__ );
    define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Require Worker Bootstrap File.
 */
require_once BASEPATH . 'cron/bootstrap.php';

use App\Core\Support\App;
use App\Core\Events\EventWorker;

//  KONFIGURASI
$db = null;
$redisConfig = null;
// $redisConfig = [
//     'host'     => config('redis.default.host'),
//     'port'     => config('redis.default.port'),
//     'database' => config('redis.default.database'),
//     'password' => config('redis.default.password'),
// ];


// Scan semua listener secara otomatis
App::bootListeners();

// Inisialisasi variabel di luar try agar bisa diakses di catch/finally
$lockFile = null;
$once = true; // Set true jika ingin mode Cron/Once
$lockPath = BASEPATH . 'worker.lock';

if($once && $is_cron) {
    $start = microtime(true);
    echo "[" . date('Y-m-d H:i:s') . "] Run Worker Events..." . PHP_EOL;
}

// Setelah ini, ListenerRegistry sudah penuh dengan callback dari folder App/Listeners
try {
    // --- Mencegah Zombie Process (Locking) ---
    if ($once) {
        $lockFile = fopen($lockPath, 'c');
        
        if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
            echo "[!] Worker is already running. Skipping this execution." . PHP_EOL;
            if ($lockFile) fclose($lockFile);
            exit(0); // Keluar dengan tenang karena memang sudah ada yang jalan
        }
        
        // Tulis PID (Process ID) ke dalam lock file untuk kebutuhan debugging
        ftruncate($lockFile, 0);
        fwrite($lockFile, (string) getmypid());
    }

    // $worker = new EventWorker($db, $redisConfig);
    $worker = new EventWorker();
    
    echo "[*] Event System Started. Mode: " . ($once ? "Once" : "Daemon") . PHP_EOL;

    if(!$once) {
        echo "[*] Untuk keluar tekan CTRL+C" . PHP_EOL;
    }

    // // Set nama tabel kustom jika perlu
    // $worker->setTable('event_queue_b');

    // // Contoh: Simpan data selama 7 hari dan bersihkan setiap 12 jam
    // // (Opsional, jika Anda menambahkan properti ini di class)
    // $worker->retentionDays = 7;

    // // Waktu eksekusi x detik per listener default 5 detik (bisa disesuaikan)
    // $worker->timePerListener = 3;

    // // Timeout dasar minimal default 30 detik (Naikan/Turunkan sesuai kebutuhan)
    // $worker->baseTimeout = 10;

    // Mulai mendengarkan antrean (Redis/MySQL Fallback)
    $worker->run($once);

} catch (\Throwable $e) { // Gunakan Throwable agar Fatal Error juga tertangkap
    echo "Fatal Worker Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
    exit(1);
} finally {
    // --- Handler Lock di Akhir (Selalu dijalankan) ---
    if ($once && $lockFile) {
        flock($lockFile, LOCK_UN);
        fclose($lockFile);
        
        // Opsional: Hapus file jika ingin folder tetap bersih
        if (file_exists($lockPath)) unlink($lockPath);
        
        echo "[*] Lock released and worker finished." . PHP_EOL;
    }

    if($once && $is_cron) {
        $end = microtime(true);
        $time = $end - $start;
        echo "Worker Events Processing took: {$time} seconds" . PHP_EOL;
        echo "End Worker Events..." . PHP_EOL;
    }

}