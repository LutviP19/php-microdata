<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/meiliserach.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


use App\Core\Http\NativeCurlStreamer;
// use App\Core\Http\GoHttpClient;
use App\Core\Support\App;

try {
    $streamer = new NativeCurlStreamer();

    // $client = new GoHttpClient();

    $path = BASEPATH . 'test/movies.json';
    $rawJson = file_get_contents($path);
    if ($rawJson === false) {
        throw new RuntimeException("Gagal membaca file: {$path}");
    }

    // Validasi JSON agar tidak mengirim payload rusak ke Meilisearch
    $decoded = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded) || $decoded === []) {
        throw new RuntimeException("File movies.json kosong atau bukan array JSON.");
    }

    $apiParams = App::externalApi('ms_add_documents', [
        'body' => $rawJson,
        'timeout' => 5.0,
    ]);

    // $response = $client->request($apiParams);
    $response = $streamer->singleStream($apiParams);
    // dd($response, true);
    
    $statusCode = (int) ($response['statusCode'] ?? 0);
    $payload = json_decode((string) ($response['body'] ?? ''), true);

    // Endpoint addDocuments di Meilisearch umumnya mengembalikan HTTP 202 Accepted beserta taskUid
    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException(
            "Meilisearch error [{$statusCode}]: " . json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    $taskUid = $payload['taskUid'] ?? null;
    echo "Task dikirim ke Meilisearch. taskUid: " . ($taskUid ?? 'unknown') . PHP_EOL;

    // Opsional: polling status task hingga succeeded / failed
    if ($taskUid !== null) {
        for ($i = 0; $i < 20; $i++) {
            $taskRes = $streamer->singleStream(App::externalApi('ms_get_task', [
                'url' => env('MEILISEARCH_URL', 'http://localhost:7700') . '/tasks/' . $taskUid,
                // 'taskUid' => $taskUid,
                'timeout' => 2.0,
            ]));
            $taskData = json_decode((string) ($taskRes['body'] ?? ''), true);
            $status = $taskData['status'] ?? 'unknown';

            if ($status === 'succeeded' || $status === 'failed') {
                echo "Status akhir task {$taskUid}: {$status}" . PHP_EOL;
                if ($status === 'failed') {
                    print_r($taskData['error'] ?? []);
                }
                break;
            }
            usleep(100_000); // 100ms
        }
    }
} catch (Throwable $e) {
    throw $e;
}