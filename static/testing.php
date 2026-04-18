<?php 
declare(strict_types=1);

use App\Data\Dashboard\v1\{DashboardData, StatsData, EmployeeData};
use App\Structs\Dashboard\v1\{DashboardStruct, SallaryStruct};


// // Generate random string
// $rndString= generateRandomString(64, false);
// die($rndString);


// // Sample Cara Implementasi Permission
// use App\Core\Auth\JWT;

// $jwt = new JWT();

// // Data yang ingin dimasukkan ke dalam token
// $payload = [
//     'user_id' => 123,
//     'username' => 'dev_user',
//     'user_permissions' => [ 
//         "asset-create", 
//         "asset-view", 
//         "asset-edit", 
//         "asset-delete", 
//         "user-manage", 
//         "report-view" 
//     ],
//     'exp' => time() + (60 * 60) // Expired dalam 1 jam
// ];

// // Generate Token
// $token = $jwt->encode($payload);
// // dd($token);


// // Cara Mengecek Permission di Sisi Server
// $decoded = $jwt->decode($token);

// if ($decoded) {
//     $permissions = $decoded['user_permissions'] ?? [];

//     // Contoh pengecekan akses untuk fitur tertentu
//     if (in_array('user-manage', $permissions)) {
//         echo "Akses diberikan: Anda boleh mengelola user.";
//     } else {
//         echo "Akses ditolak: Anda tidak punya izin.";
//     }
// }
// exit;


// echo "Testing script.";

// dd(config('app'));
// http://localhost:8000/testing?limit=150

$cache = new \App\Core\Support\Cache();

$request = $_GET;
// Ambil data paginate result dari cache selama 5 menit
$page = (int) ($request['page'] ?? 3);
$limit = (int) ($request['limit'] ?? 50); // total data perpage

$id = $request['id'] ?? null;

// Maximum limit to render in browser
// else using chunk - FFI
$maxLimitToRender = 10000;

// Dynamic 
if($limit >= $maxLimitToRender) {
    $logDebug = "Memory PHP saat ini: " . (memory_get_usage(true) / 1024 / 1024) . " MB";
    write_log($logDebug, 'static/testing.php', 'debug', 'debug_FFI.log');

    $isInsomnia = str_contains($_SERVER['HTTP_USER_AGENT'], 'insomnia');
    // dd($isInsomnia);
    try{
        // dd($limit);
        $options = [
            // PENTING: Matikan buffering agar PHP tidak menarik 1 juta baris sekaligus ke RAM
            // (defined('\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY') ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) => false,
            // Gunakan 1001 langsung untuk menjamin kompatibilitas di PHP 8.3 - 8.5
            1001 => false,
        ];
        // dd($options);
        $mainData = new StatsData(null, $options);

        // Set struct
        $mainData->structClass = SallaryStruct::class;

        $status = 200;
        $message = 'Testing Chunk FFI';

        // Max limit 100000 (untuk di Browser, Agar tidak Crash/Hang)
        // $limit = $limit > 100000 ? 100000 : $limit;
        $chunkData = $mainData->getAllDataSalaries($id, $limit, '*', true);

        // Gunakan helper
        if($isInsomnia) {
            json_response_stream($status, $message, $chunkData);
        } else {
            // Pastikan output buffering PHP kosong
            while (ob_get_level()) ob_end_flush();
            $counter = 0;
            // Parsing Generator()
            foreach ($chunkData as $item) {                
                // Flush data ke Caddy setiap 100 baris
                if (++$counter % 100 === 0) {
                    flush(); 
                }
                if (connection_aborted()) break;
            }
            echo "Total data: " . number_format($counter, 0);
            exit;
        }

// // === MANUAL HANDLER
//         // PAKSA PHP melakukan kompresi di level output buffer
//         // Ini lebih stabil daripada ob_gzhandler untuk streaming
//         if (!connection_aborted()) {
//             ini_set('zlib.output_compression', 'On');
//         }

        
//         header('Content-Type: application/json');
//         // Matikan buffering Caddy/Nginx
//         header('X-Accel-Buffering: no'); 
//         header('X-Content-Type-Options: nosniff');
//         http_response_code(200);

//         // Aktifkan streaming
//         set_time_limit(0);
//         if (ob_get_level()) ob_end_clean();

//         // Pastikan output buffering PHP kosong
//         while (ob_get_level()) ob_end_flush();
        

//         echo '{';
//         echo '"statusCode":' . $status . ',';
//         echo '"message":"' . addslashes($message) . '",';
        
//         // 2. Buka object "data"
//         echo '"data":';

//         echo "[";
//         $counter = 0;
//         $first = true;
//             // Parsing Generator()
//             foreach ($chunkData as $item) {

//                 if($isInsomnia) {
//                     if (!$first) echo ',';
//                     echo json_encode($item, JSON_UNESCAPED_SLASHES);
//                     $first = false;
//                 }
                
//                 // Flush data ke Caddy setiap 100 baris
//                 if (++$counter % 100 === 0) {
//                     flush(); 
//                 }
//                 if (connection_aborted()) break;
//             }
//         echo "]";
//         echo '}'; // tutup root

//         if(!$isInsomnia) {
//             echo $counter;
//         }
// // === END MANUAL HANDLER

    } catch (\Throwable $e) {
        // Log error secara detail
        echo("Gagal inisialisasi StatsData: " . $e->getMessage());
        // Re-throw agar error detail (seperti typo function) muncul di log global
        throw $e;
    } finally {
        // 2. Hapus referensi variabel besar
        unset($chunkData, $mainData);
        
        // 3. Paksa PHP Engine membuang sampah memori
        gc_collect_cycles();
        
        exit;
    }
} else {
    $mainData = new StatsData();
    // dd($mainData->getPDO()->getAttribute(1001));
}

// Set table
$table = 'salaries';
$mainData->table($table);

// Set limit agar bisa auto Stream
$mainData->limitToStream = 100;

// Query Utama
$dataEmployees = null;
$query = "SELECT * FROM ".$table." ORDER BY to_date DESC";

// Cek limit agar otomatis masuk ke Mode Stream
if($limit > $mainData->limitToStream) {
    $dataEmployees = $mainData->paginate($query, [], $page, $limit);
    // dd($dataEmployees['meta']);

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
