<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/curlRust.php

/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


use App\Core\Support\App;


$start = microtime(true);

try {
    $client = new \App\Core\Http\RustHttpClient();

    $params = App::externalApi('dashboard_get');
    // dd($params);

    // Hanya kirim Body / Timeout
    $apiParams = App::externalApi('dashboard_get', [
        'body' => ['page' => 1, 'limit' => 1000], // Bisa Array|string|JSON
        'timeout' => 15 // Set atau gunakan default
    ]);

    // dd($apiParams, true);
    $single = $client->request($apiParams);
    // dd($single, true);
    
    if ($single['error']) {
        // Error ini sudah tercatat di http_bridge.log secara otomatis
        echo "Gagal memproses data: " . $single['error'];
    } else {
        $data = json_decode($single['body'], true)[0];
        // dd($data, true);
        if($data['statusCode'] >= 200 && $data['statusCode'] < 300) {
            echo "Single Response: " . json_encode($data['data']['pagination_data']['meta']) . PHP_EOL;
        } else {
            $statusCode = $data['statusCode'];
            echo "Request Single Error: {$statusCode} - " . ($data['message'] ?? 'N/A') . PHP_EOL;
        }
    }
    // exit;


    // 2. Contoh Parallel Call (Jauh lebih cepat untuk banyak URL)
    $tasks = [
        App::externalApi('dashboard_get', [
            'body' => ['page' => 1, 'limit' => 20000], // Bisa Array|string|JSON
        ]),
        App::externalApi('dashboard_get', [
            'body' => json_encode(['page' => 3, 'limit' => 5000]), // Bisa Array|string|JSON
        ]),
        App::externalApi('dashboard_get', [
            'body' => ['page' => 2, 'limit' => 100000], // Bisa Array|string|JSON
        ]),
    ];

    echo "Executing multi-request..." . PHP_EOL;
    $results = $client->multiRequest($tasks);
    // dd($results);

    foreach ($results as $index => $res) {
        if (!empty($res['error'])) {
            echo "Request #$index Gagal: " . $res['error'] . PHP_EOL;
        } else {            
            $data = json_decode($res['body'], true);
            
            // Validasi format GO STREAMING FILTER
            if(is_array($data) && isset($data[0])) {
                $data = $data[0];
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
    echo "Fatal Error: " . $e->getMessage();
} finally {
    // Close App

    // End timmer
    $end = microtime(true);
    $time = $end - $start;
    echo "It took {$time} seconds to finished." . PHP_EOL;

    exit;
}