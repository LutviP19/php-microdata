<?php 

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


$logFile = logs_path('cron_log.txt'); // read logfile every 5 seconds
if (file_exists($logFile)) {
    $content = file_get_contents($logFile) ?: 'Menunggu log...';
    // echo "<pre>" . nl2br(htmlspecialchars($content)) . "</pre>";
    echo "<div class='leading-none font-mono text-xs md:text-sm'>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
    echo "</div>";
} else {
    echo "Belum ada log.";
}