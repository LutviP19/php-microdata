<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Auth\v2;


use App\Structs\Auth\v2\AuthStruct;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class AuthData extends AuthStruct {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static string $table = "users";

    /**
     * Primary key column name
     */
    protected static string $primaryKey = 'id';

    /**
     * Enable or disable Soft Deletes
     */
    protected static bool $useSoftDeletes = true;

    /**
     * Soft delete timestamp column
     */
    protected static string $deletedAtColumn = 'deleted_at';

    public function __construct(?PDO $pdo = null)
    {
        // Default connection
        $conn = $pdo ?? Connection::make(config('default_db'));

        // save it to static properties
        static::$staticPdo = $conn;
    }
    
    /**
     * getAllData
     *
     * @param  mixed $id
     * @param  mixed $selectCols
     * @return void
     */
    public function getAllData($id = null, $selectCols = '*') {
        if(!is_null($id)) {
            // Logic to getUserProfile
            return static::select($selectCols)->where(static::$primaryKey, '=', $id)->limit(10)->get();
        }
            
        return static::select($selectCols)->limit(10)->get();
    }

    public function seed() {        
        $permissions = [
            [1, 'Create Asset', 'asset-create'],
            [2, 'View Asset', 'asset-view'],
            // ... dst
        ];

        foreach ($permissions as $p) {
            static::rawStatement("CALL sp_upsert_permission(?, ?, ?)", $p);
        }

        // Mapping juga bisa dinamis
        static::rawStatement("CALL sp_assign_permission(?, ?)", [2, 1]); // Role 2, Perm 1
    }

    /**
     * Seeder Dinamis di PHP
     */
    public function syncPermissions(array $data) {
        foreach ($data as $item) {
            $sql = "INSERT INTO `permissions` (`id`, `name`, `slug`, `created_at`) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `slug` = VALUES(`slug`)";
            
            static::rawQuery($sql, [$item['id'], $item['name'], $item['slug']]);
        }
    }

    /**
     * Mendapatkan semua permission user di group tertentu
     */
    public function getPermissions($userId, $groupId) {
        $sql = "SELECT permission_slug 
                FROM v_user_permissions 
                WHERE user_id = ? 
                AND (group_id = ? OR group_id IS NULL)";
                
        $result = static::rawQuery($sql, [$userId, $groupId]);
        return array_column($result, 'permission_slug');
    }

    
}