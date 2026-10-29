<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Data\Authlogin\{AuthloginData};
use App\Core\Database\Connection;
use App\Core\Support\EncryptDecrypt;
use App\Core\Support\Session;
use App\Core\Auth\{BaseAuth, SodiumAuthV4};


class AuthLoginModel extends AuthloginData
{

    public function __construct(?PDO $pdo = null)
    {
        // Use Default connection
        $conn = $pdo ?? Connection::make();
        parent::__construct($conn);

        // Middleware - Model for API Only
        if(!is_json_request() || !expects_json()) {
            dd('KO');
        }
    }

    public function index(?array $request = [])
    {
        // Create instance
        $conn = null;
        $mainData = new AuthloginData($conn);
        $statsData = new AuthloginData($conn);
        
        // Cache data
        $cache = new \App\Core\Support\Cache();

        // Ambil data untuk paginate result
        $page = (int) ($request['page'] ?? 1);
        $limit = (int) ($request['limit'] ?? 10); // total data perpage


        // Ambil data stats dari cache selama 5 menit
        $dataStats = $cache->remember('auth_index', fn() => $statsData->getAllData(), 300);

        // Query dasar
        $query = "SELECT * FROM ".$modelData::getTableName()." ORDER BY updated_at DESC";

        
        // Set limit agar bisa auto Stream
        $mainData->limitToStream = 2;
        $dataAuth = null;
        // Cek limit agar otomatis masuk ke Mode Stream
        if($limit > $mainData->limitToStream) {            
            $dataAuth = $mainData->paginate($query, [], $page, $limit);
        } else {
            $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
            $queryString = md5((string) $cleanQuery);
            $cacheKeyId = "auth_data:{$mainData::getTableName()}:" . $queryString . ":p{$page}:l{$limit}";
            $dataAuth = $cache->remember($cacheKeyId, fn() => $mainData->paginate($query, [], $page, $limit), 300);
        }

        $modelA = [
            // 'result' => $result,
            'request' => $request,
            'table' => $mainData::getTableName(),
            'title' => $request['title'] ?? 'Testing model',
            'stats_data' => $dataStats ?: null,
            'pagination_data' => $dataAuth, // Ini mode cache
        ];

        $data = [            
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing index',
        ];
        // $data = array_merge($data, $dataAuth);

        // Output Stream
        if($limit > $mainData->limitToStream) {
            $realData = $dataAuth['data'];
            $paginationMeta = $dataAuth['meta'];
            
            unset($modelA['pagination_data']);
            return json_response_stream(
                $data['status'], 
                $data['message'],
                $realData, 
                $paginationMeta,
                $modelA
            );
        }


        // Return normal 
        return handle_response_error($data, $modelA);
    }

    public function store(?array $request = [])
    {
        // Set model Data
        $modelData = new AuthloginData();

        $email = $request['email'];
        $password = $request['password'];
        // extract($request);
        $callback = $modelData->checkCredentials($email ?? null, $password ?? null);
        // dd($callback);

        if(false === $callback) {
            $modelA = [
                'errors' => [
                    'status' => 401,
                    'message' => 'Invalid Credentials!'
                ]
            ];
        } else {
            // Save session
            foreach($callback as $key => $item) {
                $item = $key === 'id' ? encryptData($item) : $item;
                $key = $key === 'ulid' ? 'uid' : $key;
                Session::set($key, $item);
            }

            // Generate token
            $authV4 = new SodiumAuthV4();
            $expToken = time() + (60 * config('session.lifetime'));
            $token = $authV4->encode(['uid' => $callback->ulid, 'role' => 'admin', 'exp' => $expToken]);
            // dd($token);

            $modelA = [
                // 'request' => $request ?? [],                
                // 'table' => $modelData::getTableName(),
                // 'title' => $request['title'] ?? 'Auth Login',
                'account' => Session::all(),
                'token' => $token,
            ];
        }

        $data = [
            'data' => $modelA,
            'status' => $status ?? 201,
            'message' => $message ?? 'auth_login',
        ];

        return handle_response_error($data, $modelA);
    }

    public function edit(?array $request = [])
    {
        // Set model Data
        $modelData = new AuthloginData();

        $modelA = [
            'request' => $request,
            'table' => $modelData::getTableName(),
            'data' => $modelData->getAllData($request['id']) ?: null,
            'title' => $request['title'] ?? 'Edit model'
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
        // Set model Data
        $modelData = new AuthloginData();

        $modelA = [
            'request' => $request ?? [],
            'table' => $modelData::getTableName(),
            'title' => $request['title'] ?? 'Testing update'
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
        // Set model Data
        $modelData = new AuthloginData();

        $modelA = [
            'request' => $request ?? [],
            'table' => $modelData::getTableName(),
            'title' => $request['title'] ?? 'Destroy model'
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing destroy',
        ];

        return handle_response_error($data, $modelA);
    }
}
