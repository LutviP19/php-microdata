<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/event-listener.php

/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use App\Core\Support\App;
use App\Core\Events\EventWorker;
use App\Core\Events\ListenerRegistry;
use App\Core\Support\RabbitFFI;
use App\Core\Database\Connection;

/**
 * --------------------------------------------------------------------------
 * 1. KONFIGURASI & BOOTSTRAP
 * --------------------------------------------------------------------------
 */
$db = null;
$redisConfig = [
    'host'     => config('redis.default.host'),
    'port'     => config('redis.default.port'),
    'database' => config('redis.default.database'),
    'password' => config('redis.default.password'),
];

// Scan semua listener secara otomatis
App::bootListeners();

/**
 * Registrasi Listener: Kirim ke RabbitMQ
 */
ListenerRegistry::listen('order.notif', function($data) {
    try {
        // 1. Inisialisasi Library RabbitMQ FFI
        $mq = new RabbitFFI();
        
        // 2. Ambil data asli dari payload
        // Payload di sini sudah termasuk _metadata dari executeListener
        $payloadToMQ = [
            'task'     => 'process_payment',
            'details'  => $data, // Ini berisi order_id, amount, dll
            'priority' => 'high'
        ];

        // 3. Kirim ke antrean RabbitMQ
        $mq->send('payment_queue', $payloadToMQ);

        echo "[Worker] Berhasil meneruskan Event ke RabbitMQ Queue: payment_queue" . PHP_EOL;

    } catch (\Exception $e) {
        // Jika RabbitMQ mati, biarkan EventWorker (MySQL/Redis) mencatat errornya
        echo "[Worker] Gagal meneruskan ke RabbitMQ: " . $e->getMessage() . PHP_EOL;
        throw $e; // Throw agar status di DB berubah jadi 'failed'
    }
});

ListenerRegistry::listen('crawler.start', function($data) {
    echo "Crawler finished for URL: " . $data['url_to_crawl'] . PHP_EOL;
});

/**
 * --------------------------------------------------------------------------
 * 3. RUNNER (WORKER LOOP)
 * --------------------------------------------------------------------------
 * Bagian ini akan berjalan terus-menerus (While True)
 */
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