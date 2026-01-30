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
 * @param  string $db_name
 *
 * @return string
 */
function database_path($db_name)
{
    return BASE_PATH . 'storage/database/' . $db_name;
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
    return BASE_PATH . 'storage/logs/' . $log_name;
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
            $decoded = json_decode($rawInput, true);

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
 * Sistem logging sederhana dengan level kategori.
 */
if (!function_exists('write_log')) {
    function write_log($message, $moduleName = '', $level = 'info', $file = '') {
        // Tentukan path folder log
        $logDir = BASEPATH . '/storage/logs/';
        $logFile = $logDir . $file ?? 'app.log';

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