<?php
/**
 * Global process request and Response output
 * 
 * @author LutviP19 <lutvip19@gmail.com>
 * @package PHP-Microdata(PMD)
 */
// process_request.php

// Load App
use App\Core\Support\App;

// Automatically validate the JSON format and sanitize JSON with FFI then JSON is converted to $_REQUEST
if (is_json_request() && !handle_json_request()) {
    json_response([], 406, "Invalid JSON", ["input" => "Invalid JSON format."]);
    die();
}

// Clean all $_REQUEST input after conversion
$_REQUEST = sanitize($_REQUEST);
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$requestMethod = $_SERVER["REQUEST_METHOD"];
$_REQUEST = array_merge($_REQUEST, $_POST, $_GET);
// dd($_REQUEST, true);

// Check var $modelName
if (!$modelName || !$model || !$modelPath) {
    throw new Exception("var modelName not set.");
}

// Validate Struct and Refine $_REQUEST to their native data type
if (is_json_request() && file_exists($structPath)) {
    $_REQUEST = App::validateStruct($structPath);
}

// Parse $dataModel and construct to standart JSON specs response
$dataModel = App::loadModel($modelPath);
$data = $dataModel["data"] ?? [];
$status = $dataModel["status"] ?? 200;
$message = $dataModel["message"] ?? "";
$errors = $dataModel["errors"] ?? [];

// Output Handler
if (is_json_request()) {
    json_response($data, $status, $message, $errors);
} else {
    // dd($data);
    extract($data);
}
