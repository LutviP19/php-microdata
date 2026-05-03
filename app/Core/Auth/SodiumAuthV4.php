<?php

namespace App\Core\Auth;

use Exception;

/**
 * SodiumAuth Library (Asymmetric - Ed25519)
 * Standar: PASETO v4.public (Versi Asimetris)
 */
class SodiumAuthV4 {
    private string $privateKey;
    private string $publicKey;
    private readonly string $basePrefix;

    public function __construct(?string $hexPrivateKey = null, ?string $hexPublicKey = null) {
        $hexPrivateKey ??= config('app.sodium_private_key');
        if ($hexPrivateKey) {
            $this->privateKey = hex2bin($hexPrivateKey);
        }

        $hexPublicKey ??= config('app.sodium_public_key');
        if ($hexPublicKey) {
            $this->publicKey = hex2bin($hexPublicKey);
        }

        $prefix = config('app.sodium_prefix');
        $this->basePrefix = str_replace(['v1', 'v2', 'v3'], 'v4', $prefix);
    }

    /**
     * Membuat Token (Sign)
     */
    public function encode(array $payload): string {
        if (empty($this->privateKey)) {
            throw new Exception("Private Key is required for signing.");
        }

        $message = json_encode($payload);

        // Bedakan prefix refresh
        $currentPrefix = $this->basePrefix;
        if (isset($payload['type']) && $payload['type'] === 'refresh') {
            $currentPrefix = str_replace('access', 'refresh', $currentPrefix);
        }
        
        $footer = $currentPrefix . '.'; 

        $signature = sodium_crypto_sign_detached($footer . $message, $this->privateKey);
        
        return $footer . $this->base64UrlEncode($message . $signature);
    }

    /**
     * Memvalidasi Token (Verify)
     */
    public function decode(string $token): ?array {
        if (empty($this->publicKey)) {
            throw new Exception("Public Key is required for verification.");
        }

        // Deteksi Prefix secara otomatis
        $currentPrefix = $this->basePrefix;
        $refreshPrefix = str_replace('access', 'refresh', $currentPrefix);

        if (str_starts_with($token, $refreshPrefix . '.')) {
            $currentPrefix = $refreshPrefix;
        } elseif (!str_starts_with($token, $currentPrefix . '.')) {
            return null; 
        }

        $footer = $currentPrefix . '.';
        
        $rawPayload = substr($token, strlen($footer));
        $decoded = $this->base64UrlDecode($rawPayload);
        
        $signatureSize = SODIUM_CRYPTO_SIGN_BYTES; // 64 bytes
        $messageSize = strlen($decoded) - $signatureSize;

        if ($messageSize <= 0) return null;

        $message = mb_substr($decoded, 0, $messageSize, '8bit');
        $signature = mb_substr($decoded, $messageSize, null, '8bit');

        $isValid = sodium_crypto_sign_verify_detached(
            $signature,
            $footer . $message,
            $this->publicKey
        );

        if (!$isValid) return null;

        $payload = json_decode($message, true);

        if (!isset($payload['exp'])) return null;

        $leeway = 60; 
        if (time() > ($payload['exp'] + $leeway)) return null;
        
        if (isset($payload['nbf']) && time() < ($payload['nbf'] - $leeway)) return null;

        return $payload;
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

    public static function generateKeys(): array {
        $kp = sodium_crypto_sign_keypair();
        return [
            'private' => bin2hex(sodium_crypto_sign_secretkey($kp)),
            'public'  => bin2hex(sodium_crypto_sign_publickey($kp))
        ];
    }
}