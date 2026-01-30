<?php 

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/..');
}
/**
 * Require Core init File.
 */
require_once BASEPATH .'/app/Core/init.php';

$logFile = logs_path('/cron_log.txt'); // read logfile every 5 seconds
if (file_exists($logFile)) {
    $content = file_get_contents($logFile) ?: 'Menunggu log...';
    echo "<pre>" . nl2br(htmlspecialchars($content)) . "</pre>";
} else {
    echo "Belum ada log.";
}