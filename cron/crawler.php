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
$ffi = FFI::cdef("
    char* crawler(char* filePath);
    void FreeString(char* ptr);
    ",
    BASEPATH_FFI . '/lib/crawler.so',
);

$start = microtime(true);

// Use BASEPATH to avoid "File Not Found" during concurrent execution
$path = BASEPATH . "/cron/urls.txt";

if (!$path) {
    die("PHP Error: urls.txt does not exist in " . getcwd());
}

// Mode
$rawMode = false; // rawMode: true || false: output JSON

// Pass the absolute path to Go
$ptr = $ffi->crawler($path);
$raw_input = FFI::string($ptr);
if($rawMode) {
    $result = clean_newlines($raw_input); // Test raw-ouput
} else {
    $result = parse_crawler_logs(clean_newlines($raw_input));
}

// Use the exact name defined in cdef
$ffi->FreeString($ptr);
// End timmer
$end = microtime(true);
$time = $end - $start;

echo "PHP-FFI Go Webcrawler result:" . PHP_EOL;
if ($rawMode) {
    echo $result . PHP_EOL; // Test raw-ouput
} else {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
echo "It took {$time} seconds to finished." . PHP_EOL;
echo "[" . date('Y-m-d H:i:s') . "] End Webcrawler..." . PHP_EOL;
