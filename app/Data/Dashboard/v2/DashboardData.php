<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard\v2;


use App\Structs\Dashboard\v2\DashboardStruct;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class DashboardData extends DashboardStruct {

    /**
     * Target database table
     */
    protected static string $table = 'users';

    /**
     * Primary key column name
     */
    protected static string $primaryKey = 'id';

    /**
     * Enable or disable Soft Deletes
     */
    protected static bool $useSoftDeletes = false;

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

    public function getAllData($id = null, $selectCols = '*') { 
        if(!is_null($id)) {
            // Logic to getAllData
            return static::select($selectCols)->where(static::$primaryKey, '=', $id)->limit(10)->get();
        }

        return static::select($selectCols)->limit(10)->get();
    }
}