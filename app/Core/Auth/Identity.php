<?php 

/**
 * Identity class 
 * Class Identity ini dirancang khusus untuk FrankenPHP Worker Mode.
 * @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Core\Auth;

class Identity {
    private static ?array $user = null;

    /**
     * Set data user dari hasil decode SodiumAuth
     */
    public static function set(array $data): void {
        self::$user = $data;
    }

    /**
     * Ambil data user atau field spesifik
     */
    public static function get(?string $key = null) {
        if ($key) {
            return self::$user[$key] ?? null;
        }
        return self::$user;
    }

    /**
     * Cek apakah user sudah terautentikasi
     */
    public static function check(): bool {
        return self::$user !== null;
    }

    /**
     * CRITICAL: Wajib dipanggil di akhir loop FrankenPHP
     * Untuk mengosongkan memori agar tidak bocor ke request selanjutnya
     */
    public static function clear(): void {
        self::$user = null;
    }
}