<?php

namespace App\Core\Auth;

/**
 * SodiumAuth Library 
 * PASETO (Platform-Agnostic Security Tokens)
 * PasetoLite (Simetris/Shared Key) yang memanfaatkan XChaCha20-Poly1305 melalui ekstensi Sodium.
 * Algoritma: HMAC SHA256 (HS256)
 */

 class SodiumAuth {
    private string $key;
    private string $prefix;

    public function __construct(?string $hexKey = null) {
        // Key harus 32 bytes (biner)
        $hexKey = $hexKey ?? config('app.sodium_key');
        // Mengubah 64 karakter hex menjadi 32 byte biner
        $this->key = hex2bin($hexKey);

        // Mengikuti standar PASETO
        $this->prefix = config('app.sodium_prefix') . '.';
    }

    /**
     * Membuat Token (Encrypt)
     */
    public function encode(array $payload): string {
        // Nonce harus unik setiap kali enkripsi
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        // Bedakan prefix refresh
        if(isset($payload['type']) && $payload['type'] === 'refresh') 
        $this->prefix = str_replace('access', 'refresh', $this->prefix);
        
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            json_encode($payload),
            $this->prefix, // Footer/AD untuk integritas
            $nonce,
            $this->key
        );

        // Gabungkan Nonce + Ciphertext lalu encode ke Base64Url
        return $this->prefix . $this->base64UrlEncode($nonce . $ciphertext);
    }

    /**
     * Memvalidasi & Dekripsi Token
     */
    public function decode(string $token): ?array {
        if (strpos($token, $this->prefix) !== 0) return null;

        $raw = $this->base64UrlDecode(substr($token, strlen($this->prefix)));
        $nonceSize = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

        if (strlen($raw) <= $nonceSize) return null;

        $nonce = mb_substr($raw, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($raw, $nonceSize, null, '8bit');

        try {
            $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                $ciphertext,
                $this->prefix,
                $nonce,
                $this->key
            );

            return $decrypted ? json_decode($decrypted, true) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode(string $data): string 
    {
        $data = str_replace(['-', '_'], ['+', '/'], $data);
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        
        return base64_decode($data);
    }
}
