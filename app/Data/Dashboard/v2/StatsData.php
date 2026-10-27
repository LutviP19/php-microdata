<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard\v2;


use App\Structs\Dashboard\v2\DashboardStruct;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class StatsData extends DashboardStruct {

    /**
     * static table name for this Class.
     *
     * @var string
     */
    protected static string $table = "salaries";

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

        // dd($options);
        $conn = $pdo ?? Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Set Custom Connection
        static::$staticPdo = $conn;

        // // Set default table
        // static::$table = "employees";

        // $this->limitToStream = 1000;
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
        // $params = [];
        $params = $id ? [$id] : [];
        // $sql = "SELECT {$selectCols} FROM {$this->table}";

        $sql = "SELECT 
                    e.emp_no,
                    CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                    e.gender,
                    d.dept_name,
                    t.title AS current_title,
                    s.salary AS current_salary,
                    s.from_date AS salary_effective_date,
                    sub.avg_salary_in_dept,
                    ROUND(s.salary - sub.avg_salary_in_dept, 2) AS salary_diff_from_dept_avg
                FROM employees e
                INNER JOIN salaries s 
                    ON e.emp_no = s.emp_no 
                    AND s.to_date = '9999-01-01' -- Mengambil gaji aktif/terakhir
                INNER JOIN dept_emp de 
                    ON e.emp_no = de.emp_no 
                    AND de.to_date = '9999-01-01' -- Mengambil departemen aktif
                INNER JOIN departments d 
                    ON de.dept_no = d.dept_no
                INNER JOIN titles t 
                    ON e.emp_no = t.emp_no 
                    AND t.to_date = '9999-01-01' -- Mengambil jabatan aktif
                INNER JOIN (
                    -- Subquery: Menghitung rata-rata gaji per departemen
                    SELECT 
                        de_sub.dept_no,
                        AVG(s_sub.salary) AS avg_salary_in_dept
                    FROM salaries s_sub
                    INNER JOIN dept_emp de_sub ON s_sub.emp_no = de_sub.emp_no
                    WHERE s_sub.to_date = '9999-01-01'
                    GROUP BY de_sub.dept_no
                ) sub ON d.dept_no = sub.dept_no
                WHERE s.salary > 50000
                ORDER BY s.salary DESC, e.hire_date ASC
                LIMIT  {$limit}";

        // if (!empty($params)) {
        //     $sql .= " WHERE id = ? LIMIT 1";
        // } else {
        //     $limit = (int) $limit;
        //     $sql .= " LIMIT {$limit}";
        // }

        // dd($sql);
        // dd(empty($params));
        // return self::table($this->table)->execQuery(
        // dd($this->pdo->getAttribute(1001));
        // return $this->execQuery(
        //     $sql, 
        //     $params, 
        //     false, 
        //     !empty($params), 
        //     empty($params),
        //     false,
        //     $chunk
        // );

        return static::rawQuery(
            $sql, 
            $params
        );
    }
}