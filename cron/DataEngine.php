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

// 2. Simulasi Data Besar (Contoh: 100.000 data produk)
$products = [];
for ($i = 1; $i <= 1000000; $i++) {
    $products[] = [
        'id' => $i,
        'name' => "Produk Ke-$i",
        'category' => ($i % 2 == 0) ? 'elektronik' : 'fashion',
        'price' => rand(100, 5000),
        'stock' => rand(0, 50)
    ];
}

echo "Total data awal: " . count($products) . " baris" . PHP_EOL;
// exit;

// Filter Exact Match (Equals)
$start = microtime(true);

$electronics = $engine->filter($products, 'category', 'elektronik', 'equals');

$end = microtime(true);
echo "Filter Equals: Ditemukan " . count($electronics) . " data dalam " . ($end - $start) . " detik" . PHP_EOL;


// Filter Perbandingan (Greater Than)
$start = microtime(true);

// Mode 'gt' sesuai dengan struct GreaterThanFilter di Rust
$expensiveProducts = $engine->filter($products, 'price', 3500, 'gt');

$end = microtime(true);
echo "Filter GT: Ditemukan " . count($expensiveProducts) . " data mahal dalam " . ($end - $start) . " detik" . PHP_EOL;




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