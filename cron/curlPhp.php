<?php
declare(strict_types=1);

/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

use App\Core\Support\App;
use App\Core\Http\NativeCurlStreamer;


// --- EKSEKUSI DEMO ---

$streamer = new NativeCurlStreamer();
$start = microtime(true);

try {
    // A. TEST SINGLE STREAM
    echo "=== TESTING SINGLE STREAM ===" . PHP_EOL;
    $singleTask = App::externalApi('dashboard_get', [
        'body' => ['page' => 1, 'limit' => 1000]
    ]);
    $single = $streamer->singleStream($singleTask);
    // dd($single, true);
    // dd($single['statusCode'], true);

    if ($single['error']) {
        // Error ini sudah tercatat di log secara otomatis
        echo "Gagal memproses data: " . $single['error'] . PHP_EOL;
    } else {
        $data = json_decode((string) $single['body'], true);
        // dd($data, true);
        if($data['statusCode'] >= 200 && $data['statusCode'] < 300) {
            echo "Single Response: " . json_encode($data['data']['pagination_data']['meta']) . PHP_EOL;
        } else {
            $statusCode = $data['statusCode'];
            echo "Request Single Error: {$statusCode} - " . ($data['message'] ?? 'N/A') . PHP_EOL;
        }
    }
    // exit;
    


    echo PHP_EOL . "=== TESTING MULTI STREAM ===" . PHP_EOL;
    // B. TEST MULTI STREAM
    $multiTasks = [
        App::externalApi('dashboard_get', ['body' => ['page' => 1, 'limit' => 20000]]),
        App::externalApi('dashboard_get', ['body' => ['page' => 3, 'limit' => 5000]]),
        App::externalApi('dashboard_get', ['body' => ['page' => 2, 'limit' => 10000]]),
    ];
    $results = $streamer->multiStream($multiTasks);

    foreach ($results as $index => $res) {
        if (!empty($res['error'])) {
            echo "Request #$index Gagal: " . $res['error'] . PHP_EOL;
        } else {
            $data = json_decode((string) $res['body'], true);
            
            // Validasi format GO STREAMING FILTER
            if(is_array($data) && isset($data[0])) {
                $data = $data;
            }            
            // $status = $data['statusCode'];
            // dd($status);
            
            if($data['statusCode'] >= 200 && $data['statusCode'] < 300) {
                // echo "Request #$index Sukses: Title adalah " . ($data['data']['title'] ?? 'N/A') . PHP_EOL;
                echo "Request #$index Sukses: " . json_encode($data['data']['pagination_data']['meta']) . PHP_EOL;
            } else {
                $statusCode = $data['statusCode'];
                echo "Request #$index Error: {$statusCode} - " . ($data['message'] ?? 'N/A') . PHP_EOL;
            }
        }
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . PHP_EOL;
} finally {
    $time = microtime(true) - $start;
    echo PHP_EOL . "--------------------------------------" . PHP_EOL;
    echo "Execution Time: " . $time . " seconds" . PHP_EOL;
    echo "Peak RAM Usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB" . PHP_EOL;
}