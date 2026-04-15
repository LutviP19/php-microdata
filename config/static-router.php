<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// file: config/static-router.php
// simple routing untuk execute file static php
// You can also run FFi ​​code if needed with single php script

$staticFile = BASEPATH . '/static/' .$page.'.php';
if (file_exists($staticFile)) {

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

    // Cast type to DefaultStruct and Refine $_REQUEST to their native data type
    $defaultStructPath = realpath(BASEPATH . "/app/Structs/DefaultStruct.php");
    if ($requestMethod === 'GET' && file_exists($defaultStructPath)) {
        $_REQUEST = \App\Core\Support\App::validateStruct($defaultStructPath);

        $defaultStructClass = (new App\Structs\DefaultStruct());
        $rulesDefaultStruct = parseStructToRules($defaultStructClass::class);
        $rulesDefault = [];
        foreach($_REQUEST as $reqKey => $reqVal) {
            if(in_array($reqKey, array_keys($rulesDefaultStruct)))
                $rulesDefault[$reqKey] = $rulesDefaultStruct[$reqKey];
        }

        $safeDataDefault = refineRequest($_REQUEST, $rulesDefault);
        $resultDefault = validateStructData($safeDataDefault, $rulesDefault);

        // Validation errors
        if(isset($resultDefault['errors'])) {
            if(is_json_request() && handle_json_request()) {
                json_response([], 406, 'Validation errors', $resultDefault['errors']);
            } else {
                http_response_code(isHtmx() ? 200 : 406);
                include BASEPATH . "/views/error/406.php";
                exit();
            }
        }
    }
    // dd($_REQUEST, true);

    // Include File PHP static.
    include $staticFile;
    exit();
}
