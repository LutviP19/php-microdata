<?php

namespace App\Core\Auth;

/**
 * JWT Library to handle public access Authorization
 * Algoritma: HMAC SHA256 (HS256)
 */
class JWT {

    private string $secret;

    public function __construct(?string $secret = null) 
    {
        $this->secret = $secret ?? config('app.jwt_secret');
    }

    /**
     * Membuat Token JWT
     */
    public function encode(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Verifikasi dan Decode Token JWT
     */
    public function decode(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;

        // Verifikasi Signature
        $validSignature = hash_hmac('sha256', $header . "." . $payload, $this->secret, true);
        if (!hash_equals($this->base64UrlEncode($validSignature), $signature)) {
            return null;
        }

        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);

        // Cek Expiration (exp) jika ada
        if (isset($decodedPayload['exp']) && time() > $decodedPayload['exp']) {
            return null; // Token kadaluarsa
        }

        return $decodedPayload;
    }

    /**
     * Verifikasi hak akses
     * Bisa ambil dari Gate Class
     */
    public function hasPermission(string $token, string $requiredPermission): bool {
        $data = $this->decode($token);
        return $data && isset($data['user_permissions']) && in_array($requiredPermission, $data['user_permissions']);
    }

    /**
     * Helper Base64Url Encode
     */
    private function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Helper Base64Url Decode
     */
    private function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}