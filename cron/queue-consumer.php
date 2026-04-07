<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/queue-consumer.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use App\Core\Support\RabbitFFI;

// WAJIB: Matikan limit waktu untuk Worker
set_time_limit(0);
ini_set('memory_limit', '256M');

$mq = new RabbitFFI();
$queueName = 'crawler_queue';

echo "[*] Menunggu pesan di [$queueName]. Untuk keluar tekan CTRL+C\n";

while (true) {
    try {
        // Fungsi ini akan 'blocking' (berhenti) di sini sampai ada pesan masuk
        $message = $mq->receive($queueName);

        if ($message) {
            $data = json_decode($message, true);
            
            echo "\n[✔] Memproses Task ID: " . ($data['task_id'] ?? 'N/A') . "\n";
            echo "    URL: " . ($data['url_to_crawl'] ?? '-') . "\n";

            // --- JALANKAN CRAWLER ANDA DI SINI ---
            // Contoh: $crawler->run($data['url_to_crawl']);
            
            echo "[+] Selesai pada: " . date('H:i:s') . "\n";
        }

    } catch (Exception $e) {
        echo "[!] Error: " . $e->getMessage() . "\n";
        sleep(5); // Tunggu sebentar sebelum mencoba koneksi ulang
    }

    // Hindari CPU Usage 100% jika terjadi loop kosong
    usleep(10000); 
}
