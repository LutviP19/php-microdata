<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Support\EncryptDecrypt;
use App\Core\Support\Session;
use App\Data\Auth\{AuthData};
use App\Core\Database\Connection;


class AuthModel extends AuthData
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
        

        // Use Default connection
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
        $mainData = new AuthData($conn);
        $statsData = new AuthData($conn);
        // dd($mainData->table);
        // dd($statsData->table);
        // dd($this->table);

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
        // $query = "SELECT * FROM ".$this->table." ORDER BY to_date DESC";
        // // Panggil fungsi paginate
        // $result = $mainData->paginate($query, [], $page, $limit);
        // dd($result, true);

        // // Test - Pagination 2
        // $page = $request['page'] ?? 1;
        // $limit = $request['limit'] ?? 10;
        // // $statsData->table = 'assets';
        // // Query dasar
        // $query = "SELECT * FROM ".$statsData->table." ORDER BY hire_date DESC";
        // // Panggil fungsi paginate
        // $result = $statsData->paginate($query, [], $page, $limit);
        // dd($result, true);
        
        
        // // Cache Query
        // $this->table = "assets";
        // $key = 'cache_index_'.$page;
        // $sql = 'SELECT * FROM '.$this->table.' LIMIT 10';
        // $cacheData = $mainData->getCachedData($key, $sql, [], 600);
        // dd($cacheData, true);


        // Cache data
        $cache = new \App\Core\Support\Cache();

        // Ambil data stats dari cache selama 5 menit
        $dataStats = $cache->remember('auth_index', function() use ($statsData) {
            return $statsData->getAllData();
        }, 300);

        // Ambil data paginate result dari cache selama 5 menit
        $dataAuth = $cache->remember('auth_result_a', function() use ($request, $mainData) {
            $page = $request['page'] ?? 1;
            $limit = $request['limit'] ?? 10;
            // $mainData->table = 'salaries';
            // Query dasar
            // $query = "SELECT * FROM assets WHERE deleted_at IS NULL ORDER BY created_at DESC";
            $query = "SELECT * FROM ".$this->table." ORDER BY updated_at DESC";
            return $mainData->paginate($query, [], $page, $limit);
        }, 300);

        $modelA = [
            // 'result' => $result,
            'request' => $request,
            'table' => $this->table,
            'title' => $request['title'] ?? 'Testing model',
            'stats_data' => $dataStats ?: null,
            'cache_data' => $dataAuth ?: null,

            // // Sample Errors - Default 417
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

        // $data = array_merge($data, $dataAuth);

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

        // // Generate random string
        // $rndString= generateRandomString(32, true, true);
        // die($rndString);

        // // Make Password from string
        // $password = \App\Core\Support\Hash::makePassword('password');
        // die($password);

        // // Test EncryptDecrypt
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

        // Set model Data
        $modelData = new AuthData();

        $modelA = [
            'request' => $request,
            'table' => $modelData->table,
            'data' => $modelData->getAllData($request['id']),
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
            'data' => $modelA,
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


        // // Hanya user dengan izin 'asset-delete' yang bisa lewat
        // \App\Core\Auth\Gate::authorize('asset-delete');
        // $model = new AssetModel();
        // if ($model->delete($id)) {
        //     return "Asset berhasil dihapus";
        // }

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
