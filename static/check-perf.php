<?php 

// file: static/check-perf.php

// Pastikan hanya admin yang bisa akses jika di production
// authorize('admin-view'); 

$opcache_status = function_exists('opcache_get_status') ? opcache_get_status() : null;
$is_worker = getenv('FRANKENPHP_WORKER') === '1' ? 'Aktif' : 'Tidak Aktif';

echo "## Status Infrastruktur PHP\n";
echo "---\n";
echo "* **Engine:** FrankenPHP\n";
echo "* **Worker Mode:** {$is_worker}\n";
echo "* **PHP Version:** " . PHP_VERSION . "\n";

if ($opcache_status && $opcache_status['opcache_enabled']) {
    $used_mem = round($opcache_status['memory_usage']['used_memory'] / 1024 / 1024, 2);
    $free_mem = round($opcache_status['memory_usage']['free_memory'] / 1024 / 1024, 2);
    $hit_rate = round($opcache_status['opcache_statistics']['opcache_hit_rate'], 2);

    echo "* **OPcache:** Aktif ✅\n";
    echo "* **Memory Used:** {$used_mem} MB\n";
    echo "* **Memory Free:** {$free_mem} MB\n";
    echo "* **Hit Rate:** {$hit_rate}%\n";
} else {
    echo "* **OPcache:** Tidak Aktif/Tidak Terinstal ❌ (Ini penyebab Latency tinggi!)\n";
}

echo "\n## Resource Usage (Current Request)\n";
echo "---\n";
echo "* **Memory Peak:** " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
echo "* **Execution Time:** " . round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2) . " ms\n";