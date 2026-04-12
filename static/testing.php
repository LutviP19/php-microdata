<?php 
declare(strict_types=1);

use App\Data\Dashboard\v1\{DashboardData, StatsData, EmployeeData};

// echo "Testing script.";

// dd(config('app'));
// http://localhost:8000/testing?limit=150

$cache = new \App\Core\Support\Cache();
$mainData = new EmployeeData();

$request = $_GET;
// Ambil data paginate result dari cache selama 5 menit
$page = $request['page'] ?? 3;
$limit = $request['limit'] ?? 50; // total data perpage

// Set table
$table = 'employees';
$mainData->table($table);

// Set limit agar bisa auto Stream
$mainData->limitToStream = 100;

// Query Utama
$dataEmployees = null;
$query = "SELECT * FROM ".$table." ORDER BY hire_date DESC";

// Cek limit agar otomatis masuk ke Mode Stream
if($limit > $mainData->limitToStream) {
    $dataEmployees = $mainData->paginate($query, [], $page, $limit);

    // Stream - Output
    json_response_stream(200, 'testing-stream', $dataEmployees['data'], $dataEmployees['meta']);
} else {            
    $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
    $queryString = md5($cleanQuery);
    $cacheKeyId = "employees_data:{$table}:" . $queryString . ":p{$page}:l{$limit}";

    $dataEmployees = $cache->remember($cacheKeyId, function() use ($query, $page, $limit, $mainData) {
        return $mainData->paginate($query, [], $page, $limit);
    }, 300);
}
dd($dataEmployees, true);
// echo "<pre>".json_encode($dataEmployees, JSON_PRETTY_PRINT)."</pre>";
