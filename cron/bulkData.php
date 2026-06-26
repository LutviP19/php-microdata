<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 *  file: cron/bulkData.php
 */


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


$dbFile = realpath(database_path('vector_store.db'));
// dd($dbFile);

// Load database
try {
    // Setup FTSs
    $db = new PDO('sqlite:'.$dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA journal_mode = WAL;");

    // 1. Master Table (Main Data)
    $db->exec("CREATE TABLE IF NOT EXISTS indexed_contents (
        id INTEGER PRIMARY KEY, 
        title TEXT UNIQUE,
        content TEXT, 
        tags TEXT,
        vector BLOB,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. FTS5 Virtual Table (For Quick Search)
    // We've included 'tags' so it can also be searched via FTS
    $db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS indexed_contents_fts USING fts5(
        title,
        content, 
        tags, 
        content='indexed_contents', 
        content_rowid='id'
    )");
} catch (Exception $e) {
    $error = $e->getMessage();
}


// // 1. Standard Autoloader for your PHP subfolders
// spl_autoload_register(function ($class) {
//     $file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
//     if (file_exists($file)) require_once $file;
// });

// 2. Define FFI
$ffi = FFI::cdef("
    void* SaveBulkData(char* dbPath, char* jsonData);
    int GetSize(void* ptr);
    void FillAndFree(void* ptr, char* outBuffer);
", BASEPATH_FFI . "lib/bulk_data.so");

// 3. Prepare Data
// $data = [
//     ['title' => 'Title A', 'content' => 'Some text...', 'tags' => 'tech,go'],
//     ['title' => 'Title B', 'content' => 'Other text...', 'tags' => 'php,ffi']
// ];
// $json = json_encode($data);
$json = file_get_contents(storage_path('logs/crawler/crawler20260323-013315.json'));

// 4. Execution Workflow
// Returns a pointer to a specific Go struct instance (Thread-Safe)
$resPtr = $ffi->SaveBulkData($dbFile, $json);

// Step A: Get exact size for THIS specific result
$size = $ffi->GetSize($resPtr);

// Step B: Allocate local PHP memory
$buffer = $ffi->new("char[$size]");

// Step C: Fill buffer and let Go release the internal container
$ffi->FillAndFree($resPtr, $buffer);

$finalStatus = FFI::string($buffer);
echo "Bulk Insert Status: " . $finalStatus . PHP_EOL;
