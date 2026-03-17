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
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Check var $modelName
if(!isset($modelName)) {
    throw new Exception("var modelName not set.");
}

// Validate Struct
if (is_json_request() && file_exists($structPath)) {
    $structNameSpace = '\\App\\Structs\\'.$structName;

    switch ($requestMethod) {
        case 'POST':
        case 'PUT':
        case 'PATCH':
            $structClass = new $structNameSpace();
            $rules = parseStructToRules($structClass::class);
            // dd($rules);

            // Refine the raw $_REQUEST data into native types
            $safeData = refineRequest($_REQUEST, $rules);
            // dd($safeData);

            if(isset($_REQUEST['id'])) {
                $rules += ["id" => "required,numeric,min=1"];
                // Refine the raw $_REQUEST data into native types
                $id =  $_GET['id'] ?? $_POST['id'];
                // dd($id);
                $safeData += refineRequest(["id" => $id], $rules);
            }

            $result = validateStructData($safeData, $rules);
            break;
        case 'GET':
            // Process GET data
            if(isset($_GET['id'])) {
                $rules = ["id" => "required,numeric,min=1"];
                // Refine the raw $_REQUEST data into native types
                $safeData = refineRequest(["id" => $_GET['id']], $rules);

                $result = validateStructData($safeData, $rules);
            } else {
                $rules = $safeData = [];
                if(isset($_REQUEST['page'])) {
                    $rules['page'] = "numeric,min=1";
                    $safeData += refineRequest(["page" => $_REQUEST['page']], $rules);
                }
                if(isset($_REQUEST['limit'])) {
                    $rules['limit'] = "numeric,min=10";
                    $safeData += refineRequest(["limit" => $_REQUEST['limit']], $rules);
                }
                if(isset($_REQUEST['offset'])) {
                    $rules['offset'] = "numeric,min=0";
                    $safeData += refineRequest(["offset" => $_REQUEST['offset']], $rules);
                }
                // Refine the raw $_REQUEST data into native types
                // $safeData = refineRequest($_REQUEST, $rules);

                
                // dd($_REQUEST);
                // dd($safeData);
                // dd($rules);

                if(!empty($rules)) {
                    $result = validateStructData($safeData, $rules);
                    // dd($result);
                }
            }
            break;
        case 'DELETE':
            $rules = ["id" => "required,numeric,min=1"];
            // Refine the raw $_REQUEST data into native types
            $safeData = refineRequest(["id" => $_GET['id'] ?? $_POST['id']], $rules);
            $result = validateStructData($safeData, $rules);
            break;
        default:
            $result = [];
            break;
    }

    if(isset($result['errors'])) {
        json_response([], 406, 'Validation errors', $result['errors']);
        die();
    }

    $_REQUEST = $safeData;
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

$request = &$_REQUEST;
switch ($requestMethod) {
    case 'GET':
        // Process GET data
        
        if(isset($_GET['id'])) {
            $dataModel = $modelClass->edit($request);
        } else {
            $dataModel = $modelClass->index($request);
        }
        break;
    case 'POST':
        // Process POST data
        // $request = &$_POST;
        $dataModel = $modelClass->store($request);
        break;
    case 'PUT':
    case 'PATCH':
        // Handle PUT & PATCH request
        // $request = &$_REQUEST;
        $dataModel = $modelClass->update($request);
        break;
    case 'DELETE':
        // Handle DELETE request
        // $request = &$_REQUEST;
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
