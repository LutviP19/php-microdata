<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Listeners;

use App\Core\Events\ListenerInterface;

class LogOrder implements ListenerInterface
{
    public $event = 'order.created';
    public $priority = 1; // Dijalankan pertama kali

    public function handle(array $data) {
        echo "[1] Logging order ke database lokal..." . PHP_EOL;
    }
}