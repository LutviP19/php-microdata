<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/../..");
}


/**
 * dump the data and kill the page.
 *
 * @param array $data
 * @return string
 */
function dd($data = [], $json = false)
{
    // Debug output JSON
    if($json) {
        die(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
    
    // Debug output TEXT/HTML
    echo "<pre>", var_dump($data), "</pre>";
    die();
}

/**
 * default database path for sqlite
 *
 * @param  string $key
 *
 * @return string
 */
function storage_path($filePath)
{
    return BASEPATH . '/storage/' . $filePath;
}

/**
 * default database path for sqlite
 *
 * @param  string $db_name
 *
 * @return string
 */
function database_path($db_name)
{
    return BASEPATH . '/storage/database/' . $db_name;
}

/**
 * default log path
 *
 * @param  string $log_name
 *
 * @return string
 */
function logs_path($log_name)
{
    return BASEPATH . '/storage/logs/' . $log_name;
}

/**
 * Sanitize the given uri.
 *
 * @param string $uri
 * @return string
 */
function sanitizeUri($uri)
{
    if (str_starts_with($uri, '/')) {
        $uri = ltrim($uri, '/');
    }

    return filter_var(
        $uri,
        FILTER_SANITIZE_URL
    );
}

/**
 * Create a url from a given uri.
 *
 * @param string $uri
 * @return string
 */
function url($uri = '')
{
    $uri = sanitizeUri($uri);
    return config('app.url')."/{$uri}";
}

/**
 * Helper untuk memanggil file di folder public/assets
 */
if (!function_exists('asset')) {
    function asset($path) {
        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . '/assets/' . ltrim($path, '/');
    }
}

/** 
 * Memuat variabel dari file .env ke dalam lingkungan PHP
 */
if (!function_exists('load_env')) {
    function load_env($path = null) {
        if ($path === null) {
            $path = BASEPATH . '/.env';
        }

        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Abaikan baris yang dimulai dengan komentar #
            if (strpos(trim($line), '#') === 0) continue;

            // Pecah berdasarkan tanda sama dengan (=)
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Bersihkan kutipan jika ada (misal: "SECRET_KEY" atau 'SECRET_KEY')
            $value = trim($value, '"\'');

            $value = trim($value, " \t\n\r\0\x0B\"'");

            // --- LOGIKA SUBSTITUSI VARIABEL ---
            // Mencari pola ${VAR_NAME}
            preg_match_all('/\${([^}]+)}/', $value, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $embeddedVar) {
                    // Ambil nilai dari $_ENV atau getenv yang sudah diproses sebelumnya
                    $replacement = $_ENV[$embeddedVar] ?? getenv($embeddedVar) ?? '';
                    $value = str_replace('${' . $embeddedVar . '}', $replacement, $value);
                }
            }
            // ----------------------------------

            // Masukkan ke dalam environment PHP
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
        return true;
    }
}

/**
 * Mengonversi variabel environment pilihan ke format JSON untuk Frontend.
 */
if (!function_exists('env_to_json')) {
    function env_to_json(array $keys) {
        $output = [];
        foreach ($keys as $key) {
            $output[$key] = env($key);
        }
        return json_encode($output);
    }
}

/**
 * get config
 *
 * @param  [string] $key
 *
 * @return string
 */
function config($key)
{
    return \App\Core\Support\Config::get($key);
}

/**
 * Mengambil nilai dari environment dengan dukungan nilai default
 */
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        
        // Konversi tipe data string ke boolean jika perlu
        switch (strtolower($value)) {
            case 'true': return true;
            case 'false': return false;
            case 'null': return null;
        }

        return $value;
    }
}

/**
 * Cek apakah request JSON.
 */
if (!function_exists('is_json_request')) {
    function is_json_request() {
        return isset($_SERVER['CONTENT_TYPE']) && 
                    stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
    }
}

/**
 * Sanitasi input berdasarkan tipe data.
 * Mendukung string, integer, float, boolean, dan array secara rekursif.
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = sanitize($value);
            }
        } else {
            if (is_string($data)) {
                // Hapus whitespace di awal/akhir
                $data = trim($data);
                // Hilangkan tag HTML untuk mencegah XSS
                $data = strip_tags($data);
                // Ubah karakter khusus menjadi entitas HTML
                $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            } elseif (is_numeric($data)) {
                // Jika numeric, pastikan tidak ada karakter aneh
                $data = filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            }
        }
        return $data;
    }
}

/**
 * Mendeteksi payload JSON dan merubahnya menjadi array di $_REQUEST
 * Berguna untuk integrasi API atau library frontend yang mengirim JSON.
 */
if (!function_exists('handle_json_request')) {
    function handle_json_request() {
        // Cek apakah Content-Type adalah application/json
        $contentType = $_SERVER["CONTENT_TYPE"] ?? $_SERVER["HTTP_CONTENT_TYPE"] ?? '';
        
        if (stripos($contentType, 'application/json') !== false) {
            // Ambil data mentah dari body
            $rawInput = file_get_contents('php://input');

            // Sanitize Json
            $dataJson = sanitizeJson($rawInput);
            $decoded = json_decode($dataJson, true);
            // dd($decoded);
            if(isset($decoded['error'])) {
                return false;
            }

            // Jika JSON valid, gabungkan ke dalam $_REQUEST
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $_REQUEST = array_merge($_REQUEST, $decoded);
                
                // Opsional: Gabungkan juga ke $_POST jika Anda lebih suka menggunakannya
                $_POST = array_merge($_POST, $decoded);
                return true;
            }
        }        
        return false;
    }
}

/**
 * Mendeteksi payload JSON dan merubahnya menjadi array di $_REQUEST
 * Berguna untuk integrasi API atau library frontend yang mengirim JSON.
 */
if (!function_exists('handle_response_error')) {
    function handle_response_error($data, $dataModel) {

        if(isset($dataModel['errors'])) {
            // Parse Errors
            $errors = $dataModel['errors'];
            $status = $errors['status'] ?? 417;
            $message = $errors['message'] ?? 'Your expectations not match to server capabilities.';

            // clean data
            unset($dataModel);
            unset($data);
            unset($errors['status']);
            unset($errors['message']);

            $data['status'] = $status;
            $data['message'] = $message;
            $data['errors'] =  $errors;
        }

        return $data;
    }
}


/**
 * Mengirimkan respons JSON yang standar dan menghentikan eksekusi script.
 */
if (!function_exists('json_response')) {
    function json_response($data, $status = 200, $message = '', $errors = []) {
        header('Content-Type: application/json');
        http_response_code($status);

        // Format output JSON
        if($message !== '') {
            $data = [
                'statusCode' => $status,
                'message' => $message,
                'data' => $data,
            ];
        } else {
            $data = [
                'statusCode' => $status,
                'data' => $data
            ];
        }
        // Keluarkan errors jika ada
        if(!empty($errors)) {
            unset($data['data']);
            $data['errors'] = $errors;
        }
        // Unset data jika status >= 300
        if ($status >= 300) {
            unset($data['data']);
        }

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
}

/**
 * Mengecek apakah request saat ini mengharapkan atau mengirimkan JSON.
 */
if (!function_exists('expects_json')) {
    function expects_json() {
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        $accept = $_SERVER["HTTP_ACCEPT"] ?? '';
        return stripos($contentType, 'application/json') !== false || 
               stripos($accept, 'application/json') !== false;
    }
}

/**
 * Kompres data JSON
 */
if (!function_exists('compress_payload')) {
    function compress_payload($data) {
        $json = json_encode($data);
        // gzencode menghasilkan format yang kompatibel dengan browser (.gz)
        return gzencode($json, 9); // Level 9 adalah kompresi maksimal

        // // Contoh Penggunaan:
        // $data = ['status' => 'success', 'data' => range(1, 1000)]; // Data besar
        // $compressed = compress_payload($data);

        // header('Content-Encoding: gzip');
        // header('Content-Type: application/json');
        // echo $compressed;
    }
}

/**
 * Dekompres data
 */
if (!function_exists('decompress_payload')) {
    function decompress_payload($payload) {
        return json_decode(gzdecode($payload), true);
    }
}

/**
 * Sistem log sederhana dengan level kategori.
 */
if (!function_exists('write_log')) {
    function write_log($message, $moduleName = '', $level = 'info', $file = '') {
        // Tentukan path folder log
        $logDir = BASEPATH . '/storage/logs/';
        $logFile = $logDir . $file ?? 'app'.str_replace(" ", "_", $level).'.log';

        // Buat direktori jika belum ada
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Format pesan log: [2026-01-30 20:00:00] [LEVEL] Message
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $datatype = gettype($message);
        
        // Jika pesan berupa array atau objek, konversi ke JSON agar terbaca
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }
        
        // Format output log
        $formattedMessage = "[$timestamp][$levelUpper][$moduleName]($datatype): $message" . PHP_EOL;

        // Tulis ke file (APPEND agar tidak menimpa data lama)
        return file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
}

/**
 * Helper untuk format angka atau status agar lebih cantik di log dashboard.
 */
if (!function_exists('format_log_status')) {
    function format_log_status($status) {
        $colors = [
            'error'   => 'text-red-500',
            'success' => 'text-green-500',
            'warning' => 'text-yellow-500',
            'info'    => 'text-blue-500'
        ];
        $colorClass = $colors[strtolower($status)] ?? 'text-gray-400';
        return "<span class='font-bold $colorClass'>" . strtoupper($status) . "</span>";
    }
}

/**
 * Menghasilkan inisial dari nama (misal: "John Doe" -> "JD")
 */
if (!function_exists('get_initials')) {
    function get_initials($name) {
        $words = explode(' ', strtoupper($name));
        $initials = '';
        
        foreach ($words as $w) {
            $initials .= $w[0] ?? '';
        }
        
        return substr($initials, 0, 2);
    }
}

/**
 * Membersihkan newline berlebihan dalam teks
 * * @param string $text Teks input
 * @param int $max_newlines Batas maksimal newline yang diizinkan (default 2)
 * @return string
 */
if (!function_exists('clean_newlines')) {
    function clean_newlines($text, $max_newlines = 2) {
        // Regex untuk mencari baris baru (\r\n atau \n)
        // {{$max_newlines + 1},} artinya cari yang jumlahnya lebih dari batas
        $pattern = "/(\r?\n){" . ($max_newlines + 1) . ",}/";
        
        // Ganti dengan jumlah newline yang diinginkan
        $replacement = str_repeat("\n", $max_newlines);
        
        return preg_replace($pattern, $replacement, trim($text));
    }
}

// Pecah raw text menjadi Array
if (!function_exists('parse_crawler_logs')) {
    function parse_crawler_logs($raw_text) {
        // 1. Bersihkan noise log sistem di awal (Opsional)
        $clean_text = preg_replace('/\[\d{4}-\d{2}-\d{2}.*?\].*?\n/s', '', $raw_text);
        
        // 2. Pecah berdasarkan pemisah END URL
        $blocks = explode('===========================END URL============================', $clean_text);
        $results = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block) || strlen($block) < 5) continue;

            $results[] = parse_scraped_content($block);
        }

        return $results;
    }
}

// Untuk memproses teks mentah hasil scraping atau log tersebut menjadi data terstruktur (Array/JSON)
if (!function_exists('parse_scraped_content')) {
    function parse_scraped_content($block) {
        $entry = [
            'url'      => null,
            'status'   => null,
            'title'    => null,
            'content'  => null,
            'metadata' => ['posted_at' => null, 'author' => null, 'labels' => []],
            'links'    => []
        ];

        // 1. Ekstrak Status & URL Awal
        if (preg_match('/^(https?:\/\/[^\s]+):\s+(\d+)/m', $block, $matches)) {
            $entry['url'] = $matches[1];
            $entry['status'] = (int)$matches[2];
        }

        // 2. Ekstrak Title secara spesifik
        if (preg_match('/Title:\s+(.*?)(?:\s+\||$)/m', $block, $matches)) {
            $entry['title'] = trim($matches[1]);
        }

        // 3. Ekstrak Metadata (Posted & Author)
        if (preg_match('/Diposting pada (.*?) oleh ([^\s\xA0]+)/u', $block, $matches)) {
            $entry['metadata']['posted_at'] = trim($matches[1]);
            $entry['metadata']['author']    = trim($matches[2]);
            
            // Tentukan "Anchor" atau titik potong
            $authorName = $matches[2];
            $searchAnchor = "oleh " . $authorName;

            // 4. Potong Content: Ambil teks HANYA SETELAH nama Author
            $contentStartPos = strpos($block, $searchAnchor);
            if ($contentStartPos !== false) {
                // Ambil sisa teks setelah 'oleh Lutvi'
                $rawContent = substr($block, $contentStartPos + strlen($searchAnchor));
                
                // Bersihkan Content dari noise sisa
                $rawContent = preg_replace('/\x{00a0}/u', ' ', $rawContent); // Bersihkan &nbsp;
                $rawContent = preg_replace('/\s+Daftar Isi\s+/u', ' ', $rawContent); // Buang Daftar Isi
                
                // Ambil teks sampai sebelum bagian "Labels:" atau "Link found:"
                if (preg_match('/^(.*?)(?=Labels:|Link found:|Bagikan :|$)/s', $rawContent, $contentMatches)) {
                    $entry['content'] = trim(preg_replace('/\s+/', ' ', $contentMatches[1]));
                }
            }
        }

        // 5. Ekstrak Labels
        if (preg_match('/Labels:\s*(.*?)\s*Bagikan/s', $block, $matches)) {
            $entry['metadata']['labels'] = array_filter(array_map('trim', explode("\n", $matches[1])));
        }

        // 6. Ekstrak Links
        if (preg_match_all('/Link found:\s+(https?:\/\/[^\s]+)/', $block, $matches)) {
            $entry['links'] = $matches[1];
        }

        return $entry;
    }
}
