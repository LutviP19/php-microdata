<?php 

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH_FFI')) {
    define('BASEPATH_FFI', __DIR__ . '/../ffi');
}

/**
 * Require Core init File.
 */
require_once BASEPATH .'/app/Core/init.php';


// Meili Search
use GuzzleHttp\Client as GuzzleClient;
use Meilisearch\Client as MelisearchClient;

// Enabled Experimental filter operators
// Enabling the experimental feature will make two new operators available, 
// the CONTAINS and STARTS WITH ones, while filtering on strings.
// The CONTAINS one is similar to the SQL LIKE operator used in that way %venture% 
// and the STARTS WITH one is like using vent%.
try {
    // Send PATCH to Enabling the experimental feature
    $clientGuzzle = new GuzzleClient([
        // Base URI is used with relative requests
        'base_uri' => 'http://127.0.0.1:7700',
        // You can set default request options here
        'timeout'  => 2.0,
    ]);
    
    $response = $clientGuzzle->patch('/experimental-features', [
        'json' => [
            'containsFilter' => true,
        ],
        'headers' => [
            'Authorization' => 'Bearer ms',
            'Accept'        => 'application/json',
        ]
    ]);
                
    // Check the HTTP status code
    $statusCode = $response->getStatusCode();
    if($statusCode !== 200) {
        // Get the response body as a stream and convert to a string/JSON
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        dd($data, true);
    }

} catch(\GuzzleHttp\Exception\RequestException $e) { // Base exception for most errors
    $errors = [];
    // Handle the exception
    if ($e->hasResponse()) {
        // If an HTTP response was received, get its body and status code
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $responseBody = $response->getBody()->getContents();

        $errors = [
            'statusCode' => $statusCode,
            'responseBody' => json_decode($responseBody, true),
        ];

    } else {
        // Handle network errors (e.g., connection timeout, DNS error)
        $errors = [
            'exception' => "Network Error: " . $e->getMessage(),
        ];
    }

    dd($errors, true);
}

// Melisearch Client
$client = new MelisearchClient('http://127.0.0.1:7700', 'ms');

// Add Documents to Your PHP Search Engine
$index = $client->index('movies');
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
$index->addDocuments($documents);

// // Keyword Search
// $keyword = $request->q; // the || The Matrix

// // Build the Search Function and Return Results
// $results = $index->search($keyword)->getHits();

// Add Filters and Facets for Smarter Search
$index->updateFilterableAttributes([
    'title',
    'genres',
    'id'
]);

$status = $client->getTask(1);
// dd($status, true);
$logFile = logs_path('cron_log.txt');
// TUGAS YANG DIJALANKAN
$message = "[" . date('Y-m-d H:i:s') . "] Tugas dijalankan: Indexing Data setiap 1 menit..." . PHP_EOL;
file_put_contents($logFile, $message, FILE_APPEND);

// Perbarui status indexing
file_put_contents($lastRunFile, $status);

// // filtered searches using SQL-style logic        
// // dd($keyword);

// // Default filter
// $maxId = 25;
// $filter = ['title CONTAINS "'.$keyword.'" AND id <= 25'];

// // Filter by genre
// if($request->has('genre')) {
//     $genre = (string) $request->genre; // action || drama
//     if($genre !== '')
//         $filter = ['title CONTAINS "'.$keyword.'" AND genres IN ['.$genre.'] AND id <= 25'];
// }

// $results = $index->search($keyword, [
//     'facets' => ['title', 'genres', 'id'],
//     'filter' => $filter,
// ])->getHits();

// dd($results, true);