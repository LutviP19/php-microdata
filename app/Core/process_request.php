<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// process_request.php
// Global process request and Response output

/**
 * Require Core init File.
 */
require_once 'init.php';
// dd(BASEPATH);

// Otomatis konversi JSON ke array
handle_json_request();

// Bersihkan seluruh input $_REQUEST
$_REQUEST = sanitize($_REQUEST);
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);

// Check var $modelName
if(!isset($modelName)) {
    throw new Exception("var modelName not set.");
}

// Create a new instance from $className
$className = '\\App\\Models\\'.$modelName;
$modelClass = new $className();

// Check $modelClass is has validMethods
$validMethods = (method_exists($modelClass,'index') && 
                    method_exists($modelClass,'store') && 
                    method_exists($modelClass,'edit') && 
                    method_exists($modelClass,'update') && 
                    method_exists($modelClass,'destroy')
                );
if(false === $validMethods) {
    throw new Exception("modelClass not valid, must have methods 'index', 'store', 'edit', 'update' and destroy'.");
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
switch ($requestMethod) {
    case 'GET':
        // Process GET data
        $request = &$_REQUEST;
        if(isset($_GET['id'])) {
            $dataModel = $modelClass->edit($request);
        } else {
            $dataModel = $modelClass->index($request);
        }
        break;
    case 'POST':
        // Process POST data
        $request = &$_POST;
        $dataModel = $modelClass->store($request);
        break;
    case 'PUT':
    case 'PATCH':
        // Handle PUT & PATCH request
        $request = &$_POST;
        $dataModel = $modelClass->update($request);
        break;
    case 'DELETE':
        // Handle DELETE request
        $request = &$_POST;
        $dataModel = $modelClass->destroy($request);
        break;
    default:
        // Handle other methods or an error
        $dataModel = [
            'status' => 400,
            'message' => 'Method not allowed.',
        ];
        break;
}

// Parse $dataModel
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
