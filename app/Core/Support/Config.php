<?php

/**
 * Config class
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Support;


/**
 * Config values from config directory.
 */
class Config
{
    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key     Key konfigurasi (contoh: 'redis.cache.host')
     * @param mixed  $default Nilai fallback jika key tidak ditemukan
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $config = App::get('config');

        // If the global config data is empty or not an array/ArrayAccess, immediately return default
        if (empty($config) || (!is_array($config) && !($config instanceof \ArrayAccess))) {
            return $default;
        }

        // Fast Track: If the key does not use dot notation, return the value directly
        if (strpos($key, '.') === false) {
            return isset($config[$key]) ? $config[$key] : $default;
        }

        // Dot Notation Path
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            // Check if the segment exists in the current array
            if (is_array($config) && isset($config[$segment])) {
                $config = $config[$segment];
            } elseif ($config instanceof \ArrayAccess && isset($config[$segment])) {
                $config = $config[$segment];
            } else {
                return $default; // Restore default/fallback values ​​if key is lost
            }
        }

        return $config;
    }
}
