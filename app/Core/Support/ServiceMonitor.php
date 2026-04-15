<?php

/**
 * ServiceMonitor class 
 * ServiceMonitor: real-time monitoring services status
 * @author LutviP19 <lutvip19@gmail.com>
 */

 
namespace App\Core\Support;

use App\Core\Database\Connection;

class ServiceMonitor {
    public static function checkAll() {
        $services = [
            ['name' => 'Database', 'icon' => 'db', 'check' => fn() => self::checkPDO()],
            ['name' => 'Redis', 'icon' => 'redis', 'check' => fn() => self::checkSocket('127.0.0.1', 6379)],
            ['name' => 'RabbitMQ', 'icon' => 'mq', 'check' => fn() => self::checkSocket('127.0.0.1', 5672)],
            ['name' => 'Meilisearch', 'icon' => 'search', 'check' => fn() => self::checkSocket('127.0.0.1', 7700)],
            ['name' => 'Mailpit', 'icon' => 'mail', 'check' => fn() => self::checkSocket('127.0.0.1', 8025)],
            ['name' => 'AI Agent (Ollama)', 'icon' => 'ai', 'check' => fn() => self::checkSocket('127.0.0.1', 11434)],
        ];

        foreach ($services as $service) {
            $isUp = $service['check']();
            self::renderCard($service['name'], $isUp);
        }
    }

    private static function checkSocket($host, $port) {
        $connection = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }

    private static function checkPDO() {
        try {
            $conn = Connection::make();
            return (bool) $conn->query('SELECT 1');
        } catch (\Exception $e) { return false; }
    }

    private static function renderCard($name, $isUp) {
        $color = $isUp ? 'emerald' : 'rose';
        $statusText = $isUp ? 'Online' : 'Offline';
        $pulse = $isUp ? 'animate-pulse' : '';
        ?>
        <div class="bg-gray-800 border border-gray-700 p-4 rounded-xl shadow-sm flex items-center justify-between transition-all duration-300">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-3 h-3 bg-<?= $color ?>-500 rounded-full <?= $pulse ?>"></div>
                    <div class="absolute inset-0 w-3 h-3 bg-<?= $color ?>-400 rounded-full blur-[2px]"></div>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-200"><?= $name ?></h4>
                    <p class="text-[10px] text-gray-500 uppercase tracking-tighter"><?= $statusText ?></p>
                </div>
            </div>
            <div class="text-xs font-mono <?= $isUp ? 'text-emerald-400' : 'text-rose-400' ?>">
                <?= $isUp ? 'UP' : 'DOWN' ?>
            </div>
        </div>
        <?php
    }
}