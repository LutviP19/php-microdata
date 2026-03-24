<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Auth;


use App\Structs\Auth\AuthStruct;
use PDO; // new PDO object
use App\Core\Database\Connection; // Uncomment to use custom DB connection

class AuthData extends AuthStruct {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static $tableM = "users";

    public function __construct(PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $driver = 'mysql';
        // $dbname = 'test';
        // $host = '127.0.0.1';
        // $port = '3306';
        // $username = 'rooty';
        // $password = 'cccc';
        // $options = [];
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);

        
        // Set PDO connection
        $this->pdo = $conn;

        // Set default table
        $this->table = self::$tableM;
    }
    
    /**
     * getAllData
     *
     * @param  mixed $id
     * @param  mixed $selectCols
     * @return void
     */
    public function getAllData($id = null, $selectCols = '*') {
        // $selectCols = $cols ?? '*';
        $id = $id ? [$id] : [];
        $sql = 'SELECT '.$selectCols.' FROM '.$this->table;
        $sql .= !empty($id) ? " WHERE id = ? LIMIT 1 ": " LIMIT 10";
        $result = self::table($this->table)->execQuery($sql, $id, false, !empty($id), empty($id));
        // dd($result, true);
        return $result;
    }

    public function seed() {
        $db = new AuthStruct();
        
        $permissions = [
            [1, 'Create Asset', 'asset-create'],
            [2, 'View Asset', 'asset-view'],
            // ... dst
        ];

        foreach ($permissions as $p) {
            $db->execQuery("CALL sp_upsert_permission(?, ?, ?)", $p);
        }

        // Mapping juga bisa dinamis
        $db->execQuery("CALL sp_assign_permission(?, ?)", [2, 1]); // Role 2, Perm 1
    }

    /**
     * Seeder Dinamis di PHP
     */
    public function syncPermissions(array $data) {
        $db = new AuthStruct();
        
        foreach ($data as $item) {
            // SQL ini aman dijalankan berulang kali (Idempotent)
            $sql = "INSERT INTO `permissions` (`id`, `name`, `slug`, `created_at`) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `slug` = VALUES(`slug`)";
            
            $db->execQuery($sql, [$item['id'], $item['name'], $item['slug']]);
        }

        // // Cara Pakai:
        // $this->syncPermissions([
        //     ['id' => 1, 'name' => 'Create Asset', 'slug' => 'asset-create'],
        //     ['id' => 2, 'name' => 'View Asset', 'slug' => 'asset-view'],
        // ]);
    }

    /**
     * Mendapatkan semua permission user di group tertentu
     */
    public function getPermissions($userId, $groupId) {
        $db = new AuthStruct();
        $sql = "SELECT permission_slug 
                FROM v_user_permissions 
                WHERE user_id = ? 
                AND (group_id = ? OR group_id IS NULL)";
                
        $result = $db->execQuery($sql, [$userId, $groupId], false, false, true);
        return array_column($result, 'permission_slug');
    }

    
}