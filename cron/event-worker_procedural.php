<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/event-worker_procedural.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use Predis\Client as PredisClient;
use App\Core\Database\Connection;
use App\Core\Events\ListenerRegistry;
// use App\Events\Listeners\UserListener; // Contoh listener Anda

$pdo = null;
// $pdo = Connection::custom($driver = '', $dbname = '', $host = '', $port = '', $username = '', $password = '', $options = []);
// Set PDO connection or use default connection
$db = $pdo ?: Connection::make();
$tableDb = 'event_queue';
$redis = new PredisClient([
            'scheme'   => 'tcp',
            'host' => config('redis.default.host'),
            'port' => config('redis.default.port'),
            'database' => config('redis.default.database'),
            'password' => config('redis.default.password') ?: null,
            'timeout'  => 1.0, // Connection timeout
        ]);

// 1. Registrasi Listener (Bisa ditaruh di file terpisah)
ListenerRegistry::listen('user.registered', function($data) {
    echo "Sending welcome email to: " . $data['email'] . PHP_EOL;
});

ListenerRegistry::listen('crawler.finished', function($data) {
    echo "Crawler finished for URL: " . $data['url'] . PHP_EOL;
});

// 2. Loop Worker
echo "[*] Event Worker started. Waiting for events..." . PHP_EOL;

while (true) {
    $itemProcessed = false;

    // --- PRIORITAS 1: REDIS (PREDIS) ---
    try {
        // RPOP mengambil data dari ujung antrean
        $raw = $redis->rpop('event_queue_list');
        if ($raw) {
            $event = json_decode($raw, true);
            ListenerRegistry::executeListener(
                $event['event_name'], 
                $event['payload'], 
                $event['user_id'] ?? null
            );
            $itemProcessed = true;
        }
    } catch (Exception $e) {
        $messageErr = "Predis Worker Error: " . $e->getMessage();
        if (config('app.debug')) {            
            \write_log([
                'message' => $messageErr 
            ], 'cron/worker-event.php', 'error', 'error_EventLib.log');
        }
        echo "[!] Error using redis: " . $e->getMessage() . PHP_EOL;
        echo "[-] Try Using MySQL as Fallback." . PHP_EOL;
    }
    
    if (!$itemProcessed) {
        // Ambil 1 data pending
        $stmt = $db->query("SELECT * FROM {$tableDb} WHERE status = 'pending' LIMIT 1 FOR UPDATE");
        $event = $stmt->fetch();

        if($event) {
            // Tandai sedang diproses
            $db->prepare("UPDATE {$tableDb} SET status = 'processing' WHERE id = ?")->execute([$event['id']]);

            try {
                $payload = json_decode($event['payload'], true);
        
                // Membungkus metadata agar listener mendapatkan konteks lengkap
                $payload['_metadata'] = [
                    'event_id'     => $event['id'],
                    'user_id'      => $event['user_id'] ?? null, // Default ke null jika tidak diset
                    'event_name'   => $event['event_name'],
                    'triggered_at' => $event['created_at']
                ];

                $listeners = ListenerRegistry::getListeners($event['event_name']);
                foreach ($listeners as $callback) {
                    call_user_func($callback, $payload);
                }

                // Tandai selesai
                $db->prepare("UPDATE {$tableDb} SET status = 'completed' WHERE id = ?")->execute([$event['id']]);
                echo "[✔] Event {$event['event_name']} ID:{$event['id']} processed.\n";
                $itemProcessed = true;
            } catch (Exception $e) {
                $db->prepare("UPDATE {$tableDb} SET status = 'failed' WHERE id = ?")->execute([$event['id']]);
                echo "[!] Error processing event ID:{$event['id']}: " . $e->getMessage() . PHP_EOL;
            }
        }
    }

    usleep(500000); // Istirahat 0.5 detik agar tidak membebani CPU
}