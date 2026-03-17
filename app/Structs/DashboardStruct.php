<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Structs;


use App\Core\Database\SchemaProperty;
use App\Models\CoreModel;
use PDO; // new PDO object
use App\Core\Database\Connection; // Uncomment to use custom DB connection

class DashboardStruct extends CoreModel {

   #[SchemaProperty(description: 'User display name', required: true, min: 3)]
    public string $username;

    #[SchemaProperty(description: 'Primary contact', required: true, email: true)]
    public string $email;

    #[SchemaProperty(description: 'User age', numeric: true, gte: 18, lte: 99)]
    public int $age;

    #[SchemaProperty(description: 'Website URL', custom: 'url')]
    public string $website;

    /**
     * static table name for this CoreModel.
     *
     * @var string
     */
    protected static $tableM = "users";
    // public static $tableM;

    public function __construct(PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $driver = 'mysql';
        // $name = 'test';
        // $host = '127.0.0.1';
        // $port = '3306';
        // $username = 'rooty';
        // $password = 'cccc';
        // $options = [];
        // $conn = $pdo ?: Connection::custom($driver, $name, $host, $port, $username, $password, $options);
        // parent::__construct($conn);
        // $this->setPDO($conn);
        
        // Default connection
        parent::__construct($pdo);

        // Set default table
        $this->table = self::$tableM;
    }

    public function getAllData($id = null, $selectCols = '*') {
        // $selectCols = $cols ?? '*';
        $id = $id ? [$id] : [];
        $sql = 'SELECT '.$selectCols.' FROM '.$this->table;
        $sql .= !empty($id) ? " WHERE id = ? LIMIT 1 ": "";
        $result = CoreModel::table($this->table)->execQuery($sql, $id, false, !empty($id), empty($id));
        // dd($result, true);
        return $result;
    }
}