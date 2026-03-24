<?php 
/**
 * @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}

require_once BASEPATH .'/app/Core/init.php';

// --- SEO ROUTING LOGIC ---
// Mengambil path dari URL (misal: /dashboard)
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// dd($requestPath);
$segments = explode('/', trim($requestPath, '/'));
$page = ($segments[0] !== '') ? $segments[0] : 'dashboard';
// dd($page);
$isPageExists = true;
$isAllowAccess = true;
// -------------------------

// // --- Test Auth Gate
// $isAllowAccess = false;
// $permission = 'asset-delete';
// if (!$isAllowAccess) {
//     if(is_json_request()) {
//         $message = "You don't have access[$permission]";
//         $errors = [
//             'auth' => 'Forbidden to access: ' . $permission
//         ];
//         json_response([], 403, $message, $errors);
//     } else {
//         http_response_code(403);
//         include BASEPATH . "/views/error/403.php";
//         exit();
//     }
// }


// Set the model name in the router file
include BASEPATH . "/config/router.php";
$model = $router[$page] ?: null;
// dd($model);
if(!empty($model)) {
    // Auto Load Model if exists    
    $structName = ucwords($model) . 'Struct';
    $modelName = ucwords($model) . 'Model';
    $structPath = BASEPATH . "/app/Structs/$model/" . $structName . ".php";
    $modelPath = BASEPATH . "/app/Models/" . $modelName . ".php";
    if (file_exists($modelPath)) {
        // Cek url edit, lalu tambahkan param $_GET['id']
        if(isset($segments[1]) && is_numeric($segments[1])) {
            $_GET['id'] = (int) $segments[1];
        }
        // dd($_GET);

        // Set page same as model route
        $page = strtolower($model);

        include_once BASEPATH .'/app/Core/process_request.php';
    } else {
        if(is_json_request()) {
            $message = "Model '$model' Not Found";
            $errors = [
                'model' => 'Model not found: ' . str_replace(BASEPATH, '', $modelPath)
            ];
            json_response([], 404, $message, $errors);
        } else {
            $isPageExists = false;
            http_response_code(404);
            include BASEPATH . "/views/error/404.php";
            exit();
        }
    }
}


// Load HTMX View
// Cek apakah ini request dari HTMX
$isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

// Tentukan apakah kita di halaman login
$isLoginPage = ($page === 'login');

if($isHtmx) {
    $viewPath = realpath(BASEPATH . "/views/partial/" . $page . ".php");
    if ($viewPath && file_exists($viewPath) && strpos($viewPath, realpath(BASEPATH . "/views/")) === 0) {
        include $viewPath;
    } else {
        if ($isLoginPage) {
            // Render hanya halaman login (tanpa sidebar/nav)
            include BASEPATH . "/views/login.php";
        } else {
            // http_response_code(404);
            include BASEPATH . "/views/error/404.php";
        }
    }
} else {
    if ($isLoginPage) {
        // Render hanya halaman login (tanpa sidebar/nav)
        include BASEPATH . "/views/login.php";
    } else {
        // Jika akses langsung (bukan AJAX), load wrapper utama (index.php)
        include BASEPATH . "/views/index.php";
    }
}