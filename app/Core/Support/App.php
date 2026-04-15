<?php 

/**
 * App class
 * @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Core\Support;

use App\Core\Events\ListenerRegistry;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Throwable;
use Exception;

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
    public static function validateStruct($structPath)
    {
        // $structName = str_replace('.php', '', basename($structPath));
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $structNameSpace = pathToNamespace(str_replace(realpath(config('app.path')), '', $structPath));
        // dd($structNameSpace);

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
                    $rules += ["id" => "required,numeric,gte=1"];
                    // Refine the raw $_REQUEST data into native types
                    // $id =  $_POST['id'] ?? $_GET['id'];
                    $id = formatReqId();
                    // dd($id);
                    $safeData += refineRequest(["id" => $id], $rules);
                }

                $result = validateStructData($safeData, $rules);
                // dd($result);
                break;
            case 'GET':
                // Process GET data
                if(isset($_REQUEST['id'])) {
                    $rules = ["id" => "required,numeric,gte=1"];
                    // Refine the raw $_REQUEST data into native types
                    // $id =  $_POST['id'] ?? $_GET['id'];
                    $id = formatReqId();
                    // dd($id);
                    $safeData = refineRequest(["id" => $id], $rules);

                    $result = validateStructData($safeData, $rules);
                } else {
                    // Validate Pagination params
                    $rules = $safeData = [];
                    if(isset($_REQUEST['page'])) {
                        $rules['page'] = "omitempty,numeric,min=1";
                        $safeData += refineRequest(["page" => $_REQUEST['page']], $rules);
                    }
                    if(isset($_REQUEST['limit'])) {
                        $rules['limit'] = "omitempty,numeric,min=1";
                        $safeData += refineRequest(["limit" => $_REQUEST['limit']], $rules);
                    }
                    if(isset($_REQUEST['total'])) {
                        $rules['total'] = "omitempty,numeric,min=0";
                        $safeData += refineRequest(["total" => $_REQUEST['total']], $rules);
                    }
                    // if(isset($_REQUEST['params'])) {
                    //     $rules['params'] = "omitempty,min=3";
                    //     $safeData += refineRequest(["params" => $_REQUEST['params']], $rules);
                    // }
                    // dd($_REQUEST);
                    // dd($safeData);
                    // dd($rules);

                    if(!empty($rules)) {
                        // dd($rules);

                        // Allow only struct keys
                        if(isset($_REQUEST['params'])) {
                            $structClass = new $structNameSpace();
                            $structRules = parseStructToRules($structClass::class);
                            foreach($structRules as $index => $structRule) {
                                // dd($index);
                                // dd($structRule);
                                // dd(isset($_REQUEST[$index]));
                                
                                // if($index === "website") 
                                //     dd($structRule);

                                if(isset($_REQUEST['params'][$index]) && (!is_null($_REQUEST['params'][$index]) || $_REQUEST['params'][$index] !== "")) {
                                    $rules[$index] = rtrim(ltrim(str_replace(['required', 'email', 'url'], '', $structRule), ","), ",");
                                    // $rules[$index] = "";
                                    $safeData += refineRequest(["$index" => $_REQUEST['params'][$index]], $rules);
                                }
                                continue;
                            }
                            // dd($safeData);
                            // dd($rules);
                        }

                        $result = validateStructData($safeData, $rules);
                        // dd($result);
                    }
                }
                break;
            case 'DELETE':
                if(isset($_REQUEST['id'])) {
                    $rules = ["id" => "numeric,required,gte=1"];
                    // Refine the raw $_REQUEST data into native types
                    // $id =  (int)($_POST['id'] ?? $_GET['id']);

                    // // Simulate generate ID
                    // unset($_REQUEST);
                    // // $id = formatReqId("123456", 1);
                    // $id = formatReqId("abcd", 1);
                    // // $id = encryptData("abcd");
                    // dd($id);
                    
                    // // Simulate encrypted ID
                    // $reqId = "g4RPUbVo0lPHZdTccyJ6z53A9fK9_mphd0ikawMdDk0"; // 123456
                    // // $reqId = "MP7GTz3IeGdgCcJISx6z4IswrAxv9hhxbzBrYW-Klmw"; // abcd
                    // $id = formatReqId($reqId, 1);
                    // dd($id);

                    $id = formatReqId();
                    // dd($id);
                    $safeData = refineRequest(["id" => $id], $rules);
                    // dd($safeData);
                    $result = validateStructData($safeData, $rules);
                } else {
                    $result['errors'] = [
                        "id"  =>  "Empty ID."
                    ];
                }
                break;
            default:
                $result = [];
                break;
        }

        // Validation errors
        if(isset($result['errors']) && is_json_request() && handle_json_request()) {
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
     */
    public static function loadModel($modelPath)
    {
        // dd($_REQUEST);
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $dataModel = [];

        // Create a new instance from $className
        $className = pathToNamespace(str_replace(realpath(config('app.path')), '', $modelPath));
        // dd($className);
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
                    // dd($request);
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

    public static function bootListeners()
    {
        // Tentukan path ke folder Listeners
        $path = BASEPATH . '/app/Listeners';
        self::loadListeners($path);
    }

    /**
     * Auto Scan folder Listeners dan melakukan require_once.
     * * @param string $path
     */
    protected static function loadListeners($path)
    {
        if (!is_dir($path)) return;

        $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $collectedListeners = [];

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                require_once $file->getRealPath();

                // Ambil path relatif dari folder Listeners
                // Contoh: "Order/SendInvoice.php"
                $relative = $iterator->getSubPathname();

                // Konversi path menjadi format Namespace
                // Kita hapus .php dan ubah "/" atau "\" menjadi "\"
                $namespacePath = str_replace(
                    ['.php', '/', DIRECTORY_SEPARATOR], 
                    ['', '\\', '\\'], 
                    $relative
                );

                // Full Class Name
                // Hasil: App\Listeners\Order\ProcessPayment
                $className = 'App\\Listeners\\' . $namespacePath;

                // Debug: Uncomment baris di bawah jika masih gagal untuk melihat class apa yang dicari
                // echo "Checking class: " . $className . PHP_EOL;

                if (class_exists($className)) {
                    $reflection = new \ReflectionClass($className);
                    
                    if ($reflection->implementsInterface('App\Core\Events\ListenerInterface')) {
                        $instance = new $className();
                        
                        $event    = $instance->event ?? null;
                        $priority = $instance->priority ?? 0;

                        if ($event) {
                            $collectedListeners[$event][] = [
                                'priority' => $priority,
                                'callback' => [$instance, 'handle']
                            ];
                        }
                    }
                }
            }
        }

        // Registrasi ke Registry dengan urutan ASC
        // dd($collectedListeners, true);
        foreach ($collectedListeners as $eventName => $listeners) {
            // ASC: Priority 1 tampil sebelum Priority 10
            usort($listeners, function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });

            // dd($listeners, true);
            foreach ($listeners as $item) {
                ListenerRegistry::listen($eventName, $item['callback']);
            }
        }
    }

    /**
     * Resolve External API Config for GoHttpClient.
     * * @param string $key Contoh: 'external_api_1'
     * @param array $dynamicOptions Parameter dinamis seperti ['body' => [...], 'headers' => [...]]
     * @return array
     */
    public static function externalApi(string $key, array $dynamicOptions = []): array
    {
        try {
            // 1. Ambil semua config routing (config/external-api.php)
            $configs = self::get('routing_external_api');

            if (!$configs) {
                $messageErr = "App::registy[routing_external_api] was not registered." . PHP_EOL;
                if (config('app.debug')) {
                    \write_log([
                        'key' => $key,
                        'message' => $messageErr                    
                    ], 'App\Core\Support\App.externalApi', 'error', 'error_APP_externalApi.log');
                }
                throw new \Exception($messageErr);
            }

            if (!isset($configs[$key])) {
                $messageErr = "External API configuration with key [$key] was not found." . PHP_EOL;
                if (config('app.debug')) {
                    \write_log([
                        'key' => $key,
                        'message' => $messageErr                    
                    ], 'App\Core\Support\App.externalApi', 'error', 'error_APP_externalApi.log');
                }
                throw new \Exception($messageErr);
            }

            $base = $configs[$key];

            // 2. Satukan Headers (Config Base + Dinamis dari argumen)
            $headers = array_merge(
                $base['headers'] ?? [],
                $dynamicOptions['headers'] ?? []
            );

            // 3. Build Result untuk GoHttpClient
            return [
                'method'  => strtoupper($base['method'] ?? 'GET'),
                'url'     => $base['url'] ?? '',
                'headers' => $headers,
                'body'    => isset($dynamicOptions['body']) 
                             ? (is_array($dynamicOptions['body']) ? json_encode($dynamicOptions['body']) : $dynamicOptions['body']) 
                             : ($base['body'] ?? ''),
                'timeout' => $dynamicOptions['timeout'] ?? $base['timeout'] ?? 30
            ];
        } catch (Throwable $e) {
            // Re-throw agar error detail (seperti typo function) muncul di log global
            throw $e;
        }
    }

    // REMOVED CANDIDAT :
    // public function getUserAbilities($userId, $groupId) 
    // {
    //     $cache = new \App\Core\Support\Cache();
    //     $cacheKey = "user_abilities_{$userId}_{$groupId}";

    //     return $cache->remember($cacheKey, function() use ($userId, $groupId) {
    //         $db = new \App\Core\Database\Model();
    //         $sql = "SELECT DISTINCT p.slug 
    //                 FROM user_roles ur
    //                 JOIN role_permissions rp ON ur.role_id = rp.role_id
    //                 JOIN permissions p ON rp.permission_id = p.id
    //                 WHERE ur.user_id = ? AND (ur.group_id = ? OR ur.group_id IS NULL)";
            
    //         $rows = $db->execQuery($sql, [$userId, $groupId], false, false, true);
            
    //         // Flatten array agar mudah dicek: ['create-asset', 'edit-asset', ...]
    //         return array_column($rows, 'slug');
    //     }, 3600); // Simpan 1 jam

    //     // // Cara Penggunaan di View/Controller (HTMX)

    //     // $abilities = $auth->getUserAbilities($user->id, $user->current_team_id);

    //     // // Contoh pengecekan akses
    //     // if (in_array('delete-asset', $abilities)) {
    //     //     // Tampilkan tombol hapus untuk HTMX
    //     //     echo '<button hx-delete="/asset/1">Hapus</button>';
    //     // }
    // }
}
