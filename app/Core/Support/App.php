<?php

/**
 * App class
 * 
 * @author LutviP19 <lutvip19@gmail.com>
 * @package PHP-Microdata(PMD)
 */

namespace App\Core\Support;

use App\Core\Events\ListenerRegistry;
use RecursiveDirectoryIterator;
use Throwable;
use Exception;
use Closure;
use ReflectionClass;
use ReflectionException;

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
    public static function get(string $key): mixed
    {
        // 1. If the service has been registered directly in the container
        if (array_key_exists($key, self::$registry)) {
            if (self::$registry[$key] instanceof Closure) {
                self::$registry[$key] = self::$registry[$key]();
            }
            return self::$registry[$key];
        }

        // 2. AUTOWIRING PATH: If key is the name of a valid Class, 
        // perform auto resolution using the Reflection API
        if (class_exists($key)) {
            try {
                return self::resolve($key);
            } catch (ReflectionException $e) {
                throw new Exception("Gagal melakukan autowiring untuk kelas [{$key}]: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Check if a value exists in the registry.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$registry);
    }

    /**
     * Register a value into the App container.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function register(string $key, mixed $value): void
    {
        self::$registry[$key] = $value;
    }

     /**
     * Remove certain services from the container if needed (Optional/Utility)
     * 
     * @param string $key
     * @return void
     */
    public static function unregister(string $key): void
    {
        if (self::has($key)) {
            unset(self::$registry[$key]);
        }
    }

    /**
     * New Feature: Register services with Singleton (Lazy Loading) scheme
     * The object will not be created until the App::get() function is called the first time.
     * 
     * @param string $key Service alias name
     * @param Closure $callback The instantiation object creation function
     * @return void
     */
    public static function singleton(string $key, Closure $callback): void
    {
        self::$registry[$key] = $callback;
    }

    /**
     * Autowiring main logic using Reflection API (Automatic Dependency Resolution)
     */
    private static function resolve(string $className): mixed
    {
        $reflector = new ReflectionClass($className);

        // If the class cannot be instantiated (eg: Interface and Abstract Class)
        if (!$reflector->isInstantiable()) {
            throw new Exception("Kelas [{$className}] bukan merupakan kelas yang dapat di-instansiasi.");
        }

        // Take the constructor of that class
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // Get the class data type from the parameter (PHP 8 ReflectionType)
            $type = $parameter->getType();

            // If the parameter does not have a class data type (type-hint), or is a primitive type (string, int, etc.)
            if (!$type || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("Cannot resolve parameter [{$parameter->getName()}] in class [{$className}] because it does not have a valid class type.");
            }

            // Retrieve the full class name of the parameter
            $dependencyClassName = $type->getName();

            // Call App::get() recursively to resolve these dependencies
            $dependencies[] = self::get($dependencyClassName);
        }

        // Create a new object by injecting all successfully resolved dependencies
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Execute (Invoke) a method on a specific object with autowiring parameters.
     *
     * @param array|callable $callback Format: [$objectInstance, 'methodName']
     * @param array $routeParams Dynamic parameters of the URL (e.g. ['id' => 77])
     * @return mixed The result of the execution of the method
     * @throws Exception
     */
    public static function call(array|callable $callback, array $routeParams = []): mixed
    {
        if (!is_array($callback)) {
            return call_user_func($callback, $routeParams);
        }

        [$instance, $method] = $callback;
        
        try {
            // Use ReflectionMethod to dissect Controller function parameters
            $reflectionMethod = new \ReflectionMethod($instance, $method);
            $parameters = $reflectionMethod->getParameters();
            $arguments = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                $paramName = $parameter->getName();

                // 1. CONDITION A: The parameter has the type-hint of a CLASS (such as Request, UserRepository)
                if ($type && !$type->isBuiltin()) {
                    $dependencyClass = $type->getName();
                    // Resolve otomatis menggunakan fungsi App::get() yang sudah kita buat sebelumnya
                    $arguments[] = self::get($dependencyClass);
                    continue;
                }

                // 2. CONDITION B: Parameters are primitive variables of the URL (such as $id or $slug)
                if (array_key_exists($paramName, $routeParams)) {
                    $arguments[] = $routeParams[$paramName];
                    continue;
                }

                // 3. CONDITION C: Use default value if available (e.g. $status = 1)
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \Exception("Cannot resolve parameter [\${$paramName}] on method [{$method}].");
            }

            // Eksekusi metode Controller dengan seluruh argumen yang sudah dirakit
            return $reflectionMethod->invokeArgs($instance, $arguments);

        } catch (\ReflectionException $e) {
            throw new \Exception("Failed to autowiring method:" . $e->getMessage());
        }
    }

    /**
     * Validate Struct
     * @param $structPath relative path to Struct
     * @param $model ModelName
     */
    public static function validateStruct($structPath)
    {
        // $structName = str_replace('.php', '', basename($structPath));
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $structNameSpace = pathToNamespace(str_replace(realpath(config("app.path")), "", $structPath));
        // dd($structNameSpace);

        switch ($requestMethod) {
            case "POST":
            case "PUT":
            case "PATCH":
                $structClass = new $structNameSpace();
                $rules = parseStructToRules($structClass::class);

                // Refine the raw $_REQUEST data into native types
                $safeData = refineRequest($_REQUEST, $rules);

                if (isset($_REQUEST["id"])) {
                    $rules += ["id" => "required,numeric,gte=1"];
                    $id = formatReqId();
                    $safeData += refineRequest(["id" => $id], $rules);
                }

                $result = validateStructData($safeData, $rules);

                break;
            case "GET":
                // Process GET data
                if (isset($_REQUEST["id"])) {
                    $rules = ["id" => "required,numeric,gte=1"];
                    $id = formatReqId();
                    $safeData = refineRequest(["id" => $id], $rules);

                    $result = validateStructData($safeData, $rules);
                } else {
                    // Validate Pagination params
                    $rules = $safeData = [];
                    if (isset($_REQUEST["page"])) {
                        $rules["page"] = "omitempty,numeric,min=1";
                        $safeData += refineRequest(["page" => $_REQUEST["page"]], $rules);
                    }
                    if (isset($_REQUEST["limit"])) {
                        $rules["limit"] = "omitempty,numeric,min=1";
                        $safeData += refineRequest(["limit" => $_REQUEST["limit"]], $rules);
                    }
                    if (isset($_REQUEST["total"])) {
                        $rules["total"] = "omitempty,numeric,min=0";
                        $safeData += refineRequest(["total" => $_REQUEST["total"]], $rules);
                    }
                    // if(isset($_REQUEST['params'])) {
                    //     $rules['params'] = "omitempty,min=3";
                    //     $safeData += refineRequest(["params" => $_REQUEST['params']], $rules);
                    // }
                    // dd($_REQUEST);
                    // dd($safeData);
                    // dd($rules);

                    if (!empty($rules)) {
                        // Allow only struct keys
                        if (isset($_REQUEST["params"])) {
                            $structClass = new $structNameSpace();
                            $structRules = parseStructToRules($structClass::class);
                            foreach ($structRules as $index => $structRule) {

                                if (
                                    isset($_REQUEST["params"][$index]) &&
                                    (!is_null($_REQUEST["params"][$index]) || $_REQUEST["params"][$index] !== "")
                                ) {
                                    $rules[$index] = rtrim(
                                        ltrim(str_replace(["required", "email", "url"], "", $structRule), ","),
                                        ",",
                                    );

                                    $safeData += refineRequest(["$index" => $_REQUEST["params"][$index]], $rules);
                                }
                                continue;
                            }
                        }

                        $result = validateStructData($safeData, $rules);
                    }
                }
                break;
            case "DELETE":
                if (isset($_REQUEST["id"])) {
                    $rules = ["id" => "numeric,required,gte=1"];

                    $id = formatReqId();
                    $safeData = refineRequest(["id" => $id], $rules);
                    $result = validateStructData($safeData, $rules);
                } else {
                    $result["errors"] = [
                        "id" => "Empty ID.",
                    ];
                }
                break;
            default:
                $result = [];
                break;
        }

        // Validation errors
        if (isset($result["errors"]) && is_json_request() && handle_json_request()) {
            json_response([], 406, "Validation errors", $result["errors"]);
            die();
        }

        return $safeData;
    }

    /**
     * Load Model
     * @param $modelPath relative path to Model
     */
    public static function loadModel($modelPath)
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $dataModel = [];

        // Create a new instance from $className
        $className = pathToNamespace(str_replace(realpath(config("app.path")), "", $modelPath));
        $modelClass = new $className();

        // Check $modelClass is has default validMethods
        $validMethods =
            method_exists($modelClass, "index") &&
            method_exists($modelClass, "store") &&
            method_exists($modelClass, "edit") &&
            method_exists($modelClass, "update") &&
            method_exists($modelClass, "destroy");
        if (false === $validMethods) {
            throw new Exception(
                "modelClass $className not valid, must have methods 'index', 'store', 'edit', 'update' and 'destroy'.",
            );
        }

        // Get Safe request data
        $request = &$_REQUEST;
        switch ($requestMethod) {
            case "GET":
                // Process GET data
                if (isset($_REQUEST["id"])) {
                    $request["id"] = $_POST["id"] ?? $_GET["id"];
                    // dd($request);
                    $dataModel = $modelClass->edit($request);
                } else {
                    $dataModel = $modelClass->index($request);
                }
                break;
            case "POST":
                // Process POST data
                $dataModel = $modelClass->store($request);
                break;
            case "PUT":
            case "PATCH":
                // Handle PUT & PATCH request
                $dataModel = $modelClass->update($request);
                break;
            case "DELETE":
                // Handle DELETE request
                $request["id"] = $_POST["id"] ?? $_GET["id"];
                $dataModel = $modelClass->destroy($request);
                break;
            default:
                // Handle other methods or an error
                $dataModel = [
                    "status" => 400,
                    "message" => "Method not allowed.",
                ];
                break;
        }

        return $dataModel;
    }

    public static function bootListeners()
    {
        // Specify the path to the Listeners folder
        $path = BASEPATH . "app/Listeners";
        self::loadListeners($path);
    }

    /**
     * Auto Scan Listeners folder and perform require_once.
     * * @param string $path
     */
    protected static function loadListeners($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $collectedListeners = [];

        foreach ($iterator as $file) {
            if ($file->getExtension() === "php") {
                require_once $file->getRealPath();

                // Retrieve the relative path of the Listeners folder
                // Example: "Order/SendInvoice.php"
                $relative = $iterator->getSubPathname();

                // Convert path to Namespace format
                // We delete .php and change "/" or "\" to "\"
                $namespacePath = str_replace([".php", "/", DIRECTORY_SEPARATOR], ["", "\\", "\\"], $relative);

                // Full Class Name
                // Result: App\Listeners\Order\ProcessPayment
                $className = "App\\Listeners\\" . $namespacePath;

                // Debug: Uncomment the line below if it still fails to see what class it is looking for
                // echo "Checking class: " . $className . PHP_EOL;

                if (class_exists($className)) {
                    $reflection = new \ReflectionClass($className);

                    if ($reflection->implementsInterface(\App\Core\Events\ListenerInterface::class)) {
                        $instance = new $className();

                        $event = $instance->event ?? null;
                        $priority = $instance->priority ?? 0;

                        if ($event) {
                            $collectedListeners[$event][] = [
                                "priority" => $priority,
                                "callback" => [$instance, "handle"],
                            ];
                        }
                    }
                }
            }
        }

        // Register to the Registry with ASC sequence
        foreach ($collectedListeners as $eventName => $listeners) {
            // ASC: Priority 1 appears before Priority 10
            usort($listeners, fn($a, $b) => $a["priority"] <=> $b["priority"]);
            
            foreach ($listeners as $item) {
                ListenerRegistry::listen($eventName, $item["callback"]);
            }
        }
    }

    /**
     * Resolve External API Config for GoHttpClient.
     * @param string $key Example: 'external_api_1'
     * @param array $dynamicOptions Dynamic parameters like ['body' => [...], 'headers' => [...]]
     * @return array
     */
    public static function externalApi(string $key, array $dynamicOptions = []): array
    {
        try {
            // 1. Take all routing config (config/external-api.php)
            $configs = self::get("routing_external_api");

            if (!$configs) {
                $messageErr = "App::registy[routing_external_api] was not registered." . PHP_EOL;
                if (config("app.debug")) {
                    \write_log(
                        [
                            "key" => $key,
                            "message" => $messageErr,
                        ],
                        "App\Core\Support\App.externalApi",
                        "error",
                        "error_APP_externalApi.log",
                    );
                }
                throw new \Exception($messageErr);
            }

            if (!isset($configs[$key])) {
                $messageErr = "External API configuration with key [$key] was not found." . PHP_EOL;
                if (config("app.debug")) {
                    \write_log(
                        [
                            "key" => $key,
                            "message" => $messageErr,
                        ],
                        "App\Core\Support\App.externalApi",
                        "error",
                        "error_APP_externalApi.log",
                    );
                }
                throw new \Exception($messageErr);
            }

            $base = $configs[$key];

            // 2. Unite Headers (Config Base + Dynamic of arguments)
            $headers = array_merge($base["headers"] ?? [], $dynamicOptions["headers"] ?? []);

            // 3. Build Results for GoHttpClient
            return [
                "method" => strtoupper($base["method"] ?? "GET"),
                "url" => $base["url"] ?? "",
                "headers" => $headers,
                "body" => isset($dynamicOptions["body"])
                    ? (is_array($dynamicOptions["body"])
                        ? json_encode($dynamicOptions["body"])
                        : $dynamicOptions["body"])
                    : $base["body"] ?? "",
                "timeout" => $dynamicOptions["timeout"] ?? ($base["timeout"] ?? 30),
            ];
        } catch (Throwable $e) {
            // Re-throw so that detailed errors (such as function typos) appear in the global log
            throw $e;
        }
    }
}
