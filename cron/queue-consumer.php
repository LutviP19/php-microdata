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
$queueName = 'payment_queue'; // custom test event

echo "[*] Menunggu pesan di [$queueName]. Untuk keluar tekan CTRL+C\n";

while (true) {
    try {
        // Fungsi ini akan 'blocking' (berhenti) di sini sampai ada pesan masuk
        $message = $mq->receive($queueName);

        if ($message) {
            $data = json_decode($message, true);
            
            if($queueName === 'crawler_queue') {
                echo "\n[✔] Memproses Task ID: " . ($data['task_id'] ?? 'N/A') . PHP_EOL;
                echo "    URL: " . ($data['url_to_crawl'] ?? '-') . PHP_EOL;
                echo "    CREATED: " . ($data['created_at'] ?? '-') . PHP_EOL;
            } else {
                echo json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
            }
            

            // --- JALANKAN CRAWLER ANDA DI SINI ---
            // Contoh: $crawler->run($data['url_to_crawl']);
            
            echo "[+] Selesai pada: " . date('H:i:s') . PHP_EOL;
        }

    } catch (Exception $e) {
        echo "[!] Error: " . $e->getMessage() . PHP_EOL;
        sleep(5); // Tunggu sebentar sebelum mencoba koneksi ulang
    }

    // Hindari CPU Usage 100% jika terjadi loop kosong
    usleep(10000); 
}
