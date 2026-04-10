<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard;


use PDO; // new PDO object

class SalaryData extends DashboardData {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static $tableM = "salaries";

    public function __construct(PDO $pdo = null)
    {        
        // Default connection
        parent::__construct($pdo);

        
        // // Set Custom PDO connection
        // $this->pdo = $pdo;
        
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