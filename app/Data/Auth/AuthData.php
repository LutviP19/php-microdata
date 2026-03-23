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

    public function getAllData($id = null, $selectCols = '*') {
        // $selectCols = $cols ?? '*';
        $id = $id ? [$id] : [];
        $sql = 'SELECT '.$selectCols.' FROM '.$this->table;
        $sql .= !empty($id) ? " WHERE id = ? LIMIT 1 ": " LIMIT 10";
        $result = self::table($this->table)->execQuery($sql, $id, false, !empty($id), empty($id));
        // dd($result, true);
        return $result;
    }
}