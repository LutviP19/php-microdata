<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard\v1;


use PDO; // new PDO object
use App\Core\Database\Connection; // Uncomment to use custom DB connection

class StatsData extends DashboardData {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static $tableM = "salaries";

    public function __construct(?PDO $pdo = null, array $options = [])
    {
        // Global Set Custom connection
        $driver = 'mysql';
        $dbname = 'employees';
        $host = '127.0.0.1';
        $port = '3306';
        $username = 'root';
        $password = '';

        // dd($options);
        $pdo = $pdo ?? Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        parent::__construct($pdo);
        
        // Set default table
        $this->table = static::$tableM;
    }

    // public function getAllData($id = null, $selectCols = '*') {
    //     $id = $id ? [$id] : [];
    //     $sql = 'SELECT '.$selectCols.' FROM '.$this->table;
    //     $sql .= !empty($id) ? " WHERE id = ? LIMIT 1 ": " LIMIT 10 ";
    //     $result = self::table($this->table)->execQuery($sql, $id, false, !empty($id), empty($id));
    //     // dd($result, true);
    //     return $result;
    // }

    /**
     * Mengambil data secara dinamis dengan proteksi SQL injection pada limit.
     * @param int|string|null $id
     * @param int $limit
     * @param string $selectCols
     * @return array
     */
    public function getAllDataSalaries(int|string|null $id = null, int $limit = 10, string $selectCols = '*', $chunk = false) 
    {
        $params = $id ? [$id] : [];
        $sql = "SELECT {$selectCols} FROM {$this->table}";
        if (!empty($params)) {
            $sql .= " WHERE id = ? LIMIT 1";
        } else {
            $limit = (int) $limit;
            $sql .= " LIMIT {$limit}";
        }

        // dd($sql);
        // dd(empty($params));
        // return self::table($this->table)->execQuery(
        // dd($this->pdo->getAttribute(1001));
        return $this->execQuery(
            $sql, 
            $params, 
            false, 
            !empty($params), 
            empty($params),
            false,
            $chunk
        );
    }
}