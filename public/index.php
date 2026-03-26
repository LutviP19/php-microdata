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
$urlSegments = explode('/', trim($requestPath, '/'));
$page = ($urlSegments[0] !== '') ? $urlSegments[0] : 'dashboard';
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
// // ===========================================

// Load Router and Mapping Models
include BASEPATH . "/config/router.php";
$loader = new \App\Core\Support\RecursiveModelLoader($models);
$resolved = $loader->resolve($page);
// dd($resolved);

// Jika tidak ketemu secara otomatis, cek manual router.php (Fallback)
if (!$resolved && isset($router[$page])) {
    $manualPath = $router[$page]; // e.g., 'Admin/User'
    $resolved = $loader->resolve(str_replace('/', '-', strtolower($manualPath)));
}
// dd($resolved);

if ($resolved) {

    // Load Model Utama
    $modelPath = $resolved['modelPath'];
    $modelName = $resolved['modelName'];
    $structName = $resolved['structName'];
    $structPath = $resolved['structPath'];
    $dataName = $resolved['dataName'];
    $dataPath = $resolved['dataPath'];
    $model = $resolved['model'];

    if (file_exists($modelPath)) {
        // Parameter ID untuk edit/detail
        if (isset($urlSegments[1]) && is_numeric($urlSegments[1])) {
            $_GET['id'] = (int) $urlSegments[1];
        }
        // dd($_GET);

        // Jalankan proses request
        $page = strtolower($model);
        include_once BASEPATH . '/app/Core/process_request.php';
    } else {
        // Handle 404
        $loader->notFoundHandler($model, $modelPath);
    }
} else {
    // Handle 404 - API JSON Only
    $model = $router[$page];
    $modelPath = "/app/Models/" . $model . "Model.php";
    $loader->notFoundHandler($model, $modelPath, true);
}


// Load HTMX View
// Cek apakah ini request dari HTMX
$isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

// Tentukan apakah ke halaman login
$isLoginPage = ($page === 'login');
$isPageExists = $viewPath = realpath(BASEPATH . "/views/partial/" . $page . ".php");
// dd($viewPath);

// Handle 404 - Non HTMX
if(!$isHtmx && !$isPageExists) {
    http_response_code(404);
    include BASEPATH . "/views/error/404.php";
    exit();
}

if($isHtmx) {
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