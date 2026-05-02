<?php

namespace App\Core\Auth;

use Exception;

/**
 * SodiumAuth Library (Asymmetric - Ed25519)
 * Terinspirasi oleh PASETO v4.public
 */
class SodiumAuthV4 {
    private string $privateKey;
    private string $publicKey;
    private string $prefix;

    public function __construct(?string $hexPrivateKey = null, ?string $hexPublicKey = null) {
        // Load Private Key untuk Signing (hanya di sisi SSO Server)
        $hexPrivateKey = $hexPrivateKey ?? config('app.sodium_private_key');
        if ($hexPrivateKey) {
            $this->privateKey = hex2bin($hexPrivateKey);
        }

        // Load Public Key untuk Verification (di sisi SSO Server & App Clients)
        $hexPublicKey = $hexPublicKey ?? config('app.sodium_public_key');
        if ($hexPublicKey) {
            $this->publicKey = hex2bin($hexPublicKey);
        }

        // Standar PASETO v4 public (v4.public.)
        $this->prefix = (str_replace(['v1', 'v2', 'v3'], 'v4', config('app.sodium_prefix')) ?: 'v4.public.access') . '.';
    }

    /**
     * Membuat Token (Sign)
     * Menggunakan Private Key
     */
    public function encode(array $payload): string {
        if (empty($this->privateKey)) {
            throw new Exception("Private Key is required for encoding/signing.");
        }

        $message = json_encode($payload);

        // Bedakan prefix refresh
        if(isset($payload['type']) && $payload['type'] === 'refresh') 
        $this->prefix = str_replace('access', 'refresh', $this->prefix);
        
        // Membuat tanda tangan Ed25519 (64 bytes)
        // Data yang ditandatangani: Prefix + Message
        $signature = sodium_crypto_sign_detached($this->prefix . $message, $this->privateKey);
        
        // Token = Prefix . Base64Url(Message + Signature)
        return $this->prefix . $this->base64UrlEncode($message . $signature);
    }

    /**
     * Memvalidasi Token (Verify)
     * Menggunakan Public Key
     */
    public function decode(string $token): ?array {
        if (empty($this->publicKey)) {
            throw new Exception("Public Key is required for decoding/verification.");
        }

        if (strpos($token, $this->prefix) !== 0) return null;

        // Ambil payload mentah dari token
        $decoded = $this->base64UrlDecode(substr($token, strlen($this->prefix)));
        
        // Ukuran signature Ed25519 adalah 64 bytes
        $signatureSize = SODIUM_CRYPTO_SIGN_BYTES;
        $messageSize = strlen($decoded) - $signatureSize;

        if ($messageSize <= 0) return null;

        $message = mb_substr($decoded, 0, $messageSize, '8bit');
        $signature = mb_substr($decoded, $messageSize, null, '8bit');

        // Verifikasi keaslian pesan
        $isValid = sodium_crypto_sign_verify_detached(
            $signature,
            $this->prefix . $message,
            $this->publicKey
        );

        if (!$isValid) return null;

        $payload = json_decode($message, true);

        // --- VALIDASI TIMESTAMP ---
        if (!isset($payload['exp'])) {
            return null; // Tolak jika tidak ada klaim expired demi keamanan
        }

        $leeway = 60; // Toleransi perbedaan waktu antar server (60 detik)
        if (time() > ($payload['exp'] + $leeway)) { 
            return null; // Token sudah kadaluarsa
        }
        
        // // Opsi tambahan: Validasi 'nbf' (Not Before) jika ada
        // if (isset($payload['nbf']) && time() < $payload['nbf']) {
        //     return null;
        // }

        return $payload;
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

    /**
     * Helper untuk generate keypair baru
     */
    public static function generateKeys(): array {
        $kp = sodium_crypto_sign_keypair();
        return [
            'private' => bin2hex(sodium_crypto_sign_secretkey($kp)),
            'public'  => bin2hex(sodium_crypto_sign_publickey($kp))
        ];
    }
}

// // Cara Menggunakan:
// // ----------------
// // Sodium V4 (Asymmetric - Ed25519)
// // ----------------
// use App\Core\Auth\SodiumAuthV4;
// $authV4 = new SodiumAuthV4();

// // // 1. Generate Keys (Sekali saja):
// // $keys = SodiumAuthV4::generateKeys();
// // // Simpan $keys['private'] di env SSO Server
// // // Simpan $keys['public'] di env SSO Server dan SEMUA App Client
// // // dd($keys, true);

// // 2. Di Server Backend (Login):
// $expToken = time() + (60 * (config('session.lifetime') / 2));
// $token = $authV4->encode(['uid' => 101, 'role' => 'admin', 'exp' => $expToken]);
// // dd($token);

// // 3. Di App Client (Middleware):
// $userData = $authV4->decode($token);

// if ($userData) {
//     // Token Valid dan asli!
//     dd($userData, true);
// }
// exit;
// // ==============