<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// router.php
// simple routing views with HTMX and JSON RestAPI

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}
/**
 * Require Core init File.
 */
require_once BASEPATH .'/app/Core/init.php';

$page = $_GET['page'] ?? 'dashboard';
        
// Auto Load Model if exists
$modelName = ucwords($page) . 'Model';
$modelPath = BASEPATH . "/app/Models/" . $modelName . ".php";
if (file_exists($modelPath)) {
    include_once $modelPath;
    // Include Core Global process_request File.
    include_once BASEPATH .'/app/Core/process_request.php';
}

// Load View
if(isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true') {   
    $viewPath = BASEPATH . "/views/" . $page . ".php";
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        include BASEPATH . "/views/error/404.php";
    }
} else {
    include "index.php";
}
