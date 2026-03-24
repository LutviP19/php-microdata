<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// process_request.php
// Global process request and Response output


// Load App
use App\Core\Support\App;


// Otomatis validasi format JSON dan sanitize JSON dengan FFI
// kemudian JSON dikonversi ke $_REQUEST
if(is_json_request() && !handle_json_request()) {
    json_response([], 406, 'Invalid JSON', ['input' => 'Invalid JSON format.']);
    die();
}

// Bersihkan seluruh input $_REQUEST setelah di konversi
$_REQUEST = sanitize($_REQUEST);
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$_REQUEST = array_merge($_REQUEST, $_POST, $_GET);
// dd($_REQUEST, true);

// Check var $modelName
if(!isset($modelName)) {
    throw new Exception("var modelName not set.");
}

// Validate Struct and Refine $_REQUEST to their native data type
if (is_json_request() && file_exists($structPath)) {
    $_REQUEST = App::validateStruct($structPath, $model);
}

// Parse $dataModel and construct to standart JSON specs response
$dataModel = App::loadModel($modelPath, $model);
$data = $dataModel['data'] ?? [];
$status = $dataModel['status'] ?? 200;
$message = $dataModel['message'] ?? '';
$errors = $dataModel['errors'] ?? [];

// Output Handler
if (is_json_request()) {
    json_response($data, $status, $message, $errors);
    // dd('JSON');
} else {
    extract($data);
}
