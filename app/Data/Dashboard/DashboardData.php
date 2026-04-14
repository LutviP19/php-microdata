<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard;


use App\Structs\Dashboard\DashboardStruct;
use PDO; // new PDO object
use App\Core\Database\Connection; // Uncomment to use custom DB connection

class DashboardData extends DashboardStruct {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static $tableM = "employees";

    public function __construct(?PDO $pdo = null)
    {        
        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);

        
        // Set PDO connection
        $this->pdo = $conn;

        // Set default table
        $this->table = self::$tableM;
    }

    public function getAllData($id = null, $selectCols = '*') { 
        $id = $id ? [$id] : [];
        $sql = 'SELECT '.$selectCols.' FROM '.$this->table;
        $sql .= !empty($id) ? " WHERE id = ? LIMIT 1 ": " LIMIT 10";
        $result = self::table($this->table)->execQuery($sql, $id, false, !empty($id), empty($id));
        // dd($result, true);
        return $result;
    }
}