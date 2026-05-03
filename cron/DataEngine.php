<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/DataEngine.php

/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


use App\Core\FFI\DataEngine;


// 1. Inisialisasi Engine (Arahkan ke file .so hasil build Rust)
// $libPath = __DIR__ . '/ffi/lib/data_engine.so';
$engine = new DataEngine();

$products = [];
$totalData = 1000000;
$chunkSize = 50000;
$currentChunk = [];

// Daftar kata kunci untuk simulasi tags
$tagPool = [
    'promo', 'premium', 'diskon', 'terlaris', 'original', 
    'garansi', 'import', 'lokal', 'limited', 'trending', 
    'murah', 'luxury', 'eco-friendly', 'waterproof'
];


echo "Total target data: $totalData baris" . PHP_EOL;


// === VERSI: 2 (Chunks)

// // MODE 1: Gunakan chunk loading (konsumsi RAM lebih besar ~464MB)
// for ($i = 1; $i <= $totalData; $i++) {
//     // Mengambil 2-3 tag secara acak dari pool
//     $randomKeys = array_rand($tagPool, rand(2, 3));
//     $selectedTags = array_map(function($key) use ($tagPool) {
//         return $tagPool[$key];
//     }, $randomKeys);

//     $products[] = [
//         'id' => $i,
//         'name' => "Produk Ke-$i",
//         'tags' => implode(' ', $selectedTags), // Hasilnya: "promo premium limited"
//         'category' => ($i % 2 == 0) ? 'elektronik' : 'fashion',
//         'price' => rand(100, 5000),
//         'stock' => rand(0, 50)
//     ];
// }
// $engine->loadInChunks($products, $chunkSize);

// MODE 2: Gunnakan append_data (TERBAIK Manual Append - RAM efisien ~30MB)
for ($i = 1; $i <= $totalData; $i++) {
    // 1. Buat data satu per satu
    $randomKeys = array_rand($tagPool, random_int(2, 3));
    $selectedTags = array_map(fn($key) => $tagPool[$key], (array)$randomKeys);

    $currentChunk[] = [
        'id' => $i,
        'name' => "Produk Ke-$i",
        'tags' => implode(' ', $selectedTags),
        'category' => ($i % 2 == 0) ? 'elektronik' : 'fashion',
        'price' => random_int(100, 5000),
        'stock' => random_int(0, 50)
    ];

    // 2. Jika chunk sudah penuh, kirim ke Rust dan KOSONGKAN RAM PHP
    if ($i % $chunkSize === 0) {
        $engine->appendChunk($currentChunk); // Fungsi baru di PHP wrapper
        echo "Sent " . $i . " rows to Rust memory...\r";

        $currentChunk = []; // PAKSA KOSONGKAN ARRAY
        // gc_collect_cycles(); // Opsional: paksa garbage collection
    }
}


echo PHP_EOL . "Data loaded successfully to Rust memory." . PHP_EOL;
echo "PHP RAM Usage: " . (memory_get_usage(true) / 1024 / 1024) . " MB" . PHP_EOL;
// echo "Jumlah data di memori Rust: " . $engine->getCount() . PHP_EOL;

// // DEBUG cek sample data
// echo "Mencoba mengambil sample data..." . PHP_EOL;

// $content = $engine->debugFirstItem();
// echo "Isi Data: " . $content . PHP_EOL;

// echo "Selesai debug." . PHP_EOL;
// exit;
// // END DEBUG

// Segera hapus array PHP setelah data aman di memori Rust
unset($products);
unset($currentChunk);
gc_collect_cycles();

echo "Memory PHP saat ini: " . (memory_get_usage(true) / 1024 / 1024) . " MB" . PHP_EOL;

// Filter Exact Match (Equals)
$start = microtime(true);
$electronics = $engine->filterOnly('category', 'elektronik', 'equals');
$end = microtime(true);
echo "Filter Equals: Ditemukan " . count($electronics) . " data dalam " . ($end - $start) . " detik" . PHP_EOL;


// Filter Perbandingan (Greater Than)
$start = microtime(true);
// Mode 'gt' sesuai dengan struct GreaterThanFilter di Rust
$expensiveProducts = $engine->filterOnly('price', 3500, 'gt');
$end = microtime(true);
echo "Filter GT: Ditemukan " . count($expensiveProducts) . " data mahal dalam " . ($end - $start) . " detik" . PHP_EOL;


// Contoh: Cari produk yang harganya di bawah 500
$start = microtime(true);
$cheapItems = $engine->filterOnly('price', 500, 'lt');
$end = microtime(true);
echo "Filter LT: Ditemukan " . count($cheapItems) . " data murah dalam " . ($end - $start) . " detik" . PHP_EOL;


// Contoh: Cari produk yang mengandung kata "premium" di kolom "tags"
$start = microtime(true);
$searchResult = $engine->filterOnly('tags', 'premium', 'contains');
$end = microtime(true);
echo "Filter Contains: Ditemukan " . count($searchResult) . " data dengan tags premium " . ($end - $start) . " detik" . PHP_EOL;
// === END VERSI: 2


// // === VERSI: 1 (Slower + RAM ~478MB)

// // Simulasi Data Besar (Contoh: 1000.000 data produk)
// for ($i = 1; $i <= $totalData; $i++) {
//     // Mengambil 2-3 tag secara acak dari pool
//     $randomKeys = array_rand($tagPool, rand(2, 3));
//     $selectedTags = array_map(function($key) use ($tagPool) {
//         return $tagPool[$key];
//     }, $randomKeys);

//     $products[] = [
//         'id' => $i,
//         'name' => "Produk Ke-$i",
//         'tags' => implode(' ', $selectedTags), // Hasilnya: "promo premium limited"
//         'category' => ($i % 2 == 0) ? 'elektronik' : 'fashion',
//         'price' => rand(100, 5000),
//         'stock' => rand(0, 50)
//     ];
// }

// echo "Total data products: " . count($products) . " baris" . PHP_EOL;
// echo "PHP RAM Usage: " . (memory_get_usage(true) / 1024 / 1024) . " MB" . PHP_EOL;
// // exit;

// // Filter Exact Match (Equals)
// $start = microtime(true);
// $electronics = $engine->filter($products, 'category', 'elektronik', 'equals');
// $end = microtime(true);
// echo "Filter Equals: Ditemukan " . count($electronics) . " data dalam " . ($end - $start) . " detik" . PHP_EOL;


// // Filter Perbandingan (Greater Than)
// $start = microtime(true);
// // Mode 'gt' sesuai dengan struct GreaterThanFilter di Rust
// $expensiveProducts = $engine->filter($products, 'price', 3500, 'gt');
// $end = microtime(true);
// echo "Filter GT: Ditemukan " . count($expensiveProducts) . " data mahal dalam " . ($end - $start) . " detik" . PHP_EOL;


// // Contoh: Cari produk yang harganya di bawah 500
// $start = microtime(true);
// $cheapItems = $engine->filter($products, 'price', 500, 'lt');
// $end = microtime(true);
// echo "Filter LT: Ditemukan " . count($cheapItems) . " data murah dalam " . ($end - $start) . " detik" . PHP_EOL;


// // Contoh: Cari produk yang mengandung kata "premium" di kolom "tags"
// $start = microtime(true);
// $searchResult = $engine->filter($products, 'tags', 'premium', 'contains');
// $end = microtime(true);
// echo "Filter Contains: Ditemukan " . count($searchResult) . " data dengan tags premium " . ($end - $start) . " detik" . PHP_EOL;
// // === END VERSI:1




// // Contoh penggunaan dalam Model atau Service
// class ProductService 
// {
//     private $dataEngine;

//     public function __construct(DataEngine $engine) {
//         $this->dataEngine = $engine;
//     }

//     public function getOutOfStockExpensiveProducts(array $allProducts) {
//         // Step 1: Filter harga > 4000
//         $expensive = $this->dataEngine->filter($allProducts, 'price', 4000, 'gt');

//         // Step 2: Filter stok yang habis (0)
//         return $this->dataEngine->filter($expensive, 'stock', 0, 'equals');
//     }
// }