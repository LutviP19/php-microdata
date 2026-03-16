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
// -------------------------

// Static router untuk execute file php
include "static-router.php";


// Look for the model name in the router file
include BASEPATH . "/config/router.php";
$model = $router[$page] ?: null;
// dd($model);
if(!empty($model)) {
    // Auto Load Model if exists
    $structName = ucwords($model) . 'Struct';
    $modelName = ucwords($model) . 'Model';
    $structPath = BASEPATH . "/app/Structs/" . $structName . ".php";
    $modelPath = BASEPATH . "/app/Models/" . $modelName . ".php";
    if (file_exists($modelPath)) {
        // Cek url edit, lalu tambahkan param $_GET['id']
        if(isset($segments[1]) && is_numeric($segments[1])) {
            $_GET['id'] = (int) $segments[1];
        }
        // dd($_GET);

        include_once $modelPath;
        include_once BASEPATH .'/app/Core/process_request.php';
    }
}



// Load View
// Cek apakah ini request dari HTMX
$isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

if($isHtmx) {
    $viewPath = realpath(BASEPATH . "/views/partial/" . $page . ".php");
    if ($viewPath && file_exists($viewPath) && strpos($viewPath, realpath(BASEPATH . "/views/")) === 0) {
        include $viewPath;
    } else {
        // http_response_code(404);
        include BASEPATH . "/views/error/404.php";
    }
} else {
    // Tentukan apakah kita di halaman login
    $isLoginPage = ($page === 'login');
    if ($isLoginPage) {
        // Render hanya halaman login (tanpa sidebar/nav)
        include BASEPATH . "/views/login.php";
    } else {
        // Jika akses langsung (bukan AJAX), load wrapper utama (index.php)
        include BASEPATH . "/views/index.php";
    }
}