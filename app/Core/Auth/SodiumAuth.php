<?php

namespace App\Core\Auth;

/**
 * SodiumAuth Library 
 * PASETO (Platform-Agnostic Security Tokens)
 * PasetoLite (Simetris/Shared Key) yang memanfaatkan XChaCha20-Poly1305 melalui ekstensi Sodium.
 * Algoritma: HMAC SHA256 (HS256)
 */

use Exception;

class SodiumAuth {
    private readonly string $key;
    private readonly string $basePrefix;

    public function __construct(?string $hexKey = null) {
        $hexKey ??= config('app.sodium_key');
        $this->key = hex2bin($hexKey);
        
        // Simpan prefix dasar (misal: "v1.access")
        $this->basePrefix = config('app.sodium_prefix');
    }

    /**
     * Membuat Token (Encrypt)
     */
    public function encode(array $payload): string {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        // Bedakan prefix refresh
        $currentPrefix = $this->basePrefix;
        if (isset($payload['type']) && $payload['type'] === 'refresh') {
            $currentPrefix = str_replace('access', 'refresh', $currentPrefix);
        }
        
        $footer = $currentPrefix . '.';

        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            json_encode($payload),
            $footer, 
            $nonce,
            $this->key
        );

        return $footer . $this->base64UrlEncode($nonce . $ciphertext);
    }

    /**
     * Memvalidasi & Dekripsi Token
     */
    public function decode(string $token): ?array {
        // Deteksi Prefix secara otomatis dari string token
        // Mencari apakah token mengandung '.access.' atau '.refresh.'
        $currentPrefix = $this->basePrefix;
        $refreshPrefix = str_replace('access', 'refresh', $currentPrefix);

        if (str_starts_with($token, $refreshPrefix . '.')) {
            $currentPrefix = $refreshPrefix;
        } elseif (!str_starts_with($token, $currentPrefix . '.')) {
            return null; // Prefix tidak dikenal
        }

        $footer = $currentPrefix . '.';
        $payloadPart = substr($token, strlen($footer));
        $raw = $this->base64UrlDecode($payloadPart);
        
        $nonceSize = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (strlen($raw) <= $nonceSize) return null;

        $nonce = mb_substr($raw, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($raw, $nonceSize, null, '8bit');

        try {
            $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                $ciphertext,
                $footer,
                $nonce,
                $this->key
            );

            if (!$decrypted) return null;

            $payload = json_decode($decrypted, true);
            
            if (isset($payload['exp']) && time() > $payload['exp']) {
                return null; // Expired
            }

            return $payload;
        } catch (Exception) {
            return null;
        }
    }

    private function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode(string $data): string {
        $data = str_replace(['-', '_'], ['+', '/'], $data);
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode($data);
    }
}