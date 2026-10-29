<?php

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models\v2;

// use App\Core\Support\EncryptDecrypt;
// use App\Core\Support\Session;
use App\Data\Dashboard\v2\{DashboardData, StatsData, EmployeeData};
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection
use PDO;

class DashboardModel 
{
    /**
     * PDO properties for this instance (Instance-based)
     *
     */
    protected ?PDO $conn = null;

    public function __construct(?PDO $pdo = null)
    {
        // Use Default connection
        $this->conn = $pdo ?? Connection::make(config('default_db'));
    }

    public function index(?array $request = [])
    {
        // Create instance
        $dashboardData = new DashboardData($this->conn);
        $mainData = new EmployeeData(null);
        $statsData = new StatsData(null);

        $dataDashboard = null;
        $dataMain = null;
        $dataStats = null;


        // $dataDashboard = $dashboardData->getAllData();
        // dd($dataDashboard, true);

        // // Middleware - Rate limiter
        // $identifier = 'Dashboard-Index-'.\clientIP();
        // $perSeconds = 6000;
        // $mainData->setRatelimiter($identifier, $perSeconds, 3);

        // // Test execQuery
        // $sql = 'SELECT * FROM '.static::getTableName().' LIMIT 10';
        // $data = $mainData::table(static::getTableName())->execQuery($sql, [], false, false, true);
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
        // $port = '3307';
        // $username = 'root';
        // $password = 'password123';
        // $options = [];
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        // $dashboardData = new DashboardData($pdo);
        // $dashboardData->setPDO($pdo);
        // $table = "assets";
        // $selectCols = $cols ?? '*';
        // $sql = 'SELECT '.$selectCols.' FROM '.$table.' WHERE id = ? LIMIT 1';
        // $result = $dashboardData->rawQuery($sql, [$id ?? 1]);
        // dd($result, true);

        // dd($request, true);
        // dd(config('app.url'), true);


        // // Test - Pagination
        // $page = $request['page'] ?? 1;
        // $limit = $request['limit'] ?? 10;
        // // $mainData->table = 'assets';
        // // Query dasar
        // $query = "SELECT * FROM ".$mainData::getTableName()." ORDER BY hire_date DESC";
        // // Panggil fungsi paginate
        // // $mainData->limitToStream = 1000;
        // $result = $mainData->paginate($limit, $page);
        // dd($result, true);

        // // Test - Pagination 2
        // $page = $request['page'] ?? 1;
        // $limit = $request['limit'] ?? 10;
        // // $statsData->table = 'assets';
        // // Query dasar
        // $query = "SELECT * FROM ".$statsData::getTableName()." ORDER BY to_date DESC";
        // // Panggil fungsi paginate
        // $result = $statsData->paginate($limit, $page);
        // dd($result, true);


        // // Cache Query
        // $page = $request['page'] ?? 1;
        // $key = 'cache_index_'.$page;
        // $sql = 'SELECT * FROM '.static::getTableName().' LIMIT 10';
        // $cacheData = $mainData->getCachedData($key, $sql, [], 600);
        // dd($cacheData, true);

        // Cache data
        $cache = new \App\Core\Support\Cache();

        // Ambil data paginate result dari cache selama 5 menit
        $page = (int) ($request['page'] ?? 1);
        $limit = (int) ($request['limit'] ?? 10); // total data perpage


        // Testing Data
        // dd($dashboardData->getAllData()); // DashboardData::class
        // dd($mainData->getAllData()); // EmployeeData::class
        // dd($statsData->getAllDataSalaries()); // statsData::class

        // // Query Utama
        // $query = "SELECT * FROM ".$mainData::getTableName()." ORDER BY hire_date DESC";
        // // dd($query);

        // Set limit agar bisa auto Stream
        $mainData->limitToStream = 25;      

        // $mainData::rawQuery($query); // Using Raw SQL
        $mainData->orderBy('hire_date', 'DESC');

        // Cek limit agar otomatis masuk ke Mode Stream
        if ($limit > $mainData->limitToStream) {
            $dataMain = $mainData->paginate($limit, $page);
            $dataStats = $statsData->getAllDataSalaries();
        } else {
            $cleanQuery = preg_replace('/\s+/', ' ', trim($mainData->toSql()));
            $queryString = md5((string) $cleanQuery);
            $cacheKeyId = "dashboard_data:{$mainData::getTableName()}:" . $queryString . ":p{$page}:l{$limit}";

            $dataMain = $cache->remember($cacheKeyId, fn () => $mainData->paginate($limit, $page), 300);

            // Ambil data stats dari cache selama 5 menit
            $dataStats = $cache->remember("dashboard_stats:getAllDataSalaries:p{$page}:l{$limit}", function() use ($statsData) {
                return $statsData->getAllDataSalaries();
            }, 300);
            // dd($dataStats);
        }

        $modelA = [
            // 'result' => $result,
            'request' => $request,
            'table' => $mainData::getTableName(),
            'title' => $request['title'] ?? 'Testing model v1',
            'stats_data' => $dataStats,
            'pagination_data' => $dataMain, // Ini mode cache
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing index v2',
        ];
        // $data = array_merge($data, $dataMain);


        // Output Stream
        if (!is_null($dataMain) && $limit > $mainData->limitToStream) {
            $realData = $dataMain['data'];
            $paginationMeta = $dataMain['meta'];

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
        $modelData = new DashboardData($this->conn);

        $modelA = [
            'request' => $request ?? [],
            'table' => $modelData::getTableName(),
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
        // Set model Data
        $modelData = new DashboardData($this->conn);

        $modelA = [
            'request' => $request,
            'table' => $modelData::getTableName(),
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
        // Set model Data
        $modelData = new DashboardData($this->conn);

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
        $modelData = new DashboardData($this->conn);

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
