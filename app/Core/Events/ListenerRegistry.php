<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Events;

class ListenerRegistry {
    private static $listeners = [];

    /**
     * Daftarkan fungsi listener untuk event tertentu
     */
    public static function listen(string $eventName, callable $callback) {
        self::$listeners[$eventName][] = $callback;
    }

    public static function getListeners(string $eventName) {
        return self::$listeners[$eventName] ?? [];
    }

    /**
     * Mengeksekusi semua listener yang terdaftar untuk sebuah event
     * @param string $eventName Nama event (misal: 'crawler.finished')
     * @param array $payload Data utama dari event
     * @param int|null $userId ID User terkait
     */
    public static function executeListener(string $eventName, array $payload, ?int $userId = null)
    {
        // Ambil daftar listener dari Registry yang sudah kita buat sebelumnya
        $listeners = self::getListeners($eventName);
        // dd($listeners, true);

        if (empty($listeners)) {
            echo "[!] No listeners registered for event: {$eventName}\n";
            return;
        }

        // Siapkan metadata tambahan untuk dikirim ke listener
        $payload['_metadata'] = [
            'event_name'   => $eventName,
            'user_id'      => $userId ?? null, // Default null jika tidak ada
            'executed_at'  => date('Y-m-d H:i:s'),
        ];

        foreach ($listeners as $callback) {
            try {
                // Panggil fungsi listener (bisa berupa Closure, Function, atau [Class, Method])
                // call_user_func($callback, $payload);

                // CEK VALIDASI CALLBACK
                if (is_callable($callback)) {
                    // Ini akan menjalankan:
                    // 1. function($data) { ... } (Closure)
                    // 2. [$instance, 'handle'] (Class Method)
                    call_user_func($callback, $payload);
                } else {
                    dd("Listener for {$eventName} is not callable.");
                }
                
                echo "[✔] Success: Listener for '{$eventName}' executed.\n";
            } catch (\Exception $e) {
                // Catat error jika salah satu listener gagal agar tidak menghentikan worker
                $messageErr = "Listener Error ({$eventName}): " . $e->getMessage();
                if (config('app.debug')) {
                    \write_log([
                        'message' => $messageErr 
                    ], 'App\Core\Events\ListenerRegistry.executeListener', 'error', 'error_EventLib.log');
                }
                echo "[✘] Failed: Error in listener for '{$eventName}': " . $e->getMessage() . "\n";
            }
        }
    }
}