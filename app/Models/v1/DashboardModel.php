<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models\v1;


use App\Core\Support\EncryptDecrypt;
use App\Core\Support\Session;
use App\Data\Dashboard\v1\{DashboardData, StatsData, EmployeeData};
use App\Core\Database\Connection;


class DashboardModel extends DashboardData
{
    public function __construct(?PDO $pdo = null)
    {
        // Use Default connection
        $conn = $pdo ?? Connection::make();
        parent::__construct($conn);
    }

    public function index(?array $request = [])
    {
        // Create instance
        $conn = null;
        $mainData = new EmployeeData($conn);
        $statsData = new StatsData($conn);


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

        // Test Session
        // Session::set('jwtId', generateUlid());
        // dd(Session::get('jwtId'));


        // // Test Set Custom connection
        // $driver = 'mysql';
        // $dbname = 'backend_php';
        // $host = '127.0.0.1';
        // $port = '3306';
        // $username = 'root';
        // $password = '';
        // $options = [];
        // $this->setPDO(null);
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);        
        // $this->setPDO($pdo);
        // $this->pdo = $pdo;
        // $this->table = "assets";
        // $selectCols = $cols ?? '*';
        // $sql = 'SELECT '.$selectCols.' FROM '.$this->table.' WHERE id = ? LIMIT 1';
        // $result = $this->execQuery($sql, [$id ?? 1], false, true, false);
        // dd($result, true);

        // dd($request, true);
        // dd(config('app.url'), true);


        // // Test - Pagination
        // $page = $request['page'] ?? 1;
        // $limit = $request['limit'] ?? 10;
        // // $mainData->table = 'assets';
        // // Query dasar
        // $query = "SELECT * FROM ".$this->table." ORDER BY hire_date DESC";
        // // Panggil fungsi paginate
        // // $mainData->limitToStream = 1000;
        // $result = $mainData->paginate($query, [], $page, $limit);
        // dd($result, true);

        // // Test - Pagination 2
        // $page = $request['page'] ?? 1;
        // $limit = $request['limit'] ?? 10;
        // // $statsData->table = 'assets';
        // // Query dasar
        // $query = "SELECT * FROM ".$statsData->table." ORDER BY to_date DESC";
        // // Panggil fungsi paginate
        // $result = $statsData->paginate($query, [], $page, $limit);
        // dd($result, true);
        
        
        // // Cache Query
        // $page = $request['page'] ?? 1;
        // $key = 'cache_index_'.$page;
        // $sql = 'SELECT * FROM '.$this->table.' LIMIT 10';
        // $cacheData = $mainData->getCachedData($key, $sql, [], 600);
        // dd($cacheData, true);

        // Cache data
        $cache = new \App\Core\Support\Cache();

        // Ambil data paginate result dari cache selama 5 menit
        $page = (int) ($request['page'] ?? 1);
        $limit = (int) ($request['limit'] ?? 10); // total data perpage

        // // // Ambil data stats dari cache selama 5 menit
        // $dataStats = $cache->remember("dashboard_stats:getAllDataSalaries:p{$page}:l{$limit}", function() use ($statsData) {
        //     return $statsData->getAllDataSalaries();
        // }, 300);
        // // dd($dataStats);


        // Testing Data
        // dd($this->getAllData(), true); // DashboardData::class
        // dd($mainData->getAllData(), true); // EmployeeData::class

        // Query Utama
        $query = "SELECT * FROM ".$mainData->table." ORDER BY hire_date DESC";

        // Set limit agar bisa auto Stream
        $mainData->limitToStream = 100;
        $dataDashboard = null;
        // Cek limit agar otomatis masuk ke Mode Stream
        if($limit > $mainData->limitToStream) {
            $dataDashboard = $mainData->paginate($query, [], $page, $limit);
        } else {            
            $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
            $queryString = md5((string) $cleanQuery);
            $cacheKeyId = "dashboard_data:{$mainData->table}:" . $queryString . ":p{$page}:l{$limit}";

            $dataDashboard = $cache->remember($cacheKeyId, fn() => $mainData->paginate($query, [], $page, $limit), 300);
        }

        $modelA = [
            // 'result' => $result,
            'request' => $request,
            'table' => $this->table,
            'title' => $request['title'] ?? 'Testing model v1',
            // 'stats_data' => $dataStats ?: null,
            'pagination_data' => $dataDashboard, // Ini mode cache
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing index v1',
        ];
        // $data = array_merge($data, $dataDashboard);


        // Output Stream
        if($limit > $mainData->limitToStream) {
            $realData = $dataDashboard['data'];
            $paginationMeta = $dataDashboard['meta'];
            
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
        $modelA = [
            'request' => $request ?? [],
            'table' => $this->table,
            'title' => $request['title'] ?? 'Testing store'
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
        $modelData = new EmployeeData();

        $modelA = [
            'request' => $request,
            'table' => $modelData->table,
            'data' => $modelData->getAllData($request['id']),
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
        $modelA = [
            'request' => $request ?? [],
            'table' => $this->table,
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
        $modelA = [
            'request' => $request ?? [],
            'table' => $this->table,
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
