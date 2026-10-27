<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard\v2;

use App\Structs\Dashboard\v2\DashboardStruct;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class EmployeeData extends DashboardStruct {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static string $table = "employees";

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

    public function __construct(?PDO $pdo = null, array $options = [])
    {
        // Global Set Custom connection
        // $driver = 'mysql';
        $driver = config("default_db");
        $dbname = 'employees';
        // $host = '127.0.0.1';
        $host = config("database.{$driver}.host");
        $port = config("database.{$driver}.port");
        $username = config("database.{$driver}.username");
        $password = (string) config("database.{$driver}.password");

        $conn = $pdo ?? Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);

        // Set Custom Connection
        static::$staticPdo = $conn;

        // // Set default table
        // static::$table = "employees";

        // $this->limitToStream = 1000;
    }

    public function getAllData($id = null, $selectCols = '*') {
        if(!is_null($id)) {
            // Logic to getAllData
            return static::select($selectCols)->where(static::$primaryKey, '=', $id)->limit(10)->get();
        }
            
        return static::select($selectCols)->limit(10)->get();
    }
}