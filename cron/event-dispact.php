<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/event-dispact.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use App\Core\Events\EventDispatcher;
use App\Core\Database\Connection;

$db = Connection::make();
$dispatcher = new EventDispatcher($db);
$current_user_id = null;

// Data yang akan dikirim
$orderData = [
    'task_id' => uniqid(),
    'order_id' => 'ORD-9921',
    'amount'   => 150000,
    'items'    => ['Buku PHP-FFI', 'Kopi Hitam']
];

// Picu event
$dispatcher->dispatch('order.created', $orderData, $current_user_id);

echo "Event order.created telah dipicu dan masuk ke antrean database/redis." . PHP_EOL;
exit;
