<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: worker-event.php

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ );
}

/**
 * Require Worker Bootstrap File.
 */
require_once BASEPATH . '/cron/bootstrap.php';

use App\Core\Support\App;
use App\Core\Events\EventWorker;

//  KONFIGURASI
$db = null;
$redisConfig = [
    'host'     => config('redis.default.host'),
    'port'     => config('redis.default.port'),
    'database' => config('redis.default.database'),
    'password' => config('redis.default.password'),
];


// Scan semua listener secara otomatis
App::bootListeners();

// Setelah ini, ListenerRegistry sudah penuh dengan callback dari folder App/Listeners
try {
    $worker = new EventWorker($db, $redisConfig);
    
    echo "[*] Untuk keluar tekan CTRL+C" . PHP_EOL;

    // // Set nama tabel kustom jika perlu
    // $worker->setTable('event_queue_b');

    // // Contoh: Simpan data selama 7 hari dan bersihkan setiap 12 jam
    // // (Opsional, jika Anda menambahkan properti ini di class)
    // $worker->retentionDays = 7;

    // Mulai mendengarkan antrean (Redis/MySQL Fallback)
    $worker->run();

} catch (\Exception $e) {
    echo "Fatal Worker Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}