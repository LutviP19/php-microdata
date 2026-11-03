<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Structs\Authlogin;


use App\Core\Database\SchemaProperty;
use App\Core\Database\BaseModelV2;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2; // Uncomment to use custom DB connection

class AuthloginStruct extends BaseModelV2 {

    #[SchemaProperty(description: 'User increment id', omitempty: true, numeric: true)]
    public string $id;

    #[SchemaProperty(description: 'User Session uid', omitempty: true, max: 26)]
    public string $uid;

    #[SchemaProperty(description: 'User ULID', omitempty: true, max: 26)]
    public string $ulid;

    #[SchemaProperty(description: 'User display name', omitempty: true, min: 3, max: 255)]
    public string $name;

    #[SchemaProperty(description: 'Primary email address', required: true, email: true)]
    public string $email;

    #[SchemaProperty(description: 'User password', required: true, min:8, max: 255)]
    public string $password;

    #[SchemaProperty(description: 'User Refresh Token', omitempty: true, min: 26)]
    public string $refresh_token;
    

    public function __construct(?PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $pdo = ConnectionV2::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        $conn = $pdo ?? ConnectionV2::make(config('default_db'));
        parent::__construct($conn);       
    }
}