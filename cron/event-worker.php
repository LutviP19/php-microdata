<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/event-worker.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use App\Core\Events\EventWorker;
use App\Core\Events\ListenerRegistry;
use App\Core\Database\Connection;

// 1. Setup Database
$db = Connection::make();

// 2. Setup Config Redis
$redisConfig = [
    'host'     => config('redis.default.host'),
    'port'     => config('redis.default.port'),
    'database' => config('redis.default.database'),
    'password' => config('redis.default.password'),
];

// 3. Registrasi Listener (Bisa dipindah ke file app/Events/listeners.php)
ListenerRegistry::listen('user.registered', function($data) {
    echo "Sending welcome email to: " . $data['email'] . PHP_EOL;
});

ListenerRegistry::listen('crawler.finished', function($data) {
    echo "Crawler finished for URL: " . $data['url'] . PHP_EOL;
});

// 4. Jalankan Library
// $worker = new EventWorker($db, $redisConfig);
$worker = new EventWorker();
$worker->run();