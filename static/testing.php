<?php 
declare(strict_types=1);

use App\Data\Dashboard\v1\{DashboardData, StatsData, EmployeeData};

// echo "Testing script.";

// dd(config('app'));

$cache = new \App\Core\Support\Cache();
$mainData = new EmployeeData();

// Ambil data paginate result dari cache selama 5 menit
$page = $request['page'] ?? 3;
$limit = $request['limit'] ?? 15; // total data perpage

// Set table
$table = 'employees';
// $mainData::$tableM = $table;
// dd($mainData->table);

// Set limit agar bisa auto Stream
// $mainData->limitToStream = 100;
$dataDashboard = null;

// Query Utama
$query = "SELECT * FROM ".$table." ORDER BY hire_date DESC";

// Cek limit agar otomatis masuk ke Mode Stream
if($limit > 50) {
    $dataDashboard = $mainData->paginate($query, [], $page, $limit);
} else {            
    $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
    $queryString = md5($cleanQuery);
    $cacheKeyId = "dashboard_data:{$table}:" . $queryString . ":p{$page}:l{$limit}";

    $dataDashboard = $cache->remember($cacheKeyId, function() use ($query, $page, $limit, $mainData) {
        return $mainData->paginate($query, [], $page, $limit);
    }, 300);
}
// dd($dataDashboard, true);
echo "<pre>".json_encode($dataDashboard, JSON_PRETTY_PRINT)."</pre>";
