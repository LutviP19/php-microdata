<?php
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/event-listener.php

/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use App\Core\Support\App;
use App\Core\Events\EventDispatcher;
use App\Core\Database\Connection;

/**
 * --------------------------------------------------------------------------
 * STEP 1: BOOTING APLIKASI
 * --------------------------------------------------------------------------
 * Memanggil App::boot() akan menjalankan Auto-Scanning pada folder App/Listeners,
 * melakukan registrasi class, dan mengurutkan berdasarkan $priority (ASC).
 */
App::bootListeners();

/**
 * --------------------------------------------------------------------------
 * STEP 2: INISIALISASI DATABASE & DISPATCHER
 * --------------------------------------------------------------------------
 */
try {
    $dispatcher = new EventDispatcher();

    echo "--- [Event System Pemicu] ---" . PHP_EOL;

    $current_user_id = 12;
    $orderData = [
        'order_id' => 'TRX-' . time(),
        'amount'   => 250000,
        'items'    => ['SSD 512GB', 'Kabel HDMI'],
        'customer' => 'Budi Santoso'
    ];


    /**
     * ----------------------
     * SKENARIO 1: Order Baru
     * ----------------------
     */
    echo "[!] Memicu event: order.created" . PHP_EOL;
    
    // Dispatch event (Payload akan masuk ke Redis/MySQL Fallback)
    $dispatcher->dispatch('order.created', $orderData, $current_user_id);

    echo "[✔] Event berhasil dikirim ke antrean." . PHP_EOL;
    echo "-------------------------------------" . PHP_EOL;

    /**
     * -------------------------
     * SKENARIO 2: Order Payment
     * -------------------------
     */
    $orderData = [
        'order_id' => 'TRX-' . time(),
        'amount'   => 250000,
        'items'    => ['SSD 512GB', 'Kabel HDMI'],
        'customer' => 'Budi Santoso'
    ];

    echo "[!] Memicu event: order.payment" . PHP_EOL;
    
    // Dispatch event (Payload akan masuk ke Redis/MySQL Fallback)
    $dispatcher->dispatch('order.payment', $orderData, $current_user_id);

    echo "[✔] Event berhasil dikirim ke antrean." . PHP_EOL;
    echo "-------------------------------------" . PHP_EOL;

    /**
     * ----------------------------------------------------------------------
     * SKENARIO 3: SendToRabbit (kirim notif)
     * ----------------------------------------------------------------------
     */
    echo "[!] Memicu event: order.notif" . PHP_EOL;
    
    $dispatcher->dispatch('order.notif', $orderData, $current_user_id);

    echo "[✔] Event notif berhasil masuk antrean." . PHP_EOL;
    echo "-------------------------------------" . PHP_EOL;

} catch (\Exception $e) {
    echo "[ERROR] Gagal memicu event: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "[TIP] Jalankan 'php worker-event.php' di terminal lain untuk melihat proses event ini." . PHP_EOL;
