<?php

/**
 * SodiumAuth Library (Asymmetric - Ed25519)
 * Standar: PASETO v4.public (Versi Asimetris)
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */

namespace App\Core\Auth;

use App\Core\Support\Session;
use Exception;

class SodiumAuthV4
{
    private string $privateKey;
    private string $publicKey;
    private readonly string $basePrefix;

    /**
     * set Expiration
     */
    protected int $expToken;
    protected int $refreshTokenTimeout = 7;
    protected int $leeway = 60; // Time difference tolerance between servers (60 seconds)

    public function __construct(?string $hexPrivateKey = null, ?string $hexPublicKey = null)
    {
        $hexPrivateKey ??= config("app.sodium_private_key");
        if ($hexPrivateKey) {
            $this->privateKey = hex2bin($hexPrivateKey);
        }

        $hexPublicKey ??= config("app.sodium_public_key");
        if ($hexPublicKey) {
            $this->publicKey = hex2bin($hexPublicKey);
        }

        $prefix = config("app.sodium_prefix");
        $this->basePrefix = str_replace(["v1", "v2", "v3"], "v4", $prefix);

        // Auth Token
        $this->expToken = (int) config("session.exptime");
    }

    /**
     * Shortcut setter for token duration and token refresh at once
     */
    public function setExpiration(int $expTokenInSeconds, int $refreshTokenTimeoutInDays = 7): static
    {
        $this->expToken = $expTokenInSeconds;
        $this->refreshTokenTimeout = $refreshTokenTimeoutInDays;
        return $this;
    }

    /**
     * Create a Token (Sign)
     * @param array<int,mixed> $payload Payload data to be signed
     * @return string The signed token
     */
    public function encode(array $payload): string
    {
        if (empty($this->privateKey)) {
            throw new Exception("Private Key is required for signing.");
        }

        $message = json_encode($payload);

        // Distinguish between refresh prefixes
        $currentPrefix = $this->basePrefix;
        if (isset($payload["type"]) && $payload["type"] === "refresh") {
            $currentPrefix = str_replace("access", "refresh", $currentPrefix);
        }

        $footer = $currentPrefix . ".";

        $signature = sodium_crypto_sign_detached($footer . $message, $this->privateKey);

        return $footer . $this->base64UrlEncode($message . $signature);
    }

    /**
     * Validating Tokens (Verify)
     * @param string $token The token to verify
     * @return ?array Verified payload data, or null if the token is invalid
     */
    public function decode(string $token): ?array
    {
        if (empty($this->publicKey)) {
            throw new Exception("Public Key is required for verification.");
        }

        // Prefix detection automatically
        $currentPrefix = $this->basePrefix;
        $refreshPrefix = str_replace("access", "refresh", $currentPrefix);

        if (str_starts_with($token, $refreshPrefix . ".")) {
            $currentPrefix = $refreshPrefix;
        } elseif (!str_starts_with($token, $currentPrefix . ".")) {
            return null;
        }
        

        $footer = $currentPrefix . ".";

        $rawPayload = substr($token, strlen($footer));
        $decoded = $this->base64UrlDecode($rawPayload);
        

        $signatureSize = SODIUM_CRYPTO_SIGN_BYTES; // 64 bytes
        $messageSize = strlen($decoded) - $signatureSize;

        if ($messageSize <= 0) {
            return null;
        }

        $message = mb_substr($decoded, 0, $messageSize, "8bit");
        $signature = mb_substr($decoded, $messageSize, null, "8bit");

        $isValid = sodium_crypto_sign_verify_detached($signature, $footer . $message, $this->publicKey);

        if (!$isValid) {
            return null;
        }

        $payload = json_decode($message, true);

        if (!isset($payload["exp"])) {
            return null;
        }        

        $leeway = 60;
        if (time() > ((int) $payload["exp"] + $leeway)) {
            return null;
        }

        if (isset($payload["nbf"]) && time() < ((int) $payload["nbf"] - $leeway)) {
            return null;
        }

        return $payload;
    }

    public function generateToken(array $payload = []): array
    {
        // Generate Token Sodium
        return [
            "access_token" => $this->encode($this->setDefaultPayload($payload, "access")),
            "refresh_token" => $this->encode($this->setDefaultPayload($payload, "refresh")),
        ];
    }

    public function setDefaultPayload(?array $payload = [], string $type = 'access'): array
    {
        $now = time();
        $authId = Session::get("authId") ?: generateUlid();
        $uid = $payload['uid'] ?? Session::get("uid");
        // $user_permissions = cacheContent('get', "user_abilities:{$uid}");
        // dd($authId);

        // // loadAbilities
        // if(Session::has('user_id') && Session::has('current_team_id')) {
        //     $user_id = (int) Session::get("uid");
        //     $current_team_id = (int) Session::get("current_team_id");
        // }

        // Default payload
        $payloadDefault = [
            "uid" => $uid,
            "jti" => $authId,
            'role' => 'access_backend',
            "fingerprint" => get_device_fingerprint(true, $uid),
            "iat" => $now,
            "iss" => "php-microdata",
            "aud" => config("app.url"),
            // "user_permissions" => $user_permissions,
        ];
        $fullPayload = array_merge($payloadDefault, $payload);
        // dd($fullPayload, true);

        $tokenPayload = [];
        if ($type === 'access') {
            $tokenPayload = array_merge($fullPayload, [
                "type" => "access",
                "exp" => $now + $this->expToken, // default: 2 Hours
            ]);
        } else {
            $tokenPayload = array_merge($fullPayload, [
                "type" => "refresh",
                "exp" => $now + (86400 * $this->refreshTokenTimeout), // 7 Days
            ]);
        }

        return $this->cleanPayload($tokenPayload, $type);
    }

    /**
     * Filter payloads based on token type to maintain efficiency and security.
     * Refresh token only requires basic identity to regenerate access token.
     */
    private function cleanPayload(array $payload, string $type): array
    {
        if ($type === "access") {
            return $payload; // Access token remains complete
        }

        // The list of allowed keys is in Refresh Token
        $allowedForRefresh = ["uid", "jti", "type", "exp", "iat", "iss", "fingerprint"];

        return array_intersect_key($payload, array_flip($allowedForRefresh));
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($data));
    }

    private function base64UrlDecode(string $data): string
    {
        $data = str_replace(["-", "_"], ["+", "/"], $data);
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat("=", 4 - $remainder);
        }
        return base64_decode($data);
    }

    /**
     * @return array<string,string>
     */
    public static function generateKeys(): array
    {
        $kp = sodium_crypto_sign_keypair();
        return [
            "private" => bin2hex(sodium_crypto_sign_secretkey($kp)),
            "public" => bin2hex(sodium_crypto_sign_publickey($kp)),
        ];
    }
}
