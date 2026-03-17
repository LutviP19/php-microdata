<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Support\EncryptDecrypt;
use App\Core\Support\Session;
use App\Structs\DashboardStruct;
use App\Core\Database\Connection; // Uncomment to use custom DB connection


class DashboardModel extends DashboardStruct
{

    public function __construct(PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $driver = 'mysql';
        // $dbname = 'test';
        // $host = '127.0.0.1';
        // $port = '3306';
        // $username = 'rootx';
        // $password = 'aa';
        // $options = [];
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        

        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);

        // Set PDO connection
        $this->pdo = $conn;
    }

    public function index(?array $request = [])
    {
        $conn = null;
        // // Use Custom connection
        // $driver = 'mysql';
        // $dbname = 'new_dbx';
        // $host = 'localhost';
        // $port = '3306';
        // $username = 'root';
        // $password = '';
        // $options = [];
        // $conn = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        // // dd($dbname);

        // Create instance
        $structDB = new DashboardStruct($conn);
        // dd($structDB->table);

        // // Middleware - Rate limiter
        // $identifier = 'Dashboard-Index-'.\clientIP();
        // $perSeconds = 6000;
        // $structDB->setRatelimiter($identifier, $perSeconds, 3);

        // // Test execQuery
        // $sql = 'SELECT * FROM '.$this->table.' LIMIT 10';
        // $data = $structDB::table($this->table)->execQuery($sql, [], false, false, true);
        // dd($data);

        // // Testing Regenerate SessioId
        // $oldSessionId = session_id();
        // $headers = bp_session_regenerate_id($oldSessionId);
        // setHeaders($headers);

        // // Test Session
        // // Session::set('jwtId', generateUlid());
        // dd(Session::get('jwtId'));
        

        // $selectCols = $cols ?? '*';
        // $sql = 'SELECT '.$selectCols.' FROM '.$this->table.' WHERE id = ? LIMIT 1';
        // $result = DashboardStruct::table($this->table)->execQuery($sql, [$id ?? 1], false, true, false);
        // dd($result, true);

        // dd($request, true);
        // dd(config('app.url'), true);
        

        $modelA = [
            'request' => $request,
            'table' => $this->table,
            'data' => $structDB->getAllData(),
            'title' => $request['title'] ?? 'Testing model',
        ];

        $data = [            
            'data' => $modelA,
            // 'errors' => $errors ?? [],
            'status' => $status ?? 200,
            // 'message' => $message ?? 'testing index',
        ];

        return $data;
    }

    public function store(?array $request = [])
    {
        // $errors = [
        //     'input_a' => 'This field is required.',
        // ];
        // $status = 400;
        // $message = 'Invalid input store.';

        $data = [
            'data' => $request ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing store',
        ];

        return $data;
    }

    public function edit(?array $request = [])
    {
        // // Generate random App Key
        // $appKey = generateAppKey();
        // die($appKey);

        // Test EncryptDecrypt
        // $enc = new EncryptDecrypt(); // Otomatis ambil dari Config::get('app.key')

        // // Enkripsi
        // // $encrypted = $enc->encrypt(['id' => 123, 'secret' => 'rahasia']);
        // $encrypted = encryptData(['id' => 123, 'secret' => 'rahasia']);
        // // die($encrypted); // Output: Base64 string panjang

        // // // Dekripsi
        // // $decrypted = $enc->decrypt($encrypted);
        // $decrypted = decryptData($encrypted);
        // // dd($decrypted, true); // Output: Array ( [id] => 123 [secret] => rahasia )

        // // Match data
        // $match = matchEncryptedData($decrypted, $encrypted);
        // dd($match);
        

        $modelA = [
            'table' => $this->table,
            'data' => (new DashboardStruct())->getAllData(1),
            'title' => $request['title'] ?? 'Edit model',
        ];

        $data = [            
            'data' => array_merge($modelA, $request),
            'errors' => $errors ?? [],
            'status' => $status ?? 200,
            'message' => $message ?? 'testing edit',
        ];

        return $data;
    }

    public function update(?array $request = [])
    {
        $data = [
            'data' => $request ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing update',
        ];

        return $data;
    }

    public function destroy(?array $request = [])
    {
        $data = [
            'data' => $request ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 200,
            'message' => $message ?? 'testing destroy',
        ];

        return $data;
    }
}
