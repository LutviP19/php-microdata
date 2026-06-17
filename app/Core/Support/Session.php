<?php

namespace App\Core\Support;

use App\Core\Support\Config;

/**
 * Handle all the stuff related to session.
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */
class Session
{
    /**
     * Get all session values.
     *
     * @return array
     */
    public static function all()
    {
        $sessions = [];
        $escaped = [
            Config::get("session.csrf_token"),
            "OBSOLETE",
            "EXPIRES",
            "nonce",
            "new_session_id",
            "destroyed",
            "userAgent",
            "IPaddress",
            "password",
            "pin",
            "errors",
            "secret",
            "jwtId",
            "tokenJwt",
            "gnr",
            "_previous_uri",
            "_old_input",
        ];

        foreach ($_SESSION as $key => $value) {
            if (in_array($key, $escaped)) {
                continue;
            }

            $data = config("session.encrypt") ? decryptData($value) : $value;
            if (empty($data)) {
                continue;
            }

            // 2. Fitur Array to String Conversion (Handling JSON)
            if (is_string($data)) {
                $decoded = json_decode($data, true);
                $sessions[$key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $data;
            } else {
                $sessions[$key] = is_array(json_decode((string) $data, true))
                    ? json_decode((string) $data, true)
                    : $data;
            }
        }

        return $sessions;
    }

    /**
     * Get a session value by key.
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (!self::has($key)) {
            return "";
        }

        $value = $_SESSION[$key];

        if (config("session.encrypt")) {
            // Dekripsi data mentah dari session
            $decrypted = decryptData($value);
            $decoded = json_decode((string) $decrypted, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $decrypted;
        }

        return is_array(json_decode((string) $value, true)) ? json_decode((string) $value, true) : $value;
    }

    /**
     * Set a value (Compatible with string and array).
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set($key, $value)
    {
        // Jika value adalah array, ubah menjadi JSON string agar bisa dienkripsi
        $processedValue = is_array($value) || is_object($value) ? json_encode($value) : $value;

        if (config("session.encrypt")) {
            $processedValue = encryptData($processedValue);
        }

        $_SESSION[$key] = $processedValue;

        return true;
    }

    /**
     * Determine if a value exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]) ? true : false;
    }

    /**
     * Unset/Remove a value.
     *
     * @param string $key
     * @return void
     */
    public static function unset($key, $recursive_unset = false)
    {
        if ($recursive_unset) {
            recursive_unset($_SESSION, $key);
        } else {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Completely destroy the session.
     *
     * @return void
     */
    public static function destroy()
    {
        $key = Config::get("session.csrf_token");
        $csrfToken = self::get($key);
        session_unset();
        $_SESSION = [];

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        self::set($key, $csrfToken);
    }

    /**
     * Destroy the user session data only.
     *
     * @return void
     */
    public static function softDestroy()
    {
        $ignoreKeys = [Config::get("session.csrf_token"), "_previous_uri", "IPaddress", "userAgent"];
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $ignoreKeys)) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Make the value available for the next request.
     * (Flash message)
     *
     * @param string $key
     * @param string|null $value
     * @return mixed
     */
    public static function flash($key, $value = null)
    {
        if (self::has($key)) {
            //value exists so return and unset it.
            $flash = self::get($key);
            self::unset($key);
            return $flash;
        } else {
            //value doesn't exists so set it.
            self::set($key, $value);
        }
    }

    /**
     * Get the previous uri stored in the session.
     *
     * @return string
     */
    public static function getPreviousUri()
    {
        return self::get("_previous_uri");
    }

    /**
     * Set the previous uri in the session.
     *
     * @param string $uri
     * @return void
     */
    public static function setPreviousUri($uri)
    {
        self::set("_previous_uri", $uri);
    }

    /**
     * Get the input value from the previous request.
     *
     * @param string $key
     * @return mixed
     */
    public static function getOldInput($key)
    {
        return isset(self::get("_old_input")[$key]) ? self::flash("_old_input")[$key] : "";
    }

    /**
     * set the input (POST) values from the previous request.
     *
     * @return void
     */
    public static function setOldInput()
    {
        $inputs = [];
        foreach ($_POST as $input => $value) {
            $inputs[e($input)] = e($value);
        }

        self::set("_old_input", $inputs);
    }
}
