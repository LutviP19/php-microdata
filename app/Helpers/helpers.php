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
 * @return void
 */
function dd($data = [], $json = false)
{
    if($json) {
        die(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
    
    echo "<pre>", var_dump($data), "</pre>";
    die();
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

        if(!empty($errors)) {
            unset($data['data']);
            $data['errors'] = $errors;
        }

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