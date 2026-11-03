<?php

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;

use App\Data\Authlogin\{AuthloginData};
use PDO; // new PDO object
use App\Core\Database\ConnectionV2;
use App\Core\Support\Session;
use App\Core\Auth\Gate;

class AuthLoginModel extends AuthloginData
{
    public function __construct(?PDO $pdo = null)
    {
        // Use Default connection
        $conn = $pdo ?? ConnectionV2::make(config('default_db'));
        parent::__construct($conn);

        // Middleware - Model for API Only
        if (!is_json_request() || !expects_json()) {
            dd('KO');
        }

        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $ignoreMethod = ['POST'];
        if(!in_array($requestMethod, $ignoreMethod)) {
            // Validate TokenAuth
            Gate::authorizeSodiumV4();
        }
        // End Middleware
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
        $dataStats = $cache->remember('auth_index', fn () => $statsData->getAllData(), 300);

        // Query dasar
        $query = "SELECT * FROM ".$mainData::getTableName()." ORDER BY updated_at DESC";


        // Set limit agar bisa auto Stream
        $mainData->limitToStream = 2;
        $dataAuth = null;
        // Cek limit agar otomatis masuk ke Mode Stream
        if ($limit > $mainData->limitToStream) {
            $dataAuth = $mainData->paginate($page, $limit);
        } else {
            $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
            $queryString = md5((string) $cleanQuery);
            $cacheKeyId = "auth_data:{$mainData::getTableName()}:" . $queryString . ":p{$page}:l{$limit}";
            $dataAuth = $cache->remember($cacheKeyId, fn () => $mainData->paginate($page, $limit), 300);
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
        if ($limit > $mainData->limitToStream) {
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
        // Middleware
        setRatelimiter('store_AuthLoginModel', 600, 5);

        // Set model Data
        $modelData = new AuthloginData();
        $headers = [];
        $token = null;

        $email = $request['email'];
        $password = $request['password'];
        $callback = $modelData->checkCredentials($email ?? null, $password ?? null);

        if (false === $callback) {
            $modelA = [
                'errors' => [
                    'status' => 401,
                    'message' => 'Invalid Credentials!'
                ]
            ];
        } else {
            $token = $this->setLoginSession($callback, isset($_REQUEST['refresh_token']));

            if(!is_null($token) && Session::has('uid') && Session::get('uid') === $callback->ulid) {
                // Set cookie
                $sessionName = config("session.sess_name");
                $sessionId = trim(Session::get('sessionId'));
                $sessionExp = config('session.exptime');
                $userId = Session::get('uid');

                // Refresh Token ID
                $expTokenRefresh = (int) config("app.refresh_timeout");
                $refreshTokenTimeout = days_to_seconds($expTokenRefresh);
                $refreshTokenKey = str_replace('session', 'refresh_token', sessionKeyFormat($userId, $sessionId));
                $refreshTokenId = encryptData($refreshTokenKey);
                
                if ($sessionId !== "") {
                    $headers = [
                        'Set-Cookie' => [
                            "{$sessionName}={$sessionId}; Max-Age={$sessionExp}; Path=/; SameSite=Lax; HttpOnly",
                            "user_id={$refreshTokenId}; Max-Age={$refreshTokenTimeout}; Path=/; SameSite=Lax"
                        ]
                    ];
                }
            }

            $modelA = [
                'account' => Session::all(),
                'token' => $token,
            ];
        }

        $data = [
            'data' => $modelA,
            'status' => $status ?? 201,
            'message' => $message ?? 'auth_login',
        ];

        return handle_response_error($data, $modelA, $headers);
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
        // Middleware
        setRatelimiter('update_AuthLoginModel', 300, 5);

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
        // Middleware
        setRatelimiter('destroy_AuthLoginModel', 900, 3);

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
