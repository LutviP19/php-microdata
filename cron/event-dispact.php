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

function generateDummyOrderData(): array
{
    $catalog = [
        'Buku PHP-FFI'               => 125000,
        'Kopi Hitam'                 => 25000,
        'Mechanical Keycap'          => 180000,
        'USB-C Hub'                  => 240000,
        'Mousepad Deskmat'           => 95000,
        'Buku PostgreSQL Internals'  => 165000,
    ];

    $itemCount = random_int(1, 3);
    $pickedKeys = array_rand($catalog, $itemCount);
    $pickedKeys = is_array($pickedKeys) ? $pickedKeys : [$pickedKeys];

    $items = [];
    $amount = 0;

    foreach ($pickedKeys as $itemName) {
        $qty = random_int(1, 2);
        $price = $catalog[$itemName];

        $amount += $price * $qty;
        $items[] = $qty > 1 ? "{$itemName} (x{$qty})" : $itemName;
    }

    return [
        'task_id'  => uniqid('task_', true),
        'order_id' => 'ORD-' . random_int(1000, 9999),
        'amount'   => $amount,
        'items'    => $items,
    ];
}

// Data yang akan dikirim
// $orderData = [
//     'task_id' => uniqid(),
//     'order_id' => 'ORD-9921',
//     'amount'   => 150000,
//     'items'    => ['Buku PHP-FFI', 'Kopi Hitam']
// ];
$orderData = generateDummyOrderData();

// Picu event
$dispatcher->dispatch('order.created', $orderData, $current_user_id);

echo "Event order.created telah dipicu dan masuk ke antrean database/redis." . PHP_EOL;
die;
