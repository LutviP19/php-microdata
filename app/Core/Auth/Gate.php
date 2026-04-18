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
     * Inisialisasi izin user ke dalam static property
     */
    public static function loadAbilities($userId, $groupId)
    {
        $cache = new Cache();
        $cacheKey = "user_abilities_{$userId}_{$groupId}";

        self::$abilities = $cache->remember($cacheKey, function() use ($userId, $groupId) {
            $db = new Model();
            $sql = "SELECT permission_slug AS slug FROM v_user_permissions 
                    WHERE user_id = ? AND group_id = ?";
            
            $rows = $db->execQuery($sql, [$userId, $groupId], false, false, true);
            return array_column($rows, 'slug');
        }, (int)(config('session.lifetime') * 30));
        // dd(self::$abilities);
    }

    /**
     * Cek apakah user punya izin spesifik
     */
    public static function allows($permission)
    {
        return in_array($permission, self::$abilities);
    }

    /**
     * Kebalikan dari allows
     */
    public static function denies($permission)
    {
        return !self::allows($permission);
    }

    /**
     * Proteksi di level Model (langsung stop jika tidak punya akses)
     */
    public static function authorize($permission)
    {
        if (self::denies($permission)) {
            if(is_json_request()) {
                $message = "You don't have access[$permission]";
                $errors = [
                    'auth' => 'Forbidden to access: ' . $permission
                ];
                json_response([], 403, $message, $errors);
            } else {
                http_response_code(isHtmx() ? 200 : 403);
                include BASEPATH . "/views/error/403.php";
                exit();
            }
        }

        return true;
    }

    /**
     * Middleware untuk memvalidasi akses user
     * @param string|null $requiredPermission Permission yang dibutuhkan (opsional)
     */
    public static function authorizeJwt(?string $requiredPermission = null, ?string $secret = null) {
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        $token = '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // Jika token kosong, cek dari Cookie (fallback umum)
        if (!$token && isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
        }

        if (!$token) {
            self::unauthorized_response("Invalid token.");
        }
        
        $secret = $secret ?? config('app.jwt_secret');
        $jwt = new JWT($secret);
        $userData = $jwt->decode($token);

        // Verifikasi Token
        if (!$userData) {
            self::unauthorized_response("Session has expired please re-login.");
        }

        // Verifikasi Permission (jika diminta)
        if ($requiredPermission) {
            $permissions = $userData['user_permissions'] ?? [];
            if (!in_array($requiredPermission, $permissions)) {
                self::forbidden_response("$requiredPermission");
            }
        }

        return $userData;
    }

    /**
     * Response Helper jika tidak login (401)
     */
    private static function unauthorized_response($msg) {
        if(is_json_request()) {
            $message = "Unauthorized";
            $errors = [
                'auth' => 'Unauthorized: ' . $msg
            ];
            json_response([], 401, $message, $errors);
        } else {
            http_response_code(isHtmx() ? 200 : 401);
            include BASEPATH . "/views/error/401.php";
            exit();
        }
    }

    /**
     * Response Helper jika tidak punya izin (403)
     */
    private static function forbidden_response($permission) {
        if(is_json_request()) {
            $message = "You don't have access[$permission]";
            $errors = [
                'auth' => 'Forbidden to access: ' . $permission
            ];
            json_response([], 403, $message, $errors);
        } else {
            http_response_code(isHtmx() ? 200 : 403);
            include BASEPATH . "/views/error/403.php";
            exit();
        }
    }
}