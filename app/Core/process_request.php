<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// process_request.php
// Global process request and Response output


// Otomatis validasi format JSON dan sanitize JSON dengan FFI
// kemudian JSON dikonversi ke $_REQUEST
if(is_json_request() && !handle_json_request()) {
    json_response([], 406, 'Invalid JSON', ['input' => 'Invalid JSON format.']);
    die();
}

// Bersihkan lagi seluruh input $_REQUEST setelah di konversi
$_REQUEST = sanitize($_REQUEST);
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);

// Check var $modelName
if(!isset($modelName)) {
    throw new Exception("var modelName not set.");
}

// Validate Struct
if (is_json_request() && file_exists($structPath)) {
    $structName = '\\App\\Structs\\'.$structName;
    $structClass = new $structName();
    $rules = parseStructToRules($structClass::class);
    // dd($rules);

    // Refine the raw $_REQUEST data into native types
    $safeData = refineRequest($_REQUEST, $rules);

    // Then call your FFI function
    $result = validateStructData($safeData, $rules);
    // dd($result);

    if(isset($result['errors'])) {
        json_response([], 406, 'Validation errors', $result['errors']);
        die();
    }
}

// Create a new instance from $className
$className = '\\App\\Models\\'.$modelName;
$modelClass = new $className();

// Check $modelClass is has default validMethods
$validMethods = (method_exists($modelClass,'index') && 
                    method_exists($modelClass,'store') && 
                    method_exists($modelClass,'edit') && 
                    method_exists($modelClass,'update') && 
                    method_exists($modelClass,'destroy')
                );
if(false === $validMethods) {
    throw new Exception("modelClass $className not valid, must have methods 'index', 'store', 'edit', 'update' and 'destroy'.");
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
