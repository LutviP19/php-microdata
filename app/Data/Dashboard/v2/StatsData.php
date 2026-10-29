<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Data\Dashboard\v2;


use App\Structs\Dashboard\v2\DashboardStruct;
// use App\Core\Database\QueryBuilderV2 as QueryBuilder; // Uncomment to use QueryBuilder
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class StatsData extends DashboardStruct {

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

        // dd($options);
        $conn = $pdo ?? Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Set Custom Connection
        static::$staticPdo = $conn;

        // // Set default table
        // static::$table = "employees";

        // $this->limitToStream = 1000;
    }

    /**
     * getAllData
     *
     * @param  mixed $id
     * @param  mixed $selectCols
     * @return void
     */
    public function getAllData($id = null, $selectCols = '*') {
        if(!is_null($id)) {
            // Logic to getUserProfile
            return static::select($selectCols)->where(static::$primaryKey, '=', $id)->limit(10)->get();
        }
            
        return static::select($selectCols)->limit(10)->get();
    }

    /**
     * Fetch data dynamically with SQL injection protection at limits.
     * @param int|string|null $id
     * @param int $limit
     * @param string $selectCols
     * @return array
     */
    public function getAllDataSalaries(int|string|null $id = null, int $limit = 10, string $selectCols = '*', bool $chunk = false) 
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

        // dd($sql);
        // dd(empty($params));

        return static::rawQuery(
            $sql, 
            $params
        );
        
        // // Example Use QueryBuilder
        // return self::select([
        //             "employees.emp_no",
        //             QueryBuilder::raw("CONCAT(employees.first_name, ' ', employees.last_name) AS full_name"),
        //             "employees.gender",
        //             "departments.dept_name",
        //             QueryBuilder::raw("titles.title AS current_title"),
        //             QueryBuilder::raw("salaries.salary AS current_salary"),
        //             QueryBuilder::raw("salaries.from_date AS salary_effective_date"),
        //             "sub.avg_salary_in_dept",
        //             QueryBuilder::raw("ROUND(salaries.salary - sub.avg_salary_in_dept, 2) AS salary_diff_from_dept_avg")
        //         ]
        //     )
        //     ->join('salaries', 'employees.emp_no', '=', QueryBuilder::raw("salaries.emp_no AND salaries.to_date = '9999-01-01'"), 'INNER', true)
        //     ->join('dept_emp', 'employees.emp_no', '=', QueryBuilder::raw("dept_emp.emp_no AND dept_emp.to_date = '9999-01-01'"), 'INNER', true)
        //     ->join('departments', 'departments.dept_no', '=', 'dept_emp.dept_no', 'INNER')
        //     ->join('titles', 'employees.emp_no', '=', QueryBuilder::raw("titles.emp_no AND titles.to_date = '9999-01-01'"), 'INNER', true)
        //     ->join(
        //             // -- Subquery: Menghitung rata-rata gaji per departemen
        //             QueryBuilder::raw("(
        //                     SELECT 
        //                         de_sub.dept_no,
        //                         AVG(s_sub.salary) AS avg_salary_in_dept
        //                     FROM salaries s_sub
        //                     INNER JOIN dept_emp de_sub ON s_sub.emp_no = de_sub.emp_no
        //                     WHERE s_sub.to_date = '9999-01-01'
        //                     GROUP BY de_sub.dept_no
        //                 ) sub
        //             "),
        //             'departments.dept_no', '=', 'sub.dept_no', 'INNER', true)
        //     ->where('salaries.salary', '>', 50000)
        //     ->orderBy('salaries.salary', 'DESC')
        //     ->orderBy('employees.hire_date', 'ASC')
        //     ->limit($limit)
        //     ->get();
    }
}