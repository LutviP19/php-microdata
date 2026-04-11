<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/curlGo.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';

try {
    $client = new \App\Core\Http\GoHttpClient();

    // Default Headers
    $headers = [
            'User-Agent' => 'PHP-FFI-App',
            'Content-Type' => 'application/json',
            'X-API-KEY' => 'sswrSrFtV1VkYz0ikG4dpouo1uEqEvS9cZ3QfwgTxdc=',
    ];

    // 1. Contoh Single Call
    // $single = $client->get("https://jsonplaceholder.typicode.com/todos/1");
    $single = $client->request('GET', "http://localhost:8000/api/v1/dashboard", [
        'headers' => $headers,
        'body' => json_encode([
            'page' => 1,
            'limit' => 2000
        ])
    ]);
    if ($single['error']) {
        // Error ini sudah tercatat di http_bridge.log secara otomatis
        echo "Gagal memproses data: " . $res['error'];
    } else {
        $data = json_decode($single['body'], true);
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
        [
            'method' => 'GET',
            'url' => 'http://localhost:8000/api/v1/dashboard',
            'headers' => $headers,
            'body' => json_encode([
                'page' => 2,
                'limit' => 10
            ])
        ],
        [
            'method' => 'GET',
            'url' => 'http://localhost:8000/api/v1/dashboard',
            'headers' => $headers,
            'body' => json_encode([
                'page' => 3,
                'limit' => 30
            ])
        ],
        [
            'method' => 'GET',
            'url' => 'http://localhost:8000/api/v1/dashboard',
            'headers' => $headers,
            'body' => json_encode([
                'page' => 4,
                'limit' => 100
            ])
        ],
    ];

    echo "Executing multi-request..." . PHP_EOL;
    $results = $client->multiRequest($tasks);

    foreach ($results as $index => $res) {
        if (!empty($res['error'])) {
            echo "Request #$index Gagal: " . $res['error'] . PHP_EOL;
        } else {
            $data = json_decode($res['body'], true);
            // dd($data, true);
            if($data['statusCode'] >= 200 && $data['statusCode'] < 300) {
                echo "Request #$index Sukses: Title adalah " . ($data['data']['title'] ?? 'N/A') . PHP_EOL;
            } else {
                $statusCode = $data['statusCode'];
                echo "Request #$index Error: {$statusCode} - " . ($data['message'] ?? 'N/A') . PHP_EOL;
            }
            
        }
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
} finally {


    exit;
}