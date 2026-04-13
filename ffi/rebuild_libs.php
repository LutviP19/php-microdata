<?php

/**
 * Automate Rebuild Rust & Go Libraries for PHP-FFI
 * Location: /ffi/rebuild_libs.php
 */

$baseDir = __DIR__;
$sourceDir = $baseDir . '/source';
$outputDir = $baseDir . '/lib';

// Pastikan folder output ada
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "=== FFI Library Rebuild Tool ===\n";
echo "Source: $sourceDir\n";
echo "Output: $outputDir\n\n";

$folders = array_diff(scandir($sourceDir), ['.', '..']);

foreach ($folders as $folder) {
    $path = $sourceDir . '/' . $folder;
    if (!is_dir($path)) continue;

    echo "Checking: [$folder]... ";

    // 1. Logika Penemuan Rust (Check folder /src)
    if (is_dir($path . '/src') && file_exists($path . '/Cargo.toml')) {
        echo "Found RUST. Building...\n";
        
        // Build menggunakan cargo
        $cmd = "cd $path && cargo build --release";
        shell_exec($cmd);

        // Cari file .so hasil build
        $soFile = $path . "/target/release/lib{$folder}.so";
        if (file_exists($soFile)) {
            copy($soFile, $outputDir . "/{$folder}.so");
            echo "SUCCESS: $folder.so generated.\n";
        } else {
            echo "ERROR: Failed to find compiled Rust library.\n";
        }
    } 
    // 2. Logika Penemuan Go (Check file .go di root folder lib)
    else {
        $goFiles = glob($path . "/*.go");
        if (!empty($goFiles)) {
            echo "Found GO. Building...\n";
            
            // Mengambil file utama (biasanya main.go atau nama_folder.go)
            $mainGo = $goFiles[0]; 
            $outSo = $outputDir . "/{$folder}.so";
            
            // Build menggunakan go build
            $cmd = "cd $path && go build -buildmode=c-shared -o $outSo $mainGo";
            shell_exec($cmd);
            
            if (file_exists($outSo)) {
                echo "SUCCESS: $folder.so generated.\n";
                // Bersihkan file header .h yang dihasilkan Go jika tidak diperlukan
                @unlink($outputDir . "/{$folder}.h");
            } else {
                echo "ERROR: Failed to compile Go library.\n";
            }
        } else {
            echo "SKIP: No source files found.\n";
        }
    }
    echo str_repeat("-", 40) . "\n";
}

echo "\nDone! All libraries are synchronized with current system architecture.\n";