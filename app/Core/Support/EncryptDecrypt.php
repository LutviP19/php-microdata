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
     * Data encryption using AES-256-CBC chipper
     */
    public function encrypt($data) {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        // Data encryption (using generated IV)
        $encrypted = openssl_encrypt(
            json_encode($data), 
            $this->cipher, 
            $this->key, 
            OPENSSL_RAW_DATA, 
            $iv
        );

        // Combine IV + Encrypted Data then encode to base64 for easy storage/sending
        return base64_encode($iv . $encrypted);
    }

    /**
     * Data decryption using AES-256-CBC chipper
     */
    public function decrypt($payload) {
        $payload = base64_decode($payload);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        
        // Separate IV and Ciphertext
        $iv = substr($payload, 0, $ivLength);
        $ciphertext = substr($payload, $ivLength);

        $decrypted = openssl_decrypt(
            $ciphertext, 
            $this->cipher, 
            $this->key, 
            OPENSSL_RAW_DATA, 
            $iv
        );

        return json_decode($decrypted, true);
    }
}