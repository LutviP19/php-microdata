<?php

/**
 * Identity class
 * This Identity class is specifically designed for FrankenPHP Worker Mode.
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Auth;

class Identity
{
    private static ?array $user = null;

    /**
     * Set data user dari hasil decode SodiumAuth
     * @param array<int,mixed> $data
     */
    public static function set(array $data): void
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        // Set session from cache
        $expTime = config("session.exptime", 7200);
        $cacheDataRedis = isset($_COOKIE[config("session.sess_name")]) ? (cacheContent('get', '*'. $_COOKIE[config("session.sess_name")] .'*', $expTime) ?? []) : [];
        $cacheData = [];
        foreach($cacheDataRedis as $key => $value) {
            $parts = explode(':', $key);
            $sessionValue = $parts[0] ?? null;
            if(!is_null($sessionValue) && $sessionValue === 'session') {
                $cacheData = $value;
            }
            continue;
        }
        $_SESSION = array_merge($_SESSION, $data, $cacheData);
        // $keysToExposed = ['uid', 'name', 'email', 'current_team_id', 'profile_photo_path', 'first_name', 'last_name', 'phone', 'default_url'];
        // $_SESSION = array_only($_SESSION, $keysToExposed);

        $keysToIgnored = ['email_verified_at', 'password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'client_token', 'remember_token', 'status', 'created_at', 'updated_at', 'deleted_at'];
        $_SESSION = array_except($_SESSION, $keysToIgnored);
        // dd($_SESSION, true);
        self::$user = $_SESSION;
    }

    /**
     * Ambil data user atau field spesifik
     * @param string|null $key Nama field yang ingin diambil (opsional)
     * @return mixed|null
     */
    public static function get(?string $key = null)
    {
        if ($key) {
            return self::$user[$key] ?? null;
        }
        return self::$user;
    }

    /**
     * Cek apakah user sudah terautentikasi
     * @return bool
     */
    public static function check(): bool
    {
        return self::$user !== null;
    }

    /**
     * CRITICAL: Wajib dipanggil di akhir loop FrankenPHP
     * Untuk mengosongkan memori agar tidak bocor ke request selanjutnya
     */
    public static function clear(): void
    {
        self::$user = null;
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }
}
