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
            $sql = "SELECT DISTINCT p.slug 
                    FROM user_roles ur
                    JOIN role_permissions rp ON ur.role_id = rp.role_id
                    JOIN permissions p ON rp.permission_id = p.id
                    WHERE ur.user_id = ? AND (ur.group_id = ? OR ur.group_id IS NULL)";
            
            $rows = $db->execQuery($sql, [$userId, $groupId], false, false, true);
            return array_column($rows, 'slug');
        }, (int)(config('session.lifetime') * 30));
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
                $isAllowAccess = false;
                http_response_code(403);
                include BASEPATH . "/views/error/403.php";
                exit();
            }
        }
    }
}