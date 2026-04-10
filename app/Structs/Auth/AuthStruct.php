<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Structs\Auth;


use App\Core\Database\SchemaProperty;
use App\Models\BaseModel;
use PDO; // new PDO object
use App\Core\Database\Connection; // Uncomment to use custom DB connection

class AuthStruct extends BaseModel {

    // #[SchemaProperty(description: 'User increment id', required: true, numeric: true)]
    // public string $id;

    #[SchemaProperty(description: 'User ULID id', omitempty: true, max: 26)]
    public string $ulid;

    #[SchemaProperty(description: 'User display name', required: true, min: 3, max: 255)]
    public string $name;

    #[SchemaProperty(description: 'Primary email address', required: true, email: true)]
    public string $email;

    #[SchemaProperty(description: 'User password', required: true, min:8, max: 255)]
    public string $password;

// Optional
    #[SchemaProperty(description: 'User status boolean', omitempty: true, boolean: true)]
    public int $status;

    #[SchemaProperty(description: 'Client token for API', omitempty: true, min:3, max:100)]
    public string $client_token;

    #[SchemaProperty(description: 'User token for remember login', omitempty: true, min:3, max:100)]
    public string $remember_token;

    // #[SchemaProperty(description: 'User team id', omitempty: true, gte:1)]
    // public int $current_team_id;

    #[SchemaProperty(description: 'User bio first name', omitempty: true, min:3, max:100)]
    public string $first_name;

    #[SchemaProperty(description: 'User bio last name', omitempty: true, max:100)]
    public string $last_name;

    #[SchemaProperty(description: 'User bio phone', omitempty: true, max:30)]
    public string $phone;

    #[SchemaProperty(description: 'User bio address line1', omitempty: true, max:150)]
    public string $address_line1;

    #[SchemaProperty(description: 'User bio address line2', omitempty: true, max:200)]
    public string $address_line2;

    #[SchemaProperty(description: 'User bio city', omitempty: true, max:100)]
    public string $city;

    #[SchemaProperty(description: 'User Profile URL', omitempty: true, custom: 'url')]
    public string $default_url;

    public function __construct(PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);
        
        // Set PDO connection
        $this->pdo = $conn;
    }
}