<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Authlogin;


use App\Structs\Authlogin\AuthloginStruct;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2; // Uncomment to use custom DB connection
use App\Core\Support\Hash;

class AuthloginData extends AuthloginStruct
{
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
        $conn = $pdo ?? ConnectionV2::make(config('default_db'));

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
    public function getAllData($id = null, $selectCols = '*')
    {
        if (!is_null($id)) {
            // Logic to getUserProfile
            return static::select($selectCols)->where(static::$primaryKey, '=', $id)->limit(10)->get();
        }

        return static::select($selectCols)->limit(10)->get();
    }

    /**
     * checkCredentials
     */
    public function checkCredentials(?string $email, ?string $password): mixed
    {
        if(empty($email) || empty($password))
            return false;
        
        $user = static::where('email', '=', $email)->whereAnd('status', '=', true)->first();
        // dd($user);

        if (!empty($user)) {
            // Check ULID - Create if not exists
            if(empty($user->ulid)) {
                $newUlid = generateUlid();
                static::updateById($user->id, ['ulid' => $newUlid]);
                $user->ulid = $newUlid;
            }

            $hash = new Hash();
            if ($hash->matchPassword($password, $user->password)) {
                return $user;
            }
        }

        return false;
    }

}