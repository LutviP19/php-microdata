<?php

/**
 * Gate class
 * This class functions as a wrapper to check whether the logged in user has certain permissions.
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Auth;

use App\Core\Support\Cache;
use App\Core\Database\Model;

class Gate
{
    protected static $abilities = [];

    /**
     * Initialize user permissions into static properties
     */
    public static function loadAbilities($userId, $groupId): void
    {
        $cache = new Cache();
        $cacheKey = "user_abilities_{$userId}_{$groupId}";

        self::$abilities = $cache->remember(
            $cacheKey,
            function () use ($userId, $groupId) {
                $db = new Model();
                $sql = "SELECT permission_slug AS slug FROM v_user_permissions
                    WHERE user_id = ? AND group_id = ?";

                $rows = $db->execQuery($sql, [$userId, $groupId], false, false, true);
                return array_column($rows, "slug");
            },
            (int) (config("session.lifetime") * 30),
        );
        // dd(self::$abilities);
    }

    /**
     * Check whether the user has specific permissions
     */
    public static function allows($permission): array
    {
        return in_array($permission, self::$abilities);
    }

    /**
     * The opposite of allows
     */
    public static function denies($permission): bool
    {
        return !self::allows($permission);
    }

    /**
     * Protection at Model level (stop immediately if you don't have access)
     */
    public static function authorize($permission): bool
    {
        if (self::denies($permission)) {
            self::forbidden_response($permission);
        }

        return true;
    }

    /**
     * Middleware to validate user access using JWT
     * @param string|null $requiredPermission Required permissions (optional)
     */
    public static function authorizeJwt(?string $requiredPermission = null, ?string $secret = null): ?array
    {
        $headers = getallheaders();
        $authHeader = $headers["Authorization"] ?? ($headers["authorization"] ?? "");

        if (is_string($authHeader) && $authHeader === '') {
            self::unauthorized_response("Invalid token.");
        }

        $token = "";
        if (preg_match("/Bearer\s(\S+)/", $authHeader, $matches)) {
            $token = $matches[1];
        }

        // If token is empty, check from Cookie (common fallback)
        if (!$token && isset($_COOKIE["auth_token"])) {
            $token = $_COOKIE["auth_token"];
        }

        if (!$token) {
            self::unauthorized_response("Invalid token.");
        }

        $secret ??= config("app.jwt_secret");
        $jwt = new JWT($secret);
        $userData = $jwt->decode($token);

        // Token Verification
        if (!$userData) {
            self::unauthorized_response("Session has expired please re-login.");
        }

        // Permission Verification (if requested)
        if ($requiredPermission) {
            $permissions = $userData["user_permissions"] ?? [];
            if (!in_array($requiredPermission, $permissions)) {
                self::forbidden_response($requiredPermission);
            }
        }

        return $userData;
    }

    /**
     * Middleware to validate user access using Sodium
     * @param string|null $requiredPermission Required permissions (optional)
     */
    public static function authorizeSodium(?string $requiredPermission = null, ?string $hexKey = null): ?array
    {
        $headers = getallheaders();
        $authHeader = $headers["Authorization"] ?? ($headers["authorization"] ?? "");

        if (is_string($authHeader) && $authHeader === '') {
            self::unauthorized_response("Invalid token.");
        }

        $token = "";
        if (preg_match("/Bearer\s(\S+)/", $authHeader, $matches)) {
            $token = $matches[1];
        }

        // If token is empty, check from Cookie (common fallback)
        if (!$token && isset($_COOKIE["auth_token"])) {
            $token = $_COOKIE["auth_token"];
        }

        if (!$token) {
            self::unauthorized_response("Invalid token.");
        }

        $hexKey ??= config("app.sodium_key");
        $auth = new SodiumAuth($hexKey);
        $userData = $auth->decode($token);

        // Token Verification
        if (!$userData || !isset($userData["exp"])) {
            self::unauthorized_response("Session has expired please re-login.");
        }

        // Cek exp
        if ($userData && isset($userData["exp"])) {
            $leeway = 60; // Toleransi perbedaan waktu antar server (60 detik)
            if (time() > $userData["exp"] + $leeway) {
                self::unauthorized_response("Session has expired please re-login.");
            }
        }

        // // Cek Type refresh
        // if ($userData && isset($userData['type'])) {
        //     if($userData['type'] === 'refresh')
        //         return self::refreshSodiumAuth($userData, $hexKey);
        // }

        // Permission Verification (if requested)
        if ($requiredPermission) {
            $permissions = $userData["user_permissions"] ?? [];
            if (!in_array($requiredPermission, $permissions)) {
                self::forbidden_response($requiredPermission);
            }
        }

        return $userData;
    }

    public static function refreshSodiumAuth(string $refreshToken, ?string $hexKey = null): ?array
    {
        $hexKey ??= config("app.sodium_key");
        $sodiumAuth = new SodiumAuth($hexKey);

        // 1. Decode & Verify Signature Refresh Token
        $payload = $sodiumAuth->decode($refreshToken);
        $isValid = isset($payload["type"]) && isset($payload["iis"]) && isset($payload["fingerprint"]);

        if (!$payload || !$isValid || $payload["type"] !== "refresh") {
            self::unauthorized_response("Invalid Refresh Token");
        }

        // // 2. Check in Redis (Whitelist)
        // // This is important so that the Refresh Token can be revived if the user logs out/bans
        // $stored = $this->redis->get("sso_refresh_map:" . $payload['uid']);
        // if ($stored !== $refreshToken) {
        //     self::unauthorized_response("Refresh Token Revoked");
        // }

        // Default payload
        $expToken = time() + config('session.exptime');
        $payloadDefault = [
            "uid" => $payload["uid"],
            "role" => $payload["role"],
            "fingerprint" => get_device_fingerprint(),
            "iat" => time(),
            "iss" => "php-microdata",
            "aud" => "users",
        ];
        $fullPayload = array_merge($payloadDefault, $payload);

        // 3. Generate New Access Token (Short: 1 hour)
        $newAccessToken = array_merge($fullPayload, [
            "type" => "access",
            "exp" => $expToken,
        ]);

        // 4. Generate New Refresh Token (Rotation -Optional but recommended)
        $newRefreshToken = array_merge($fullPayload, [
            "type" => "refresh",
            "exp" => days_to_seconds(7), // 7 Hari
        ]);

        // // 5. Update Redis Whitelist
        // $this->redis->setex("sso_refresh_map:" . $payload['uid'], 86400 * 7, $newRefreshToken);

        $dataNewToken = [
            "access_token" => $sodiumAuth->encode(cleanSodiumPayload($newAccessToken, "access")),
            "refresh_token" => $sodiumAuth->encode(cleanSodiumPayload($newRefreshToken, "refresh")),
        ];

        // Generate New Token Sodium
        return $dataNewToken;
    }

    /**
     * Middleware to validate user access using SodiumV4
     * @param string|null $requiredPermission Required permissions (optional)
     */
    public static function authorizeSodiumV4(?string $requiredPermission = null, ?string $pubKey = null): ?array
    {
        $token = getBearerToken();
        if (!$token) {
            self::unauthorized_response("Invalid token.");
        }

        $pubKey ??= config("app.sodium_public_key");
        $privateKey = config("app.sodium_private_key");
        $auth = new SodiumAuthV4($privateKey, $pubKey);
        $userData = $auth->decode($token);
        // dd($userData, true);

        // Check session cache redis
        $prefixKey = sessionKeyFormat($userData['uid'], $userData['jti']);
        $isKeyExists = getKeyTllFromRedis($prefixKey);
        $isInvalidToken = ($isKeyExists <= 0 || !$userData || !isset($userData["exp"]));

        // Token Verification
        if ($isInvalidToken) {
            self::unauthorized_response("Session has expired please re-login.");
        }

        // Check exp
        if ($userData && isset($userData["exp"])) {
            $leeway = 60; // Toleransi perbedaan waktu antar server (60 detik)
            if (time() > $userData["exp"] + $leeway) {
                self::unauthorized_response("Session has expired please re-login.");
            }
        }

        // // Fingerprint - Strict Mode DeviceID
        // if (isset($userData["uid"]) && isset($userData["fingerprint"])) {
        //     $fingerprint = get_device_fingerprint(false, $userData["uid"]);
        //     if (!matchHashData($fingerprint, $userData["fingerprint"])) {
        //         self::unauthorized_response("Session has expired please re-login.");
        //     }
        // }
        // // dd($userData, true);

        // Permission Verification (if requested)
        if ($requiredPermission) {
            $permissions = $userData["user_permissions"] ?? [];
            if (!in_array($requiredPermission, $permissions)) {
                self::forbidden_response($requiredPermission);
            }
        }

        return $userData;
    }

    public static function refreshSodiumAuthV4(string $refreshToken, array $payloadToken = [], ?string $pubKey = null): ?array
    {
        $pubKey ??= config("app.sodium_public_key");
        $privateKey = config("app.sodium_private_key");
        $sodiumAuth = new SodiumAuthV4($privateKey, $pubKey);

        // 1. Decode & Verify Signature Refresh Token
        $payload = $sodiumAuth->decode($refreshToken);
        if (!$payload || is_null($payload)) {
            self::unauthorized_response("Invalid Refresh Token");
        }

        $isValid = isset($payload["type"]) && isset($payload["iss"]) && isset($payload["fingerprint"]);        

        // Check session cache redis
        $prefixKey = str_replace('session', 'refresh_token', sessionKeyFormat($payload['uid'], $payload['jti']));
        $isKeyExists = getKeyTllFromRedis($prefixKey);

        if ($isKeyExists <= 0 || !$isValid || $payload["type"] !== "refresh") {
            self::unauthorized_response("Invalid Refresh Token");
        }

        // // Fingerprint - Strict Mode DeviceID
        // if (isset($payload["uid"]) && isset($payload["fingerprint"])) {
        //     $fingerprint = get_device_fingerprint(false, $payload["uid"]);
        //     if (!matchHashData($fingerprint, $payload["fingerprint"])) {
        //         self::unauthorized_response("Session has expired please re-login.");
        //     }
        // }
        // // dd($payload, true);

        // Default payload
        $payload = array_merge($payload, $payloadToken);
        $now = time();
        $expToken = $now + config('session.exptime');
        $expTokenRefresh = 7;
        $uid = $payload["uid"];
        
        $payloadDefault = [
            "uid" => $uid,
            'jti' => $payload["jti"],
            // "role" => $payload["role"],
            "fingerprint" => get_device_fingerprint(true, $uid),
            "iat" => $now,
            "iss" => "php-microdata.{$uid}",
            // "aud" => "users",
        ];
        $fullPayload = array_merge($payloadDefault, $payload);

        $sodiumAuth->setExpiration($expToken, $expTokenRefresh);
        $dataNewToken = [
            "access_token" => $sodiumAuth->encode($sodiumAuth->setDefaultPayload($fullPayload, "access")),
            "refresh_token" => $sodiumAuth->encode($sodiumAuth->setDefaultPayload($fullPayload, "refresh")),
        ];
        
        // Generate New Token Sodium
        return $dataNewToken;
    }

    /**
     * Response Helper if not logged in (401)
     */
    public static function unauthorized_response(?string $msg)
    {
        unauthorized_response($msg);
    }

    /**
     * Response Helper if you don't have permission (403)
     */
    public static function forbidden_response(?string $permission)
    {
        forbidden_response($permission);
    }
}
