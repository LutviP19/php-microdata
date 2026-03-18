<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Support\EncryptDecrypt;
use App\Core\Support\Session;
use App\Data\Dashboard\DashboardData;
use App\Core\Database\Connection; // Uncomment to use custom DB connection


class DashboardModel extends DashboardData
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
        $mainData = new DashboardData($conn);
        // dd($mainData->table);

        // // Middleware - Rate limiter
        // $identifier = 'Dashboard-Index-'.\clientIP();
        // $perSeconds = 6000;
        // $mainData->setRatelimiter($identifier, $perSeconds, 3);

        // // Test execQuery
        // $sql = 'SELECT * FROM '.$this->table.' LIMIT 10';
        // $data = $mainData::table($this->table)->execQuery($sql, [], false, false, true);
        // dd($data);

        // // Testing Regenerate SessioId
        // $oldSessionId = session_id();
        // $headers = bp_session_regenerate_id($oldSessionId);
        // setHeaders($headers);

        // // Test Session
        // // Session::set('jwtId', generateUlid());
        // dd(Session::get('jwtId'));


        // // Test change table
        // $this->table = "assets";
        // $selectCols = $cols ?? '*';
        // $sql = 'SELECT '.$selectCols.' FROM '.$this->table.' WHERE id = ? LIMIT 1';
        // $result = DashboardData::table($this->table)->execQuery($sql, [$id ?? 1], false, true, false);
        // dd($result, true);

        // dd($request, true);
        // dd(config('app.url'), true);


        // // Test - Pagination
        // $page = $request['page'] ?? 1;
        // $limit = $request['limit'] ?? 10;
        // // $mainData->table = 'assets';
        // // Query dasar
        // // $query = "SELECT * FROM assets WHERE deleted_at IS NULL ORDER BY created_at DESC";
        // $query = "SELECT * FROM assets ORDER BY created_at DESC";
        // // Panggil fungsi paginate
        // $result = $mainData->paginate($query, [], $page, $limit);
        // dd($result, true);
        
        
        // // Cache Query
        // $this->table = "assets";
        // $key = 'cache_index_'.$page;
        // $sql = 'SELECT * FROM '.$this->table.' LIMIT 10';
        // $cacheData = $mainData->getCachedData($key, $sql, [], 600);
        // dd($cacheData, true);


        // Cache data
        $cache = new \App\Core\Support\Cache();
        // Ambil data chart dari cache selama 5 menit
        $dataDashboard = $cache->remember('dashboard_index', function() use ($mainData) {
            return $mainData->getAllData();
        }, 300);

        $modelA = [
            // 'result' => $result,
            'request' => $request,
            'table' => $this->table,
            'cache_data' => $dataDashboard,
            'title' => $request['title'] ?? 'Testing model',

            // // Sample Errors
            // 'errors' => [
            //     // 'status' => 500,
            //     // 'message' => $message ?? 'Errors occured.',
            //     'path' => 'Path not found',
            // ]
        ];

        $data = [            
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing index',
        ];

        return handle_response_error($data, $modelA);
    }

    public function store(?array $request = [])
    {
        // // Test Error
        // $status = 400;
        // $message = 'Invalid input store.';

        $modelA = [
            'request' => $request ?? [],
            'table' => $this->table,
            'title' => $request['title'] ?? 'Testing store',

            // // Sample Errors
            // 'errors' => [
            //     'status' => $status ?? 500,
            //     'message' => $message ?? 'Errors occured.',
            //     'path' => 'Path not found',
            //     'input_a' => 'This field is required.',
            // ]
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 201,
            'message' => $message ?? 'testing store',
        ];

        return handle_response_error($data, $modelA);
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

        // // Test Error
        // $status = 400;
        // $message = 'Invalid id.';

        $modelA = [
            'table' => $this->table,
            'data' => (new DashboardData())->getAllData($request['id']),
            'title' => $request['title'] ?? 'Edit model',

            // // Sample Errors
            // 'errors' => [
            //     'status' => $status ?? 500,
            //     'message' => $message ?? 'Errors occured.',
            //     'path' => 'Path not found',
            //     'input_id' => 'This field is required.',
            // ]
        ];

        $data = [            
            'data' => array_merge($modelA, $request),
            'status' => $status ?? 200,
            'message' => $message ?? 'testing edit',
        ];

        return handle_response_error($data, $modelA);
    }

    public function update(?array $request = [])
    {
        // // Test Error
        // $status = 400;
        // $message = 'Invalid input update.';

        $modelA = [
            'request' => $request ?? [],
            'table' => $this->table,
            'title' => $request['title'] ?? 'Testing update',

            // // Sample Errors
            // 'errors' => [
            //     'status' => $status ?? 500,
            //     'message' => $message ?? 'Errors occured.',
            //     'path' => 'Path not found',
            //     'input_a' => 'This field is required.',
            // ]
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 201,
            'message' => $message ?? 'testing update',
        ];

        return handle_response_error($data, $modelA);
    }

    public function destroy(?array $request = [])
    {
        // // Test Error
        // $status = 400;
        // $message = 'Invalid id.';

        $modelA = [
            'request' => $request ?? [],
            'table' => $this->table,
            'title' => $request['title'] ?? 'Destroy model',

            // // Sample Errors
            // 'errors' => [
            //     'status' => $status ?? 500,
            //     'message' => $message ?? 'Errors occured.',
            //     'path' => 'Path not found',
            //     'input_id' => 'This field is required.',
            // ]
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing destroy',
        ];

        return handle_response_error($data, $modelA);
    }
}
