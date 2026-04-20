<?php 
/**
 * @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}

require_once BASEPATH .'/app/Core/init.php';

//====== Middleware
// Jalankan fungsi CORS
handle_cors();

// Filter IP - API Only
if (is_json_request()) {
    check_ip_access(clientIP());
}

// Validasi API Key
if (!validateApiKey()) {
    json_response([], 401, 'Unauthorized', ['auth' => 'Unauthorized: Invalid API Key']);
}
//====== End Middleware

// --- SEO ROUTING LOGIC ---
// Mengambil path dari URL (misal: /dashboard)
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// dd($requestPath);
$lastSegment = basename($requestPath);
$urlSegments = explode('/', trim($requestPath, '/'));
$page = ($urlSegments[0] !== '') ? $urlSegments[0] : 'dashboard';
// dd($page);
$resolved = null;
$isPageExists = true;
$isAllowAccess = true;
// -------------------------

// // --- Test Auth Gate
// // Set Perrmissions 1: Admin, 2: Manager, 3: Staff 
// \App\Core\Auth\Gate::loadAbilities(1, 1);
// \App\Core\Auth\Gate::loadAbilities(2, 2);
// $permission = 'asset-delete';
// \App\Core\Auth\Gate::authorize($permission);
// // ===========================================

// Load Router and Mapping Models
include BASEPATH . "/config/router.php";
$loader = new \App\Core\Support\RecursiveModelLoader($models);

// Cek router.php (Main Route to Models)
if (isset($router[$page])) {
    $normalPath = $router[$page];
    $resolved = $loader->resolve(formatRoutePath($normalPath)); // Mengubah format "v1/Dashboard" menjadi "v1-dashboard"
} else {
    $customPath = $router[$requestPath] ?? null;
    if (is_numeric($lastSegment)) {
        $fixReqPath = str_replace("/{$lastSegment}", "", $requestPath);
        $customPath = $router[$fixReqPath];
    }

    if ($customPath && ($resolved = $loader->resolve(formatRoutePath($customPath)))) {
        $resolved['page'] = ltrim($requestPath, '/');
    }
}
// dd($resolved, true); //debug: $resolved | $page

// Jika tidak resolve fallback ke default $page
if (!$resolved) {
    $resolved = $loader->resolve($page);
}
// dd($resolved, true);

if ($resolved && file_exists($resolved['modelPath'])) {
    // Load Model Utama
    $modelPath = $resolved['modelPath'];
    $modelName = $resolved['modelName'];
    $structName = $resolved['structName'];
    $structPath = $resolved['structPath'];
    $dataName = $resolved['dataName'];
    $dataPath = $resolved['dataPath'];
    $model = $resolved['model'];

    if ($model && file_exists($modelPath)) {
        // Parameter ID untuk edit/detail
        // if (isset($urlSegments[1]) && is_numeric($urlSegments[1])) {
        if (is_numeric($lastSegment)) {
            $_GET['id'] = (int) $lastSegment;
        }
        // dd($_GET);

        // Jalankan proses request
        $page = $resolved['page'];

        // Middleware Model for API Only
        if(!is_json_request() || !expects_json()) {
            if(!file_exists(realpath(BASEPATH . "/views/partial/" . $page . ".php")))
            $loader->notFoundHandler($page, $modelPath);
        }

        include_once BASEPATH . '/app/Core/process_request.php';
    } else {
        // Handle 404
        $loader->notFoundHandler($page, $modelPath);
    }
} else {
    // Handle 404 - API JSON Only
    $model = $router[$page] ?? $page;
    $modelPath = "/app/Models/" . $model . "Model.php";
    $loader->notFoundHandler($model, $modelPath, true);
}


// Load HTMX View
// Tentukan apakah ke halaman login
$isLoginPage = ($page === 'login');
$isPageExists = $viewPath = realpath(BASEPATH . "/views/partial/" . $page . ".php");
// dd($viewPath);

// Handle 404 - Non HTMX
if(!isHtmx() && !$isPageExists) {
    http_response_code(isHtmx() ? 200 : 404);
    include BASEPATH . "/views/error/404.php";
    exit();
}

if(isHtmx()) {
    if ($viewPath && file_exists($viewPath) && strpos($viewPath, realpath(BASEPATH . "/views/")) === 0) {
        include $viewPath;
    } else {
        if ($isLoginPage) {
            // Render hanya halaman login (tanpa sidebar/nav)
            include BASEPATH . "/views/login.php";
        } else {
            http_response_code(isHtmx() ? 200 : 404);
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