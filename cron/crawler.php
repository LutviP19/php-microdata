<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


// Webcrawler Concurrent process
echo "[" . date('Y-m-d H:i:s') . "] Run PHP-FFI Go Webcrawler Concurrent process..." . PHP_EOL;
$ffi = \FFI::cdef("
    char* crawler(char* filePath);
    void FreeString(char* ptr);
    ",
    // BASEPATH_FFI . '/lib/crawler.so',
    path_join(BASEPATH_FFI, 'lib', 'crawler.so'),
);

$start = microtime(true);

// Use BASEPATH to avoid "File Not Found" during concurrent execution
$path = BASEPATH . "cron/urls.txt";

if (!$path) {
    die("PHP Error: urls.txt does not exist in " . getcwd());
}

// Mode
$rawMode = false; // rawMode: true || false: output JSON

// Pass the absolute path to Go
$ptr = $ffi->crawler($path);
$raw_input = \FFI::string($ptr);
if($rawMode) {
    $result = clean_newlines($raw_input); // Test raw-ouput
} else {
    $result = parse_crawler_logs(clean_newlines($raw_input));

    // $refinedData = [];
    // foreach ($result as $item) {
    //     if(is_null($item['title']))
    //         continue;

    //     // Flatten labels into a comma-separated string
    //     $tags = "";
    //     if (isset($item['metadata']['labels'])) {
    //         $tags = implode(', ', $item['metadata']['labels']);
    //     }

    //     $refinedData[] = [
    //         'title'   => $item['title'],
    //         'content' => $item['content'],
    //         'tags'    => $tags
    //     ];
    // }

    // // This $payload is now ready for Go
    // $payload = json_encode($refinedData);
    // if(!file_exists(logs_path('crawler'))) mkdir(logs_path('crawler'), 775, true);
    // file_put_contents(logs_path('crawler/crawler'.date('Ymd-His').'.json'), $payload);
}

// Use the exact name defined in cdef
$ffi->FreeString($ptr);
// End timmer
$end = microtime(true);
$time = $end - $start;

// Logika cek output command line
if (!$is_cron) {
    echo "PHP-FFI Go Webcrawler result:" . PHP_EOL;

    if ($rawMode) {
        echo $result . PHP_EOL; // Test raw-ouput
    } else {
        $jsonResult = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo $jsonResult . PHP_EOL;
    }
} else {

    if (!$rawMode) {
        $count = count($result);
        echo "Crawled items: {$count}" . PHP_EOL;
    }
}
echo "It took {$time} seconds to finished." . PHP_EOL;
echo "End Webcrawler..." . PHP_EOL;
