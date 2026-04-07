<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Listeners;


use App\Core\Support\RabbitFFI;
use App\Core\Events\ListenerInterface;

class SendToRabbit implements ListenerInterface
{
    public $event = 'order.notif';
    public $priority = 5; // Dijalankan setelah log

    public function handle(array $data) {

        try {
            // 1. Inisialisasi Library RabbitMQ FFI
            $mq = new RabbitFFI();
            
            // 2. Ambil data asli dari payload
            // Payload di sini sudah termasuk _metadata dari executeListener
            $payloadToMQ = [
                'task'     => 'process_payment',
                'details'  => $data, // Ini berisi order_id, amount, dll
                'priority' => 'high'
            ];

            // 3. Kirim ke antrean RabbitMQ
            $mq->send('payment_queue', $payloadToMQ);

            echo "[2] Meneruskan data ke RabbitMQ via FFI..." . PHP_EOL;
        } catch (\Exception $e) {
            // Jika RabbitMQ mati, biarkan EventWorker (MySQL/Redis) mencatat errornya
            echo "[Worker] Gagal meneruskan ke RabbitMQ: " . $e->getMessage() . PHP_EOL;
            throw $e; // Throw agar status di DB berubah jadi 'failed'
        }
    }
}