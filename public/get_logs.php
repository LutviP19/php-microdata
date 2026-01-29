<?php 

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/..');
}

$logFile = BASEPATH . '/cron_log.txt'; // read logfile every 5 seconds
if (file_exists($logFile)) {
    $content = file_get_contents($logFile) ?: 'Menunggu log...';
    echo "<pre>" . nl2br(htmlspecialchars($content)) . "</pre>";
} else {
    echo "Belum ada log.";
}