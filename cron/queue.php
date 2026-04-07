<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/queue.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


use App\Core\Support\RabbitFFI;

$mq = new RabbitFFI();

$data = [
    'task_id' => uniqid(),
    'url_to_crawl' => 'https://lutvi-code.blogspot.com',
    'created_at' => date('Y-m-d H:i:s')
];
// dd($data, true);

try {
    // Set timeout PHP agar tidak hang jika FFI bermasalah
    set_time_limit(10); 
    
    $mq->send('crawler_queue', $data);
    echo "Pesan berhasil masuk antrean!" . PHP_EOL;
} catch (Exception $e) {
    echo "Gagal: " . $e->getMessage();
}
