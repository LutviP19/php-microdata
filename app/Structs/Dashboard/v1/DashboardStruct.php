<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Structs\Dashboard\v1;


use App\Core\Database\SchemaProperty;
use App\Models\BaseModel;
use PDO; // new PDO object
use App\Core\Database\Connection; // Uncomment to use custom DB connection

class DashboardStruct extends BaseModel {

   #[SchemaProperty(description: 'User display name', required: true, min: 3)]
    public string $username;

    #[SchemaProperty(description: 'Primary contact', required: true, email: true)]
    public string $email;

    #[SchemaProperty(description: 'User age', numeric: true, gte: 18, lte: 99)]
    public int $age;

    #[SchemaProperty(description: 'Website URL', custom: 'url')]
    public string $website;

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
    }
}