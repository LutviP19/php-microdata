<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 * 
 *  Event Dispatcher yang mencoba menyimpan ke Redis terlebih dahulu, 
 *  namun otomatis beralih ke MySQL jika koneksi Redis terputus atau bermasalah.
 */

namespace App\Core\Events;

use PDO;
use Exception;
use PDOException;
use App\Core\Database\Connection;
use Predis\Client as PredisClient; // Gunakan Predis

class EventDispatcher {
    private $db;
    private $dbtable;
    private $redis = null;
    private $useRedis = false;

    public function __construct(?PDO $db = null) {
        
        // Set PDO connection or use default connection
        $this->db = $db ?? Connection::make();
        
        // Set Default Table
        $this->dbtable = 'event_queue';

        $this->initPredis();
    }
    
    /**
     * initPredis
     *
     * @return bool $this->useRedis
     */
    private function initPredis() {
        // Ambil driver dari env/config, default ke file/mysql jika bukan redis
        if (config('app.queue_driver') !== 'redis') {
            $this->useRedis = false;
            return;
        }

        try {
            // Konfigurasi Predis
            $this->redis = new PredisClient([
                'host' => config('redis.default.host'),
                'port' => config('redis.default.port'),
                'database' => config('redis.default.database'),
                'password' => config('redis.default.password') ?: null,
                // 'timeout'  => 1.0, // Connection timeout
            ]);

            // Cek koneksi dengan ping
            $this->redis->ping();
            $this->useRedis = true;
        } catch (Exception $e) {
            $this->redis = null;
            $this->useRedis = false;
            // Jika Redis gagal, otomatis fallback ke MySQL tanpa memutus script
            $messageErr = "Predis Connection Failed: " . $e->getMessage();
            if (config('app.debug')) {
                \write_log([
                    'message' => $messageErr 
                ], 'App\Core\Events\EventDispatcher.initPredis', 'error', 'error_EventLib.log');
            }
        }
    }

    /**
     * menyimpannya ke database mysql queue
     * @param string $eventName Nama event
     * @param array $data Data payload
     * @param int|null $userId ID User yang memicu (opsional)
     */
    private function dispatchToMysql(string $eventName, array $data, ?int $userId = null) {
        $sql = "INSERT INTO {$this->dbtable} (user_id, event_name, payload, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);
        
        $payload = json_encode($data);
        
        try {
            $stmt->execute([$userId, $eventName, $payload]);
            return "queued_via_mysql_" . $this->db->lastInsertId();
        } catch (Exception $e) {
            $messageErr = "Event Dispatch Error: " . $e->getMessage();
            if (config('app.debug')) {
                \write_log([
                    'message' => $messageErr 
                ], 'App\Core\Events\EventDispatcher.dispatchToMysql', 'error', 'error_EventLib.log');
            }
            throw new Exception("Gagal menyimpan event ke database.");
        }
    }

    /**
     * Memicu event dan menyimpannya ke database queue
     * @param string $eventName Nama event
     * @param array $data Data payload
     * @param int|null $userId ID User yang memicu (opsional)
     */
    public function dispatch(string $eventName, array $data, ?int $userId = null) {
        $payload = [
            'event_name' => $eventName,
            'payload'    => $data,
            'user_id'    => $userId ?? null, // Default null jika tidak diset
            'created_at' => date('Y-m-d H:i:s')
        ];

        $jsonPayload = json_encode($payload);

        // 1. Coba kirim ke Redis
        if ($this->useRedis && $this->redis) {
            try {
                $this->redis->lpush('event_queue_list', $jsonPayload);
                return "queued_via_redis";
            } catch (Exception $e) {
                // Jika saat push gagal, lanjut ke fallback MySQL
                $messageErr = "Predis Push Error: " . $e->getMessage();
                if (config('app.debug')) {
                    \write_log([
                        'message' => $messageErr 
                    ], 'App\Core\Events\EventDispatcher.dispatch', 'error', 'error_EventLib.log');
                }
            }
        }

        // 2. Fallback ke MySQL
        return $this->dispatchToMysql($eventName, $data, $userId);

        // Contoh Penggunaan
        // // Dengan User ID (Misal saat User Login)
        // $dispatcher->dispatch('auth.login', ['ip' => '192.168.1.1'], $user->id);
        // // Tanpa User ID (Misal System Task / Crawler)
        // $dispatcher->dispatch('crawler.start', ['url' => 'https://example.com']);
    }
}