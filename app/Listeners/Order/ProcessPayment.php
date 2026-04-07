<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

// file: app/Listeners/Order/ProcessPayment.php

namespace App\Listeners\Order;

use App\Core\Events\ListenerInterface;

class ProcessPayment implements ListenerInterface
{
    /**
     * Nama event yang ingin didengarkan oleh class ini.
     * Variabel ini akan dibaca oleh Reflection di App::boot().
     */
    public $event = 'order.payment';
    public $priority = 7; // Dijalankan setelah SendToRabbit

    /**
     * Logika utama saat event dipicu.
     */
    public function handle(array $data)
    {
        $amount = $data['amount'] ?? 0;
        $orderId = $data['order_id'] ?? 'Unknown';

        echo "[PaymentGate] Memproses pembayaran ID: {$orderId} sebesar Rp" . number_format($amount) . PHP_EOL;

        // Simulasi logika bisnis:
        // 1. Hubungkan ke API Bank
        // 2. Update status order di DB
    }
}