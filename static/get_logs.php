<?php 
declare(strict_types=1);

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


$logFile = logs_path('cron_log.txt'); // read logfile every (x) seconds
$maxLines = 100; // Ambil 100 baris terakhir saja
$mode = 'fopen'; // fopen | tail

if (file_exists($logFile)) {

    // --- JALANKAN ROTASI SEBELUM MEMBACA ---
    // Rotate jika > 2MB, simpan backup selama 1 hari saja agar hemat disk
    // rotate_log_if_large($logFile, 2 * 1024 * 1024, 1);
    rotate_log_if_large($logFile, 2 * 1024, 1); // Simulasi cepat

    // PHP - open file
    if($mode === 'fopen') {
        $file = fopen($logFile, 'r');
        $lines = [];
        
        // Pindah ke posisi akhir file
        fseek($file, 0, SEEK_END);
        $pos = ftell($file);
        $count = 0;

        // Baca mundur per karakter untuk mencari baris baru
        while ($pos > 0 && $count < $maxLines) {
            fseek($file, --$pos);
            if (fgetc($file) == "\n") {
                $count++;
            }
        }

        if(!$count) {
            echo "<div class='leading-none font-mono text-xs md:text-sm'>";
            echo "<pre>Belum ada log...</pre>";
            echo "</div>";
        } else {
            // Baca sisa file dari posisi tersebut ke bawah
            // echo "<div class='leading-none font-mono text-xs md:text-sm bg-black text-gray-300'>";
            echo "<div class='leading-none font-mono text-xs md:text-sm'>";
            echo "<pre>" . htmlspecialchars(stream_get_contents($file)) . "</pre>";
            echo "</div>";
        }

        fclose($file);
    }
    
    // Ambil 100 baris terakhir secara instan (tail - Linux Only)
    if($mode === 'tail') {
        $content = tail_logs($logFile, $maxLines);

        if(is_null($content)) {
            echo "<div class='leading-none font-mono text-xs md:text-sm'>";
            echo "<pre>Belum ada log...</pre>";
            echo "</div>";
        } else {
            // echo "<pre>" . nl2br(htmlspecialchars($content)) . "</pre>";
            echo "<div class='leading-none font-mono text-xs md:text-sm'>";
            echo "<pre>" . htmlspecialchars($content) . "</pre>";
            echo "</div>";
        }
    }

} else {
    echo "Belum ada log.";
}
exit();