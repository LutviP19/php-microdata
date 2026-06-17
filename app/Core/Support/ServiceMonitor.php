<?php

/**
 * ServiceMonitor class
 * ServiceMonitor: real-time monitoring services status
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Support;

class ServiceMonitor
{
    public static function checkAll()
    {
        $services = [
            ["id" => "db", "name" => "MySQL Database", "host" => "127.0.0.1", "port" => 3306],
            ["id" => "redis", "name" => "Redis", "host" => "127.0.0.1", "port" => 6379],
            ["id" => "mq", "name" => "RabbitMQ", "host" => "127.0.0.1", "port" => 5672],
            ["id" => "mailpit", "name" => "Mailpit", "host" => "127.0.0.1", "port" => 8025],
            ["id" => "search", "name" => "Meilisearch", "host" => "127.0.0.1", "port" => 7700],
            ["id" => "ollama", "name" => "Ollama (AI)", "host" => "127.0.0.1", "port" => 11434],
        ];

        foreach ($services as $srv) {
            $start = microtime(true);
            $conn = @fsockopen($srv["host"], $srv["port"], $errno, $errstr, 0.5);
            $latency = round((microtime(true) - $start) * 1000, 2); // dalam ms

            if ($conn) {
                fclose($conn);
                $isUp = true;
                // Ambil info tambahan jika layanan online
                $extra = self::getExtraInfo($srv["id"]);
            } else {
                $isUp = false;
                $extra = ["mem" => "N/A", "version" => "OFFLINE"];
            }

            self::renderAdvancedCard($srv["name"], $isUp, $latency, $extra);
        }
    }

    private static function getExtraInfo($id)
    {
        switch ($id) {
            case "db":
                // Mengambil jumlah koneksi aktif MySQL
                $output = shell_exec("mysqladmin -u" . config("db.user") . " -p" . config("db.pass") . " status 2>&1");
                preg_match("/Threads: (\d+)/", (string) $output, $matches);
                return ["mem" => "Active Conn: " . ($matches[1] ?? "0"), "load" => "MySQLd"];

            case "redis":
                // Mengambil info memory via redis-cli
                $info = shell_exec("redis-cli info memory 2>&1");
                preg_match("/used_memory_human:(.*)/", (string) $info, $matches);
                return ["mem" => trim($matches[1] ?? "N/A"), "load" => "Redis-KV"];

            case "mq":
                // Mengambil info queue RabbitMQ (Butuh rabbitmq-diagnostics atau via Management API)
                $queues = shell_exec("rabbitmqctl list_queues name messages 2>&1 | wc -l");
                $count = (int) $queues > 0 ? (int) $queues - 1 : 0;
                return ["mem" => $count . " Queues", "load" => "AMQP"];

            case "mailpit":
                // Mailpit API untuk cek jumlah email tersimpan
                $ctx = stream_context_create(["http" => ["timeout" => 0.5]]);
                $api = @file_get_contents("http://127.0.0.1:8025/api/v1/info", false, $ctx);
                $data = $api ? json_decode($api, true) : null;
                $size = isset($data["DatabaseSize"]) ? round($data["DatabaseSize"] / 1024 / 1024, 2) . "MB" : "N/A";
                return ["mem" => $size, "load" => "SMTP"];

            case "search":
                // Meilisearch Health/Stats
                $ctx = stream_context_create(["http" => ["timeout" => 0.5]]);
                $stats = @file_get_contents("http://127.0.0.1:7700/stats", false, $ctx);
                $data = $stats ? json_decode($stats, true) : null;
                $dbSize = isset($data["databaseSize"]) ? round($data["databaseSize"] / 1024 / 1024, 2) . "MB" : "N/A";
                return ["mem" => $dbSize, "load" => "Indexing"];

            case "ollama":
                // Mengambil model yang sedang aktif dan penggunaan memori
                $ctx = stream_context_create(["http" => ["timeout" => 0.5]]);
                $ps = @file_get_contents("http://127.0.0.1:11434/api/ps", false, $ctx);
                $data = $ps ? json_decode($ps, true) : null;

                if (!empty($data["models"])) {
                    $m = $data["models"][0];
                    $mem = round($m["size_vram"] / 1024 / 1024 / 1024, 1) . "GB VRAM";
                    return ["mem" => $mem, "load" => $m["name"]];
                }
                return ["mem" => "0GB", "load" => "Idle"];

            default:
                return ["mem" => "---", "load" => "---"];
        }
    }

    private static function renderAdvancedCard($name, $isUp, $latency, $extra)
    {
        $color = $isUp ? "emerald" : "rose";
        $latencyColor = $latency > 100 ? "text-amber-400" : "text-emerald-400";
        $elementView = BASEPATH . "/views/elements/monitoring-service-status.php";
        if (file_exists($elementView)) {
            include $elementView;
        } else {
            echo "View element not found: " . htmlspecialchars($elementView);
        }
    }
}
