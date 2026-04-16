<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


// Fobonacci
$ffi1 = FFI::cdef(
    "int Fibonacci(int n);", // using inline header
    BASEPATH_FFI . '/lib/fibonacci.so',
);

function fibonacci(int $n): int
{
    if ($n <= 1) {
        return $n;
    }

    return fibonacci($n - 1) + fibonacci($n - 2);
}

$start = microtime(true);
$result = $ffi1->Fibonacci(35);
$end = microtime(true);
$time = $end - $start;

echo "[" . date('Y-m-d H:i:s') . "] Run Fibonacci..." . PHP_EOL;
echo "PHP-FFI Go Fibonacci result: {$result}. It took {$time} seconds to compute.", PHP_EOL;


$start = microtime(true);
$result = fibonacci(35);
$end = microtime(true);
$time = $end - $start;

echo "PHP Native Fibonacci result: {$result}. It took {$time} seconds to compute.", PHP_EOL;


// Concurrent process
echo "[" . date('Y-m-d H:i:s') . "] Run PHP-FFI Go Concurrent process..." . PHP_EOL;

// 1. Inisialisasi FFI (Pastikan header file valid)
$ffi3 = FFI::cdef(
    file_get_contents(BASEPATH_FFI . '/lib/concurrency.h'), // we are using file header on this lib
    BASEPATH_FFI . '/lib/concurrency.so',
);

$imagePaths = [
    "pathA",
    "pathB",
    "pathC",
    "pathD",
];
$imagesCount = count($imagePaths);

$cArray = $ffi3->new("char*[" . count($imagePaths) . "]"); // create a new array with fixed size
$buffers = []; // this will just hold variables to prevent PHP's garbage collection

foreach ($imagePaths as $i => $path) {
    $size = strlen($path); // the size to allocate in bytes
    $buffer = $ffi3->new("char[" . ($size + 1) . "]"); // create a new C string of length +1 to add space for null terminator
    FFI::memcpy($buffer, $path, $size); // copy the content of $path to memory at $buffer with size $size
    $cArray[$i] = $ffi3->cast("char*", $buffer); // cast it to a C char*, aka a string
    $buffers[] = $buffer; // assigning it to the $buffers array ensures it doesn't go out of scope and PHP cannot garbage collect it
}

$failedOut = $ffi3->new("char**"); // create a string array in C, this will be passed as reference
$failedCount = $ffi3->new("int"); // create an integer which will be passed as reference

$start = microtime(true);

// 4. Eksekusi Fungsi Go
$ffi3->ResizeImages(
    $cArray,
    $imagesCount,
    FFI::addr($failedOut),
    FFI::addr($failedCount),
);

$end = microtime(true);
$time = $end - $start;

// 5. Ambil nilai dari CData
$count = $failedCount->cdata; 

echo "Failed items: {$count}" . PHP_EOL;

if ($count > 0) {
    for ($i = 0; $i < $count; $i++) {
        // Gunakan casting eksplisit jika diperlukan
        if(!$is_cron)
        echo " - " . FFI::string($failedOut[$i]) . PHP_EOL;
    }
    
    /**
     * PENTING: Jika Go menggunakan 'C.CString' untuk failedOut, 
     * Anda WAJIB memanggil fungsi free dari sisi Go/C agar tidak memory leak.
     * Contoh: $ffi3->FreeStringArray($failedOut, $count);
     */
}

echo "Processing took: {$time} seconds" . PHP_EOL;

// 6. Cleanup variabel besar sebelum pindah ke proses lain
unset($cArray, $buffers, $failedOut);
gc_collect_cycles();

// Webcrawler Concurrent process
include BASEPATH . '/cron/crawler.php';