<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Support\EncryptDecrypt;
use App\Core\Support\Session;
use App\Data\Dashboard\{DashboardData, StatsData, EmployeeData, SalaryData};
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


        // Cache data
        $cache = new \App\Core\Support\Cache();

        // Ambil data untuk paginate result
        $page = $request['page'] ?? 1;
        $limit = $request['limit'] ?? 10; // total data perpage


        // Ambil data stats dari cache selama 5 menit
        $dataStats = $cache->remember('dashboard_index', function() use ($statsData) {
            return $statsData->getAllData();
        }, 300);


        // Query dasar
        $query = "SELECT * FROM ".$this->table." ORDER BY hire_date DESC";

        // Set limit agar bisa auto Stream
        $mainData->limitToStream = 55;
        $dataDashboard = null;
        // Cek limit agar otomatis masuk ke Mode Stream
        if($limit > $mainData->limitToStream) {
            $dataDashboard = $mainData->paginate($query, [], $page, $limit);
        } else {
            $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
            $queryString = md5($cleanQuery);
            $cacheKeyId = "dashboard:{$this->table}:" . $queryString . ":p{$page}:l{$limit}";

            $dataDashboard = $cache->remember($cacheKeyId, function() use ($query, $page, $limit, $mainData) {
                return $mainData->paginate($query, [], $page, $limit);
            }, 300);
        }
        

        $modelA = [
            // 'result' => $result,
            'request' => $request,
            'table' => $this->table,
            'title' => $request['title'] ?? 'Testing model',
            'stats_data' => $dataStats ?: null,
            'pagination_data' => $dataDashboard, // Ini mode cache
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing index',
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
        // Set model Data
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
