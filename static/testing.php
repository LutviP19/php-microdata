<?php 
declare(strict_types=1);

// if(is_cli()) {
//     if (!defined('BASEPATH')) {
//         define('BASEPATH', __DIR__ . "/..");
//     }

//     if (!defined('BASEPATH_FFI')) {
//         define('BASEPATH_FFI', __DIR__ . '/../ffi');
//     }

//     require_once BASEPATH . '/cron/bootstrap.php';
// }

use App\Data\Dashboard\v1\{DashboardData, StatsData, EmployeeData};
use App\Structs\Dashboard\v1\{DashboardStruct, SallaryStruct};

use App\Core\Support\App;
use App\Core\Http\NativeCurlStreamer;
use App\Core\Auth\SodiumAuth;


// // Generate random string - JWT Secret
// $rndString = generateRandomString(64, false);
// die($rndString);

// SodiumAuth - Key ($hexKey)
// echo bin2hex(random_bytes(32)).PHP_EOL;
// php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"


// // Testing Class
// Class Testing extends \App\Models\BaseModel {
//     function __construct() {
//         parent::__construct();

//         // $genJwt = self::generateTokenJWT();
//         // dd($genJwt);

//         $genSodium = self::generateTokenSodium();
//         dd($genSodium, true);
//     }
// }

// $testing = new Testing();
// exit;


// Testing - Protobuf:
$bridge = new \App\Core\FFI\ProtoBridge();

// Metadata yang akan dikeluarkan lagi di decode
$metadata = ["user_id" => rand(1,88), "ip_address" => clientIP(), "browser" => get_short_ua()];
// $metadata = ["user" => "admin"];
// Data Payload (Bisa berupa string biner, gambar, atau hasil encrypt)
$extraPayload = "ini data biner rahasia";

// // Simulasikan Encode
// $contentData = ['test' => 'value'];
// $contentData = "/v1/auth x0001";
// $binProto = $bridge->pack($contentData, $metadata, $extraPayload);
// $dataProto = $bridge->unpack($binProto);

// // Jika metadata ada isinya
// echo "metadata: ";
// print_r($dataProto['metadata']);
// echo "<br>". PHP_EOL;

// // Jika ingin mengambil kembali payload aslinya
// $originalPayload = hex2bin($dataProto['payload_raw']);
// echo "payload_raw: " . $originalPayload ."<br>". PHP_EOL; // "ini data biner rahasia"
// // dd($dataProto, true);

// $dataArray = json_decode($dataProto['content'], true);
// echo "content: ";
// dd($dataArray, true);
// exit;


// // Testing CURL - Client Web
// $streamer = new NativeCurlStreamer();
// $start = microtime(true);

// try {
//     // A. TEST SINGLE STREAM
//     echo "=== TESTING CURL STREAM - Client Webhook ===<br>" . PHP_EOL;
//     $singleTask = App::externalApi('microdata_client_web', [
//         'body' => json_encode([
//                     'email' => 'test@microdata.local', 
//                     'password' => 'abcde1234',
//                     // List Events should be run
//                     'calledEvents' => ['order.created', 'order.payment', 'order.notif'] 
//                 ])
//     ]);

//     $single = $streamer->singleStream($singleTask);
//     // dd($single, true);
//     // dd($single['statusCode'], true);

//     // // Test JSON Protobuf
//     // $singleProtoTask = App::externalApi('testing_client_web', [
//     //     'body' => json_encode([
//     //                 'email' => 'test@microdata.local', 
//     //                 'password' => 'abcde1234',
//     //                 // List Events should be run
//     //                 'calledEvents' => ['order.created', 'order.payment', 'order.notif'] 
//     //             ])
//     // ]);
//     // $single = $streamer->singleStream($singleProtoTask);
//     // // End - Test JSON Protobuf

//     if ($single['error']) {
//         // Error ini sudah tercatat di log secara otomatis
//         echo "Gagal memproses data: " . $single['error'] ."<br>". PHP_EOL;
//     } else {

//         // Curl Decode
//         $dataCurl = json_decode($single['body'], true);
//         // dd($dataCurl, true);


//         // // Test JSON Protobuf
//         // // $binProto = $bridge->pack($dataCurl, $metadata, $extraPayload);
//         // $dataProto = $bridge->unpack($single['body']);

//         // // Jika metadata ada isinya
//         // echo "metadata: ";
//         // print_r($dataProto['metadata']);
//         // echo "<br>". PHP_EOL;

//         // // Jika ingin mengambil kembali payload aslinya
//         // $originalPayload = hex2bin($dataProto['payload_raw']);
//         // echo "payload_raw: " . $originalPayload."<br>". PHP_EOL; // "ini data biner rahasia"

//         // $data = json_decode($dataProto['content'], true);
//         // echo "content: ";
//         // dd($data, true);
//         // // End - Test JSON Protobuf


//         if($dataCurl['statusCode'] >= 200 && $dataCurl['statusCode'] < 300) {
//             // echo "Single Response: " . json_encode($data['data']['pagination_data']['meta']) . PHP_EOL;
//             echo "Single Response: " . json_encode($dataCurl) ."<br>". PHP_EOL;
//         } else {
//             $statusCode = $dataCurl['statusCode'];
//             echo "Request Single Error: {$statusCode} - " . ($dataCurl['message'] ?? 'N/A') ."<br>". PHP_EOL;
//         }
//     }
    
// } catch (Exception $e) {
//     echo "Fatal Error: " . $e->getMessage() ."<br>". PHP_EOL;
// } finally {
//     $time = microtime(true) - $start;
//     echo PHP_EOL . "--------------------------------------<br>" . PHP_EOL;
//     echo "Execution Time: " . $time . " seconds<br>" . PHP_EOL;
//     echo "Peak RAM Usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB<br>" . PHP_EOL;
// }
// exit;


// Sodium V4 (Asymmetric - Ed25519)
// ----------------
use App\Core\Auth\SodiumAuthV4;
$authV4 = new SodiumAuthV4();

// // 1. Generate Keys (Sekali saja):
// $keys = SodiumAuthV4::generateKeys();
// // Simpan $keys['private'] di env SSO Server
// // Simpan $keys['public'] di env SSO Server dan SEMUA App Client
// // dd($keys, true);

// 2. Di Server Backend (Login):
$expToken = time() + (60 * (config('session.lifetime') / 2));
$token = $authV4->encode(['uid' => 101, 'role' => 'admin', 'exp' => $expToken]);
// dd($token);

// 3. Di App Client (Middleware):
$userData = $authV4->decode($token);

if ($userData) {
    // Token Valid dan asli!
    dd($userData, true);
}
exit;
// ==============


// // Saat register karyawan baru
// // $newUlid = \App\Core\Support\UlidGenerator::generate(); // Hasil: 01H7XRMZ5W...
// $newUlid = generateUlid(); // Hasil: 01H7XRMZ5W...
// // dd($newUlid);
// // dd(get_short_ua());

// // Simpan ke DB jika belum ada ulid
// // $db->query("UPDATE {$this->table} SET ulid) = ? WHERE id = ?", [$newUlid, $user_id]);

// $auth = new SodiumAuth();
// // Asumsi validasi user & password sukses
// $expToken = time() + (60 * (config('session.lifetime') / 2));
// $payload = [
//     'uid'  => 12345,
//     'ulid' => $newUlid,
//     'role' => 'backend_dev',
//     'issuer' => 'php-microdata',
//     'audience' => 'users',
//     'exp'  => $expToken
// ];

// $token = $auth->encode($payload);
// // dd($token);

// // 2. Dekripsi & Validasi
// $user = null;
// if (!empty($token)) {
//     $user = $auth->decode($token);
// }
// // dd($user);

// echo "Access token:<br>" . PHP_EOL;
// if (!$user) {
//     // http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     return;
// }

// // Cek Expired
// if (time() > $user['exp']) {
//     // http_response_code(401);
//     echo json_encode(['error' => 'Token Expired']);
//     return;
// }

// // Jika sukses, tampilkan data
// echo json_encode([
//     'message' => "Selamat datang, karyawan ID: " . $user['uid'],
//     'data' => [/* data dashboard */]
// ]);
// echo "<br>-------<br>" . PHP_EOL;

// // Middleware Refresh Token
// // SAAT LOGIN
// $expToken = time() + (60 * (config('session.lifetime') / 2));
// $accessTokenPayload = [
//     'uid'  => $user->ulid,
//     'uid'  => 12345,
//     'ulid' => $newUlid,
//     'role' => 'backend_dev',
//     'type' => 'access',
//     'exp'  => $expToken
// ];
// $accessTokenPayload = array_merge($accessTokenPayload, $payload);

// $refreshTokenPayload = [
//     'type' => 'refresh',
// ];
// $refreshTokenPayload = array_merge($refreshTokenPayload, $payload);

// $dataToken = [
//     'access_token'  => $auth->encode($accessTokenPayload),
//     'refresh_token' => $auth->encode($refreshTokenPayload)
// ];
// echo "With refresh token:<br>" . PHP_EOL;
// echo "<pre>" . json_encode($dataToken, JSON_PRETTY_PRINT) ."</pre>";
// echo "<br>-------<br>" . PHP_EOL;


// // Validasi - Middleware: Apakah token ada, tipenya 'refresh', dan belum expired?
// $oldRefreshToken = $dataToken['refresh_token'] ?? '';
// $data = $auth->decode($oldRefreshToken);
// echo " Validasi - Middleware refresh token:<br>" . PHP_EOL;
// if ($data && $data['type'] === 'refresh' && $data['exp'] > time()) {
    
//     // Buat Access Token baru
//     $newAccessPayload = [
//         'uid'  => $data['uid'],
//         'ulid' => $newUlid,
//         'role' => 'backend_dev',
//         'type' => 'access',
//         'exp'  => time() + 3600
//     ];

//     echo json_encode([
//         'access_token' => $auth->encode($newAccessPayload)
//     ]);
// } else {
//     http_response_code(401);
//     echo json_encode(['error' => 'Refresh token invalid atau expired']);
// }
// echo "<br>-------<br>" . PHP_EOL;
// exit;



// // Sample Cara Implementasi JWT Permission
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
//         // "user-manage", 
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
