<?php 

/**
 * App class
 * @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Core\Support;

/**
 * App Container.
 */
class App
{
    /**
     * All registered keys.
     *
     * @var array
     */
    protected static $registry = [];

    /**
     * Get a value from the registry.
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        return self::has($key) ? self::$registry[$key] : false;
    }

    /**
     * Check if a value exists in the registry.
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset(self::$registry[$key]) ? true : false;
    }

    /**
     * Register a value into the App container.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function register($key, $value)
    {
        self::$registry[$key] = $value;
    }


    /**
     * Validate Struct
     * @param $structPath relative path to Struct
     * @param $model ModelName
     */
    public static function validateStruct($structPath, $model)
    {
        $structName = str_replace('.php', '', basename($structPath));
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        $structNameSpace = '\\App\\Structs\\'.$model.'\\'.$structName;

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
                    $id =  $_POST['id'] ?? $_GET['id'];
                    // dd($id);
                    $safeData += refineRequest(["id" => $id], $rules);
                }

                $result = validateStructData($safeData, $rules);
                break;
            case 'GET':
                // Process GET data
                if(isset($_REQUEST['id'])) {
                    $rules = ["id" => "required,numeric,min=1"];
                    // Refine the raw $_REQUEST data into native types
                    $safeData = refineRequest(["id" => $_POST['id'] ?? $_GET['id']], $rules);

                    $result = validateStructData($safeData, $rules);
                } else {
                    // Validate Pagination params
                    $rules = $safeData = [];
                    if(isset($_REQUEST['page'])) {
                        $rules['page'] = "numeric,min=1";
                        $safeData += refineRequest(["page" => $_REQUEST['page']], $rules);
                    }
                    if(isset($_REQUEST['limit'])) {
                        $rules['limit'] = "numeric,min=1";
                        $safeData += refineRequest(["limit" => $_REQUEST['limit']], $rules);
                    }
                    if(isset($_REQUEST['total'])) {
                        $rules['total'] = "numeric,min=0";
                        $safeData += refineRequest(["total" => $_REQUEST['total']], $rules);
                    }
                    if(isset($_REQUEST['query'])) {
                        $rules['total'] = "numeric,min=0";
                        $safeData += refineRequest(["total" => $_REQUEST['total']], $rules);
                    } 
                    // dd($_REQUEST);
                    // dd($safeData);
                    // dd($rules);

                    if(!empty($rules)) {

                        // Allow only struct keys
                        $structClass = new $structNameSpace();
                        $structRules = parseStructToRules($structClass::class);
                        foreach($structRules as $index => $structRule) {
                            // dd($index);
                            // dd($structRule);
                            // dd(isset($_REQUEST[$index]));
                            
                            // if($index === "website") 
                            //     dd($structRule);

                            if(isset($_REQUEST[$index]) && (!is_null($_REQUEST[$index]) || $_REQUEST[$index] !== "")) {
                                $rules[$index] = ltrim(str_replace(['required', 'email', 'url'], '', $structRule), ",");
                                // $rules[$index] = "";
                                $safeData += refineRequest(["$index" => $_REQUEST[$index]], $rules);
                            }
                            continue;
                        }
                        // dd($safeData);
                        // dd($rules);

                        $result = validateStructData($safeData, $rules);
                        // dd($result);
                    }
                }
                break;
            case 'DELETE':
                $rules = ["id" => "required,numeric,min=1"];
                // Refine the raw $_REQUEST data into native types
                $id =  $_POST['id'] ?? $_GET['id'];
                $safeData = refineRequest(["id" => $id], $rules);
                $result = validateStructData($safeData, $rules);
                break;
            default:
                $result = [];
                break;
        }

        // Validation errors
        if(isset($result['errors'])) {
            json_response([], 406, 'Validation errors', $result['errors']);
            die();
        }

        // Refine to data type
        // $_REQUEST = $safeData;

        return $safeData;
    }


    /**
     * Load Model
     * @param $modelPath relative path to Model
     * @param $model ModelName
     */
    public static function loadModel($modelPath, $model)
    {
        // dd($_REQUEST);
        $modelName = str_replace('.php', '', basename($modelPath));
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $dataModel = [];
        // dd($modelName);

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

        // Get Safe request data
        $request = &$_REQUEST;
        switch ($requestMethod) {
            case 'GET':
                // Process GET data
                if(isset($_REQUEST['id'])) {
                    $request['id'] = $_POST['id'] ?? $_GET['id'];
                    $dataModel = $modelClass->edit($request);
                } else {
                    $dataModel = $modelClass->index($request);
                }
                break;
            case 'POST':
                // Process POST data
                $dataModel = $modelClass->store($request);
                break;
            case 'PUT':
            case 'PATCH':
                // Handle PUT & PATCH request
                $dataModel = $modelClass->update($request);
                break;
            case 'DELETE':
                // Handle DELETE request
                $request['id'] = $_POST['id'] ?? $_GET['id'];
                $dataModel = $modelClass->destroy($request);
                break;
            default:
                // Handle other methods or an error
                $dataModel = [
                    'status' => 400,
                    'message' => 'Method not allowed.'
                ];
                break;
        }

        return $dataModel;
    }
}
