<?php

namespace App\Core\Support;

/**
 * Handle all the stuff related to cookies.
 * 
 * @author Lutvi <lutvip19@gmail.com>
 */
class Cookie
{
    /**
     * Default options for cookie setting.
     * 
     * @var array
     */
    protected static array $defaults = [
        'expires'  => 0,
        'path'     => '/',
        'domain'   => null,
        'secure'   => false,
        'httponly' => true, // Secure default to prevent XSS
        'samesite' => 'Lax', // Modern standards for CSRF prevention
    ];

    /**
     * Get a cookie value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(?string $key = null, mixed $default = null): mixed
    {
        // If key is null, returns the entire cookies array
        if ($key === null) {
            return $_COOKIE ?? [];
        }

        return $_COOKIE[$key] ?? $default;
    }

    /**
     * Set a cookie value with dynamic options support.
     *
     * @param string $key
     * @param string $value
     * @param array|int $options Array of option configurations or expiration TTLs in integer form (timestamp/seconds)
     * @return bool
     */
    public static function set(string $key, string $value, array|int $options = []): bool
    {
        if (headers_sent()) {
            return false;
        }

        // If the third parameter is an integer, assume it is 'expires'
        if (is_int($options)) {
            $options = ['expires' => $options];
        }

        // Combine options with defaults
        $config = array_merge(self::$defaults, $options);

        // If value is an array, send multiple cookies with the same key name
        if (is_array($value)) {
            $success = true;
            foreach ($value as $val) {
                $result = setcookie("{$key}[]", $val, [
                    'expires'  => $config['expires'],
                    'path'     => $config['path'],
                    'domain'   => $config['domain'],
                    'secure'   => $config['secure'],
                    'httponly' => $config['httponly'],
                    'samesite' => $config['samesite'],
                ]);
                if (!$result) $success = false;
            }
            return $success;
        }

        // Execute the PHP 8.x built-in setcookie function with the options array
        return setcookie($key, $value, [
            'expires'  => $config['expires'],
            'path'     => $config['path'],
            'domain'   => $config['domain'],
            'secure'   => $config['secure'],
            'httponly' => $config['httponly'],
            'samesite' => $config['samesite'],
        ]);
    }

    /**
     * Determine if a cookie value exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_COOKIE[$key]);
    }

    /**
     * Unset/Remove a cookie value.
     *
     * @param string $key
     * @param string $path
     * @param string|null $domain
     * @return bool
     */
    public static function unset(string $key, string $path = '/', ?string $domain = null): bool
    {
        if (headers_sent()) {
            return false;
        }

        // Delete from superglobal memory directly to synchronize within the same request
        unset($_COOKIE[$key]);

        // Set expiration to the past (1 hour ago) to trigger deletion in the browser
        return setcookie($key, '', [
            'expires'  => time() - 3600,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => self::$defaults['secure'],
            'httponly' => self::$defaults['httponly'],
            'samesite' => self::$defaults['samesite'],
        ]);
    }
}