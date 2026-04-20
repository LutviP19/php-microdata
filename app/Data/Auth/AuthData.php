<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Auth;


use App\Core\Support\Hash;
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

    public function __construct(?PDO $pdo = null)
    {
        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);

        // Set default table
        $this->table = self::$tableM;
    }

    

    // protected function setLoginSession($user)
    // {
    //     foreach ($user as $key => $value) {
    //         if ($key === 'ulid') {
    //             $key = 'uid';
    //         }

    //         Session::set($key, $value);
    //     }

    //     Session::set('gnr', generateRandomString(32, true));
    //     $userId =  Session::get('uid');
    //     $gnr =  Session::get('gnr');

    //     // Set login session
    //     $validateClient = new \App\Core\Security\Middleware\ValidateClient($userId);
    //     $clientToken = $validateClient->getToken();
    //     $clientTokenGen = $validateClient->generateToken();
    //     Session::set('client_token', $clientTokenGen);

    //     if (false === $validateClient->matchToken($clientTokenGen)) {

    //         Session::destroy();
    //         return false;

    //         // return endResponse(
    //         //     $this->getOutput(false, 401, [
    //         //       'auth' => 'Client not found!',
    //         //     ], 'Invalid Client!'), 401);
    //     }

    //     // initJwtToken
    //     Session::set('secret', encryptData($clientToken, $gnr));
    //     Session::set('jwtId', generateUlid());
    //     $jwtToken = $this->initJwtToken();

    //     // Create specific data for jwt
    //     $info = 'Api jwt-'.$userId;
    //     $subject = 'Access API for user:'.$userId;
    //     $tokenJwt =  $jwtToken->createToken($userId, $info, $subject);
    //     Session::set('tokenJwt', $tokenJwt);

    //     return $tokenJwt;
    // }

    // //user model code....
    // public static function getAllUser()
    // {
    //     $data = self::table(self::$tableM)->select()->get();
    //     if($data) return $data;

    //     return null;
    // }

    // public static function getUserByEmail($email)
    // {
    //     $data = self::table(self::$tableM)->select([
    //                     'ulid',
    //                     'name',
    //                     'email',
    //                     'password',
    //                     'client_token',
    //                     'current_team_id',
    //                     'profile_photo_path',
    //                     'first_name',
    //                     'last_name',
    //                     'default_url'
    //                 ])
    //                 ->where('email', '=', $email)
    //                 ->whereAnd('status', '=', 1)
    //                 ->first();

    //     if($data) return $data;

    //     return null;
    // }

    // public static function getClientId($id, $columnId = 'id')
    // {
    //     $data = self::table(self::$tableM)->select(['client_token'])
    //             ->where($columnId, '=', $id)
    //             ->whereAnd('status', '=', 1)
    //             ->first();
    //     // \App\Core\Support\Log::debug($data, 'UserModel.getClientId');

    //     if ($data) {
    //         return $data->client_token;
    //     }

    //     return false;
    // }

    // public static function getUlid($id)
    // {
    //     $data = self::table(self::$tableM)->select(['ulid'])->where('id', '=', $id)->first();
    //     // \App\Core\Support\Log::debug($data, 'UserModel.getUlid');

    //     if ($data) {
    //         return $data->ulid;
    //     }

    //     return false;
    // }

    // public static function updateClientToken($columnId, $id)
    // {
    //     $token = generateRandomString();

    //     self::table(self::$tableM)->primaryKey($columnId);
    //     $update = self::table(self::$tableM)->updateWhere(['client_token' => $token], $columnId, $id);

    //     if (true === $update) {
    //         return $token;
    //     } else {
    //         return null;
    //     }
    // }


    protected function checkCredentials($user, $password): bool
    {
        if ($user) {
            if (Hash::matchPassword($password, $user->password)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * getAllData
     *
     * @param  mixed $id
     * @param  mixed $selectCols
     * @return void
     */
    public function getAllData($id = null, $selectCols = '*') {
        $id = $id ? [$id] : [];
        $sql = 'SELECT '.$selectCols.' FROM '.$this->table;
        $sql .= !empty($id) ? " WHERE id = ? LIMIT 1 ": " LIMIT 10";
        $result = $this->execQuery($sql, $id, false, !empty($id), empty($id));
        // dd($result, true);
        return $result;
    }

    public function seed() {        
        $permissions = [
            [1, 'Create Asset', 'asset-create'],
            [2, 'View Asset', 'asset-view'],
            // ... dst
        ];

        foreach ($permissions as $p) {
            $this->execQuery("CALL sp_upsert_permission(?, ?, ?)", $p);
        }

        // Mapping juga bisa dinamis
        $this->execQuery("CALL sp_assign_permission(?, ?)", [2, 1]); // Role 2, Perm 1
    }

    /**
     * Seeder Dinamis di PHP
     */
    public function syncPermissions(array $data) {
        foreach ($data as $item) {
            $sql = "INSERT INTO `permissions` (`id`, `name`, `slug`, `created_at`) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `slug` = VALUES(`slug`)";
            
            $this->execQuery($sql, [$item['id'], $item['name'], $item['slug']]);
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
                
        $result = $this->execQuery($sql, [$userId, $groupId], false, false, true);
        return array_column($result, 'permission_slug');
    }

    
}