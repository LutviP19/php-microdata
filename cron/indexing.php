<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


// Meili Search
use App\Core\Support\App;
use App\Core\Http\GoHttpClient;

// Enabled Experimental filter operators
// Enabling the experimental feature will make two new operators available, 
// the CONTAINS and STARTS WITH ones, while filtering on strings.
// The CONTAINS one is similar to the SQL LIKE operator used in that way %venture% 
// and the STARTS WITH one is like using vent%.
try {
    
    $client = new GoHttpClient();
    $apiParams = App::externalApi('ms_enabled_experimental', [
        'body' => json_encode(['containsFilter' => true]), // Bisa Array|string|JSON
        'timeout' => 2.0 // Set atau gunakan default
    ]);

    // dd($apiParams, true);
    $response = $client->request($apiParams);
    // dd($response, true);
                
    // Check the HTTP status code
    $statusCode = $response['status'];
    if($statusCode !== 200) {
        // Get the response body as a stream and convert to a string/JSON
        $body = $response['body'];
        $data = json_decode((string) $body, true);
        dd($data, true);
    }

} catch(Exception $e) {
    throw($e);
}


// Add Documents to Your PHP Search Engine
$documents = [
    ['id' => 1, 'title' => 'Inception', 'genres' => ['Action', 'Sci-Fi']],
    ['id' => 2, 'title' => 'Interstellar', 'genres' => ['Adventure', 'Drama']],
    ['id' => 3, 'title' => 'The Dark Knight', 'genres' => ['Action', 'Crime', 'Drama']],
    ['id' => 4, 'title' => 'Pulp Fiction', 'genres' => ['Crime', 'Drama']],
    ['id' => 5, 'title' => 'The Matrix', 'genres' => ['Action', 'Sci-Fi']],
    ['id' => 6, 'title' => 'Forrest Gump', 'genres' => ['Drama', 'Romance']],
    ['id' => 7, 'title' => 'Gladiator', 'genres' => ['Action', 'Adventure', 'Drama']],
    ['id' => 8, 'title' => 'The Godfather', 'genres' => ['Crime', 'Drama']],
    ['id' => 9, 'title' => 'Parasite', 'genres' => ['Comedy', 'Drama', 'Thriller']],
    ['id' => 10, 'title' => 'Spirited Away', 'genres' => ['Animation', 'Adventure', 'Family']],
    ['id' => 11, 'title' => 'The Shawshank Redemption', 'genres' => ['Drama']],
    ['id' => 12, 'title' => 'Avengers: Endgame', 'genres' => ['Action', 'Adventure', 'Sci-Fi']],
    ['id' => 13, 'title' => 'Coco', 'genres' => ['Animation', 'Adventure', 'Comedy']],
    ['id' => 14, 'title' => 'The Silence of the Lambs', 'genres' => ['Crime', 'Drama', 'Thriller']],
    ['id' => 15, 'title' => 'The Lion King', 'genres' => ['Animation', 'Adventure', 'Drama']],
    ['id' => 16, 'title' => 'Fight Club', 'genres' => ['Drama']],
    ['id' => 17, 'title' => 'Blade Runner 2049', 'genres' => ['Action', 'Sci-Fi', 'Drama']],
    ['id' => 18, 'title' => 'Whiplash', 'genres' => ['Drama', 'Music']],
    ['id' => 19, 'title' => 'Grand Budapest Hotel', 'genres' => ['Adventure', 'Comedy', 'Drama']],
    ['id' => 20, 'title' => 'Joker', 'genres' => ['Crime', 'Drama', 'Thriller']],
    ['id' => 21, 'title' => 'Your Name', 'genres' => ['Animation', 'Drama', 'Fantasy']],
    ['id' => 22, 'title' => 'Dune', 'genres' => ['Action', 'Adventure', 'Sci-Fi']],
    ['id' => 23, 'title' => 'Everything Everywhere All at Once', 'genres' => ['Action', 'Adventure', 'Comedy']],
    ['id' => 24, 'title' => 'The Prestige', 'genres' => ['Drama', 'Mystery', 'Sci-Fi']],
    ['id' => 25, 'title' => 'Spiderman: Across the Spider-Verse', 'genres' => ['Animation', 'Action', 'Adventure']]
];

$client = new GoHttpClient();
$apiParams = App::externalApi('ms_add_documents', [
        'body' => json_encode($documents),
        'timeout' => 5.0,
    ]);
$response = $client->request($apiParams);
// dd($response, true);

$statusCode = (int) ($response['status'] ?? 0);
$payload = json_decode((string) ($response['body'] ?? ''), true);

// Endpoint addDocuments di Meilisearch umumnya mengembalikan HTTP 202 Accepted beserta taskUid
if ($statusCode < 200 || $statusCode >= 300) {
    throw new RuntimeException(
        "Meilisearch error [{$statusCode}]: " . json_encode($payload, JSON_THROW_ON_ERROR)
    );
}
$taskUid = $payload['taskUid'] ?? null;
echo "Task dikirim ke Meilisearch. taskUid: " . ($taskUid ?? 'unknown') . PHP_EOL;




// Settings Filterable & Sortable Attributes
$settings = [
    'filterableAttributes' => ["title", "id", "genres", "release_year", "is_featured", "language"],
    'sortableAttributes' => ["rating", "release_year", "popularity"]
];
$apiParams = App::externalApi('ms_search_settings', [
        // 'url' => env('MEILISEARCH_URL', 'http://localhost:7700') . '/indexes/movies/settings',
        'body' => json_encode($settings),
        'timeout' => 5.0,
    ]);
$response = $client->request($apiParams);
// dd($response, true);


// Build the Search Function and Return Results
// Keyword Search
$keyword = 'Dune'; // Dune || the || The Matrix
$genre = ''; // action || drama

// Default filter
$maxId = 25;
$limit = 10;
// $filter = ['title CONTAINS "'.$keyword.'" AND id <= 25'];
$filter = ['title CONTAINS "'.$keyword.'" AND release_year >= 2015'];

// Filter by genre
if($genre !== '')
    $filter = ['title CONTAINS "'.$keyword.'" AND genres IN ['.$genre.'] AND id <= 25'];

// Opsional: polling status task hingga succeeded / failed
if ($taskUid !== null) {
    for ($i = 0; $i < 20; $i++) {
        $taskRes = $client->request(App::externalApi('ms_get_task', [
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


    $filters = [
        'q' => $keyword,
        'filter' => $filter,
        'sort' => ["rating:desc"],
        'limit' => $limit
    ];

    $apiParams = App::externalApi('ms_search_documents', [
        'url' => env('MEILISEARCH_URL', 'http://localhost:7700') . '/indexes/movies/search',
        'body' => json_encode($filters),
    ]);
    $results = $client->request($apiParams);
    // dd($results, true);
    $statusCode = (int) ($results['status'] ?? 0);
    $items = json_decode((string) ($results['body'] ?? ''), true);
    dd($items, true);
} else {
    echo "Meilisearch error [{$statusCode}]: " . json_encode($payload, JSON_THROW_ON_ERROR) . PHP_EOL;
}