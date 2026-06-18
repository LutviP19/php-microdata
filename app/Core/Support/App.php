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
        // return self::has($key) ? self::$registry[$key] : false;

        // // PERBAIKAN UTAMA: Menggunakan array_key_exists alih-alih isset.
        // // Ini memastikan jika service terdaftar sebagai null (karena RabbitMQ/Redis down di Docker lokal),
        // // fungsi ini tetap mengembalikan nilai null asli, bukan mereturn nilai boolean false.
        // return array_key_exists($key, self::$registry) ? self::$registry[$key] : null;

        // if (!array_key_exists($key, self::$registry)) {
        //     return null;
        // }

        // // === FITUR SINGLETON AUTOMATIC RESOLUTION ===
        // // Jika service yang disimpan berupa instance dari Closure (Fungsi),
        // // eksekusi fungsi tersebut untuk membuat objek aslinya, 
        // // lalu timpa registry dengan hasil objek asli tersebut agar menjadi static instansiasi tunggal.
        // if (self::$registry[$key] instanceof Closure) {
        //     self::$registry[$key] = self::$registry[$key]();
        // }

        // return self::$registry[$key];

        // 1. Jika service sudah terdaftar langsung di container
        if (array_key_exists($key, self::$registry)) {
            if (self::$registry[$key] instanceof Closure) {
                self::$registry[$key] = self::$registry[$key]();
            }
            return self::$registry[$key];
        }

        // 2. JALUR AUTOWIRING: Jika key merupakan nama sebuah Class yang valid, 
        // lakukan resolusi otomatis menggunakan Reflection API
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
        // return isset(self::$registry[$key]) ? true : false;

        // Menggunakan array_key_exists agar kolom yang bernilai null tetap dianggap "ada" kuncinya
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
     * Menghapus service tertentu dari container jika dibutuhkan (Opsional/Utilitas)
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
     * Fitur Baru: Mendaftarkan service dengan skema Singleton (Lazy Loading)
     * Objek tidak akan dibuat sebelum fungsi App::get() dipanggil pertama kali.
     * 
     * @param string $key Nama alias service
     * @param Closure $callback Fungsi pembuat objek instansiasi
     * @return void
     */
    public static function singleton(string $key, Closure $callback): void
    {
        self::$registry[$key] = $callback;
    }

    /**
     * Logika utama Autowiring menggunakan Reflection API (Resolusi Dependensi Otomatis)
     */
    private static function resolve(string $className): mixed
    {
        $reflector = new ReflectionClass($className);

        // Jika kelas tidak bisa di-instansiasi (misal: Interface atau Abstract Class)
        if (!$reflector->isInstantiable()) {
            throw new Exception("Kelas [{$className}] bukan merupakan kelas yang dapat di-instansiasi.");
        }

        // Ambil konstruktor dari kelas tersebut
        $constructor = $reflector->getConstructor();

        // Jika tidak memiliki konstruktor, langsung buat objek baru tanpa argumen
        if (is_null($constructor)) {
            return new $className();
        }

        // Ambil daftar parameter dari konstruktor
        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // Ambil tipe data class dari parameter (PHP 8 ReflectionType)
            $type = $parameter->getType();

            // Jika parameter tidak memiliki tipe data (type-hint) kelas, atau merupakan tipe primitif (string, int, dll)
            if (!$type || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("Tidak dapat menyelesaikan parameter [{$parameter->getName()}] di kelas [{$className}] karena tidak memiliki tipe kelas yang valid.");
            }

            // Ambil nama lengkap kelas dari parameter tersebut
            $dependencyClassName = $type->getName();

            // Panggil App::get() secara rekursif untuk menyelesaikan dependensi ini
            $dependencies[] = self::get($dependencyClassName);
        }

        // Buat objek baru dengan menyuntikkan seluruh dependensi yang berhasil di-resolve
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Mengeksekusi (Invoke) sebuah method pada objek tertentu dengan autowiring parameter.
     * 
     * @param array|callable $callback Format: [$objectInstance, 'methodName']
     * @param array $routeParams Parameter dinamis dari URL (misal: ['id' => 77])
     * @return mixed Hasil eksekusi dari method tersebut
     * @throws Exception
     */
    public static function call(array|callable $callback, array $routeParams = []): mixed
    {
        if (!is_array($callback)) {
            return call_user_func($callback, $routeParams);
        }

        [$instance, $method] = $callback;
        
        try {
            // Gunakan ReflectionMethod untuk membedah parameter fungsi Controller
            $reflectionMethod = new \ReflectionMethod($instance, $method);
            $parameters = $reflectionMethod->getParameters();
            $arguments = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                $paramName = $parameter->getName();

                // 1. KONDISI A: Parameter memiliki type-hint sebuah CLASS (seperti Request, UserRepository)
                if ($type && !$type->isBuiltin()) {
                    $dependencyClass = $type->getName();
                    // Resolve otomatis menggunakan fungsi App::get() yang sudah kita buat sebelumnya
                    $arguments[] = self::get($dependencyClass);
                    continue;
                }

                // 2. KONDISI B: Parameter merupakan variabel primitif dari URL (seperti $id atau $slug)
                if (array_key_exists($paramName, $routeParams)) {
                    $arguments[] = $routeParams[$paramName];
                    continue;
                }

                // 3. KONDISI C: Gunakan nilai default jika tersedia (misal: $status = 1)
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \Exception("Tidak dapat menyelesaikan parameter [\${$paramName}] pada metode [{$method}].");
            }

            // Eksekusi metode Controller dengan seluruh argumen yang sudah dirakit
            return $reflectionMethod->invokeArgs($instance, $arguments);

        } catch (\ReflectionException $e) {
            throw new \Exception("Gagal melakukan autowiring pada metode: " . $e->getMessage());
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
                // dd($rules);

                // Refine the raw $_REQUEST data into native types
                $safeData = refineRequest($_REQUEST, $rules);
                // dd($safeData);

                if (isset($_REQUEST["id"])) {
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
            case "GET":
                // Process GET data
                if (isset($_REQUEST["id"])) {
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
                        // dd($rules);

                        // Allow only struct keys
                        if (isset($_REQUEST["params"])) {
                            $structClass = new $structNameSpace();
                            $structRules = parseStructToRules($structClass::class);
                            foreach ($structRules as $index => $structRule) {
                                // dd($index);
                                // dd($structRule);
                                // dd(isset($_REQUEST[$index]));

                                // if($index === "website")
                                //     dd($structRule);

                                if (
                                    isset($_REQUEST["params"][$index]) &&
                                    (!is_null($_REQUEST["params"][$index]) || $_REQUEST["params"][$index] !== "")
                                ) {
                                    $rules[$index] = rtrim(
                                        ltrim(str_replace(["required", "email", "url"], "", $structRule), ","),
                                        ",",
                                    );
                                    // $rules[$index] = "";
                                    $safeData += refineRequest(["$index" => $_REQUEST["params"][$index]], $rules);
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
            case "DELETE":
                if (isset($_REQUEST["id"])) {
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
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $dataModel = [];

        // Create a new instance from $className
        $className = pathToNamespace(str_replace(realpath(config("app.path")), "", $modelPath));
        // dd($className);
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
        // Tentukan path ke folder Listeners
        $path = BASEPATH . "/app/Listeners";
        self::loadListeners($path);
    }

    /**
     * Auto Scan folder Listeners dan melakukan require_once.
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

                // Ambil path relatif dari folder Listeners
                // Contoh: "Order/SendInvoice.php"
                $relative = $iterator->getSubPathname();

                // Konversi path menjadi format Namespace
                // Kita hapus .php dan ubah "/" atau "\" menjadi "\"
                $namespacePath = str_replace([".php", "/", DIRECTORY_SEPARATOR], ["", "\\", "\\"], $relative);

                // Full Class Name
                // Hasil: App\Listeners\Order\ProcessPayment
                $className = "App\\Listeners\\" . $namespacePath;

                // Debug: Uncomment baris di bawah jika masih gagal untuk melihat class apa yang dicari
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

        // Registrasi ke Registry dengan urutan ASC
        // dd($collectedListeners, true);
        foreach ($collectedListeners as $eventName => $listeners) {
            // ASC: Priority 1 tampil sebelum Priority 10
            usort($listeners, fn($a, $b) => $a["priority"] <=> $b["priority"]);

            // dd($listeners, true);
            foreach ($listeners as $item) {
                ListenerRegistry::listen($eventName, $item["callback"]);
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

            // 2. Satukan Headers (Config Base + Dinamis dari argumen)
            $headers = array_merge($base["headers"] ?? [], $dynamicOptions["headers"] ?? []);

            // 3. Build Result untuk GoHttpClient
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
