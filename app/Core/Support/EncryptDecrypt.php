<?php

namespace App\Core\Support;

use App\Core\Support\Config;

/**
 * Handle encryption data.
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */


class EncryptDecrypt {
    private $key;
    private $cipher = 'AES-256-CBC';

    public function __construct($key = null) {
        // Retrieve the key from parameters or Config
        $rawKey = (string) ($key ?: Config::get('app.key'));
        
        // Clear base64: prefix if present
        if (strpos($rawKey, 'base64:') === 0) {
            $rawKey = base64_decode(substr($rawKey, 7));
        }

        if (!$rawKey) {
            throw new Exception("App key is not set.");
        }

        // Save the original key (Make sure the length is 32 bytes for AES-256)
        $this->key = $rawKey;
    }

    /**
     * Compares plain values ​​with encrypted data.
     */
    public function match($value, $encryptedData) {
        try {
            $decrypted = $this->decrypt($encryptedData);            
            
            return $decrypted === $value;
        } catch (Exception $e) {
            
            return false;
        }
    }

    /**
     * Data encryption using AES-256-CBC chipper (No Slashes)
     * add HMAC Signature sha256 (Anti-Tamper)
     */
    public function encrypt($data) {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        // Data encryption (menggunakan RAW_DATA agar lebih ringkas sebelum di-encode)
        $encrypted = openssl_encrypt(
            json_encode($data), 
            $this->cipher, 
            $this->key, 
            OPENSSL_RAW_DATA, 
            $iv
        );

        // Buat Signature (HMAC) dari IV + Encrypted Data
        // Gunakan key yang sama atau key berbeda untuk hashing
        $signature = hash_hmac('sha256', $iv . $encrypted, $this->key, true);

        // Gabungkan: Signature (32 bytes) + IV (16 bytes) + Data
        $raw = $signature . $iv . $encrypted;

        // Gunakan Base64 URL Safe: Ganti '+' dengan '-', '/' dengan '_', dan hapus '='
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($raw));
    }

    /**
     * Data decryption (URL Safe compatible)
     */
    public function decrypt($encryptedData) {

        // Validasi string
        if (!$this->isValid($encryptedData))
            return null;

        // Kembalikan ke format Base64 standar
        $data = str_replace(['-', '_'], ['+', '/'], $encryptedData);
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        
        $decoded = base64_decode($data);
        
        // Panjang komponen (SHA256 = 32 bytes, AES-IV = 16 bytes)
        $sigLength = 32;
        $ivLength = openssl_cipher_iv_length($this->cipher);
        
        // Pisahkan komponen
        $receivedSig = substr($decoded, 0, $sigLength);
        $iv          = substr($decoded, $sigLength, $ivLength);
        $encrypted   = substr($decoded, $sigLength + $ivLength);

        // 1. VERIFIKASI: Hitung ulang HMAC dari IV + Encrypted
        $calculatedSig = hash_hmac('sha256', $iv . $encrypted, $this->key, true);

        // Gunakan hash_equals untuk mencegah Timing Attack
        if (!hash_equals($receivedSig, $calculatedSig)) {
            return false; // Segel rusak! Data telah dimanipulasi.
        }

        // 2. DEKRIPSI: Hanya dilakukan jika signature valid
        $decrypted = openssl_decrypt(
            $encrypted, 
            $this->cipher, 
            $this->key, 
            OPENSSL_RAW_DATA, 
            $iv
        );

        return json_decode($decrypted, true);
    }

    /**
     * Memvalidasi apakah string memiliki format Base64URL Safe 
     * dan panjang yang masuk akal untuk hasil enkripsi AES-256
     */
    public function isValid($string) {
        if (empty($string) || !is_string($string)) {
            return false;
        }

        // 1. Cek karakter: Hanya izinkan Alfanumerik, Dash (-), dan Underscore (_)
        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $string)) {
            return false;
        }

        // 2. Hitung panjang IV minimum (AES-256-CBC biasanya 16 bytes)
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $sigLength = 32; // SHA256
        
        // Decode sementara untuk cek ukuran byte asli
        $raw = base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
        
        // Minimal harus berisi Signature + IV + 1 byte data
        return strlen($raw) > ($sigLength + $ivLength);
    }
}