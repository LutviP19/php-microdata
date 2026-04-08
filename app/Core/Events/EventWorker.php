<?php
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 * 
 *  EventWorker yang mencoba menyimpan ke Redis terlebih dahulu, 
 *  Class ini bertugas menangani koneksi dan perulangan antrean (looping).
 */


namespace App\Core\Events;

use PDO;
use Exception;
use Predis\Client as PredisClient;
use App\Core\Database\Connection;
use App\Core\Events\ListenerRegistry;

class EventWorker
{
    protected $db;
    protected $redis = null;
    protected $table = 'event_queue'; // Tabel MySQL
    protected $queueKey = 'event_queue_list'; // Redis KEY
    protected $sleepTime = 500000; // 0.5 detik
    protected $lastCleanupTime;
    protected $cleanupInterval = 3600; // Jalankan cleaner setiap 1 jam (3600 detik)
    protected $retentionDays = 3;      // Simpan history selama 3 hari
    protected $logToDatabase = true;   // riwayat (log) di MySQL
    protected $logBuffer = [];
    protected $batchSize = 50; // Kirim ke DB setiap 50 log
    protected $lastBatchFlush;
    protected $timePerListener = 5; // Berikan waktu eksekusi 5 detik per listener (bisa disesuaikan)
    protected $baseTimeout = 30;    // Timeout dasar minimal

    public function __construct(PDO $db = null, array $redisConfig = [])
    {
        // Set PDO connection or use default connection
        $this->db = $db ?: Connection::make();
        // Tanpa ERRMODE_EXCEPTION, PDO tidak akan melempar Exception saat query gagal
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->initRedis($redisConfig);

        $this->lastCleanupTime = time(); // Set waktu awal saat worker jalan
        $this->lastBatchFlush = time();
    }

    protected function initRedis(array $config = [])
    {
        // Ambil driver dari env/config, default ke file/mysql jika bukan redis
        if (config('app.queue_driver') !== 'redis') {
            $this->redis = null;
            return;
        }

        try {
            if(!empty($config)) {
                $this->redis = new PredisClient([
                    // 'scheme'   => 'tcp',
                    'host'     => $config['host'],
                    'port'     => $config['port'],
                    'database' => $config['database'],
                    'password' => $config['password'] ?: null,
                    // 'timeout'  => 1.0,
                ]);
            } else {
                $this->redis = new PredisClient([
                    // 'scheme'   => 'tcp',
                    'host' => config('redis.default.host'),
                    'port' => config('redis.default.port'),
                    'database' => config('redis.default.database'),
                    'password' => config('redis.default.password') ?: null,
                    // 'timeout'  => 1.0, // Connection timeout
                ]);
            }
            

            $this->redis->ping();
        } catch (Exception $e) {
            $this->logError("Predis Connection Failed: " . $e->getMessage());
            $this->redis = null; // Set null agar fallback ke DB jalan
        }
    }

    public function setTable(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Menghapus data lama dari tabel event_queue
     */
    public function cleanUp()
    {
        try {
            echo "[*] Cleaning up old events..." . PHP_EOL;

            $sql = "DELETE FROM {$this->table} 
                    WHERE status IN ('completed', 'failed') 
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->retentionDays]);
            
            $count = $stmt->rowCount();
            echo "[✔] Cleanup finished. {$count} rows deleted." . PHP_EOL;
            
            $this->lastCleanupTime = time();
        } catch (Exception $e) {
            $this->logError("Cleanup Error: " . $e->getMessage());
        }
    }

    public function run($once = false)
    {
        echo "[*] Event Worker started (" . ($once ? "Once Mode" : "Daemon Mode") . ")..." . PHP_EOL;

        // 1. Hitung total listeners untuk menentukan timeout yang aman
        // Kita ambil dari Registry yang sudah di-load oleh App::boot()
        $allListeners = \App\Core\Events\ListenerRegistry::getAllListeners();
        $totalListenerCount = 0;
        foreach ($allListeners as $eventGroup) {
            $totalListenerCount += count($eventGroup);
        }

        // Hitung timeout: (jumlah listener * estimasi waktu) + buffer dasar
        $dynamicTimeout = ($totalListenerCount * $this->timePerListener) + $this->baseTimeout;

        // Set limit waktu eksekusi PHP (0 jika daemon agar tidak mati, atau X detik jika once)
        if ($once) {
            set_time_limit($dynamicTimeout);
            echo "[*] Mode: Once | Dynamic Timeout: {$dynamicTimeout}s" . PHP_EOL;
        } else {
            set_time_limit(0); // Daemon harus berjalan selamanya
            echo "[*] Mode: Daemon | Timeout: Unlimited" . PHP_EOL;
        }

        // Jalankan cleanup pertama kali saat start
        $this->cleanUp();

        do {
            $itemProcessed = false;

            // Reset timeout setiap kali loop mulai agar tidak timeout di tengah jalan (untuk Daemon)
            if (!$once) {
                set_time_limit($this->baseTimeout); 
            }

            // --- MAINTENANCE LOGIC ---
            // Cek Cleanup berkala (hanya relevan di Daemon Mode)
            if (!$once && (time() - $this->lastCleanupTime) > $this->cleanupInterval) {
                $this->cleanUp();
            }

            // Cek Force Flush Batch Log
            if (!empty($this->logBuffer) && (time() - $this->lastBatchFlush) > 30) {
                $this->flushLogs();
            }

            // --- PROCESSING LOGIC ---
            // 1. Coba Redis
            if (config('app.queue_driver') === 'redis' && $this->redis) {
                $itemProcessed = $this->processRedis();
            }

            // 2. Fallback ke MySQL
            if (!$itemProcessed) {
                $itemProcessed = $this->processDatabase();
            }

            // --- EXIT & SLEEP LOGIC ---
            if ($once) {
                // Jika dalam mode 'once', kita berhenti jika tidak ada item lagi yang diproses
                if (!$itemProcessed) {
                    echo "[*] No more events to process. Shutting down..." . PHP_EOL;
                    break; 
                }
            } else {
                // Jika dalam mode 'daemon', tidur sebentar jika tidak ada kerjaan
                if (!$itemProcessed) {
                    usleep($this->sleepTime);
                }
            }

        } while (!$once || $itemProcessed);

        // TERAKHIR: Pastikan semua log yang tersisa di buffer terkirim sebelum script mati
        if (!empty($this->logBuffer)) {
            echo "[B] Finalizing logs before exit..." . PHP_EOL;
            $this->flushLogs();
        }
    }

    protected function processRedis()
    {
        try {
            $raw = $this->redis->rpop($this->queueKey);
            if ($raw) {
                $event = json_decode($raw, true);
                $startTime = microtime(true); // Mulai hitung waktu eksekusi

                try {
                    ListenerRegistry::executeListener(
                        $event['event_name'],
                        $event['payload'],
                        $event['user_id'] ?? null
                    );

                    if ($this->logToDatabase) {
                        $executionTime = round(microtime(true) - $startTime, 4);
                        $this->addToBatch($event, 'completed', $executionTime);
                    }
                    return true;
                } catch (Exception $e) {
                    if ($this->logToDatabase) {
                        $executionTime = round(microtime(true) - $startTime, 4);
                        $this->addToBatch($event, 'failed', $executionTime);
                    }
                    throw $e; 
                }
            }
        } catch (Exception $e) {
            $this->logError("Redis Process Error: " . $e->getMessage());
        }
        return false;
    }

    protected function processDatabase()
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // Gunakan prepare untuk keamanan
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'pending' LIMIT 1 FOR UPDATE");
            $stmt->execute();
            
            // Mengambil data sebagai Object (stdClass)
            $event = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$event) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                return false;
            }

            // Akses menggunakan properti object ->bukan ['id']
            $this->db->prepare("UPDATE {$this->table} SET status = 'processing' WHERE id = ?")
                    ->execute([$event->id]);
            
            $this->db->commit();

            try {
                // Parsing payload dari object
                $payloadData = json_decode($event->payload, true);

                ListenerRegistry::executeListener(
                    $event->event_name,
                    $payloadData,
                    $event->user_id ?? null
                );

                $this->db->prepare("UPDATE {$this->table} SET status = 'completed' WHERE id = ?")
                        ->execute([$event->id]);

                echo "[✔] Event {$event->event_name} ID:{$event->id} processed." . PHP_EOL;
                return true;

            } catch (Exception $e) {
                $this->db->prepare("UPDATE {$this->table} SET status = 'failed' WHERE id = ?")
                        ->execute([$event->id]);
                throw $e;
            }

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logError("DB Worker Error: " . $e->getMessage());
        }

        return false;
    }

   /**
     * Menambahkan log ke dalam buffer sementara
     */
    protected function addToBatch(array $event, string $status, float $executionTime)
    {
        $this->logBuffer[] = [
            'user_id'    => $event['user_id'] ?? null,
            'event_name' => $event['event_name'] ?? 'unknown',
            'payload'    => is_array($event['payload']) ? json_encode($event['payload']) : $event['payload'],
            'status'     => $status,
            'created_at' => $event['created_at'] ?? date('Y-m-d H:i:s'),
            'exec_time'  => $executionTime // Tambahan info performa
        ];

        // Jika buffer penuh, kirim ke database
        if (count($this->logBuffer) >= $this->batchSize) {
            $this->flushLogs();
        }
    }

    /**
     * Mengirim semua log di buffer ke MySQL dalam satu query (Multi-Insert)
     */
    public function flushLogs()
    {
        if (empty($this->logBuffer)) return;

        try {
            $rowCount = count($this->logBuffer);
            
            // Membangun query Multi-Insert: INSERT INTO table (...) VALUES (...), (...), (...)
            $placeholders = [];
            $values = [];

            foreach ($this->logBuffer as $log) {
                $placeholders[] = "(?, ?, ?, ?, ?, ?)";
                $values[] = $log['user_id'];
                $values[] = $log['event_name'];
                $values[] = $log['payload'];
                $values[] = $log['status'];
                $values[] = $log['created_at'];
                $values[] = $log['exec_time'];
            }

            $sql = "INSERT INTO {$this->table} 
                    (user_id, event_name, payload, status, created_at, execution_time) 
                    VALUES " . implode(', ', $placeholders);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            // Kosongkan buffer setelah sukses
            $this->logBuffer = [];
            $this->lastBatchFlush = time();
            
            echo "[B] Batch log saved ({$rowCount} rows)." . PHP_EOL;
        } catch (Exception $e) {
            $this->logError("Batch Flush Failed: " . $e->getMessage());
        }
    }

    protected function logError($message)
    {
        if (config('app.debug')) {
            \write_log(['message' => $message], 'EventWorker', 'error', 'error_EventLib.log');
        }
        echo "[!] " . $message . PHP_EOL;
    }
}