<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 *  file: cron/searchBulkData.php
 */


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


$dbFile = realpath(database_path('vector_store.db'));
$userInput = "Waktu";
$limit = 10;

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


// Define the interface
$ffi = \FFI::cdef("
    char* SearchFTS(char* dbPath, char* query, int limit);
    void FreeString(char* ptr);
", path_join(BASEPATH_FFI, 'lib', 'bulk_data.so'));

// 1. Call Go (Go handles sanitization and FTS5 logic)
$resPtr = $ffi->SearchFTS($dbFile, $userInput, $limit);

// 2. Convert and Free
$json = FFI::string($resPtr);
$ffi->FreeString($resPtr);

// 3. Use results
$results = json_decode($json, true);

foreach ($results as $row) {
    echo "ID: {$row['id']} - Tags: {$row['tags']}\n";
    echo "Content: " . mb_substr((string) $row['content'], 0, 100) . "...\n================\n";
}

// // ======================
// // Sample Update - Delete
// $ffi = FFI::cdef("
//     char* UpdateRecord(char* dbPath, long long id, char* content, char* tags);
//     char* DeleteRecord(char* dbPath, long long id);
//     void FreeString(char* ptr);
// ", path_join(BASEPATH_FFI, 'lib', 'bulk_data.so'));

// // Usage
// $resPtr = $ffi->UpdateRecord($dbFile, 123, "New Content", "tag1,tag2");
// echo FFI::string($resPtr);
// $ffi->FreeString($resPtr);
