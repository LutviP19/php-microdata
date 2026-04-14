<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard;


use PDO; // new PDO object
use App\Core\Database\Connection; // Uncomment to use custom DB connection

class EmployeeData extends DashboardData {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static $tableM = "employees";

    public function __construct(?PDO $pdo = null)
    {
        // Global Set Custom connection
        $driver = 'mysql';
        $dbname = 'employees';
        $host = '127.0.0.1';
        $port = '3306';
        $username = 'root';
        $password = '';
        $options = [];
        $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        parent::__construct($pdo);
        
        // Set default table
        $this->table = self::$tableM;
    }

    public function getAllData($id = null, $selectCols = '*') {
        $id = $id ? [$id] : [];
        $sql = 'SELECT '.$selectCols.' FROM '.$this->table;
        $sql .= !empty($id) ? " WHERE emp_no = ? LIMIT 1 ": " LIMIT 10";
        $result = self::table($this->table)->execQuery($sql, $id, false, !empty($id), empty($id));
        // dd($result, true);
        return $result;
    }
}