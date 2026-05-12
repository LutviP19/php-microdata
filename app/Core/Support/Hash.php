<?php

namespace App\Core\Support;

/**
 * Hash class
 * @author Lutvi <lutvip19@gmail.com>
 */
class Hash
{
    /**
     * Create a MAC for the given value.
     *
     * @param  mixed  $value
     * @param  string  $key
     * @param  string  $mode : base64 | string
     * @return string
     */
    public static function create(#[\SensitiveParameter] $value, #[\SensitiveParameter] $key = "", $mode = "base64")
    {
        $key = str_replace("base64:", "", (string) $key ?: Config::get("app.hash_key"));

        if ($mode === "string") {
            return hash_hmac("sha256", (string) $value, $key);
        }

        return base64_encode(hash_hmac("sha256", (string) $value, $key));
    }

    /**
     * Verify a hash against a string.
     *
     * @param string $str
     * @param string $hash
     * @param  string  $key
     * @param  string  $mode : base64 | string
     * @return bool
     */
    public static function matchHash(
        #[\SensitiveParameter] $str,
        $hash,
        #[\SensitiveParameter] $key = "",
        $mode = "base64",
    ) {
        return hash_equals(self::create($str, $key, $mode), $hash);
    }

    /**
     * Create a hash from string.
     *
     * @param string $str
     * @return string
     */
    public static function makePassword(#[\SensitiveParameter] $str)
    {
        return password_hash($str, PASSWORD_BCRYPT);
    }

    /**
     * Verify a hash against a string.
     *
     * @param string $str
     * @param string $hash
     * @return bool
     */
    public static function matchPassword(#[\SensitiveParameter] $str, $hash)
    {
        return password_verify($str, $hash);
    }

    /**
     * Create a unique hash.
     *
     * @param int $len
     * @return string
     */
    public static function unique($len = 32)
    {
        return bin2hex(random_bytes($len));
    }

    /**
     * Create a random string.
     *
     * @param int $len
     * @return string
     */
    public static function randomString(int $len = 64, bool $special = true)
    {
        $partLen = $len / 2;
        // Ambil entropi biner murni (CSPRNG)
        $binaryPart = bin2hex(random_bytes($partLen));

        $characters = "012356789ABCDEFGHIJKLMNOPQRSTUVWXYZ098765321";
        $characters .= $special ? 'abcdefghijklmnopqrstuvwxyz*&!@%^#$' : "";
        $max = mb_strlen($characters, "8bit") - 1;
        $string = "";

        for ($i = 0; $i < $partLen; ++$i) {
            $string .= $characters[random_int(0, $max)];
        }
        $combined = str_shuffle($binaryPart . $string);

        return substr($combined, 0, $len);
    }
}
