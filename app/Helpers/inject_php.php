<?php

/**
 * Inject php native functions.
 * using FFI or Custom functions
 * @author Lutvi <lutvip19@gmail.com>
 */


if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/../..");
}


/**
 * Sanitasi JSON berdasarkan tipe data.
 * Mendukung string, integer, float, boolean, dan array secara rekursif.
 */
if (!function_exists('sanitizeJson')) {
    function sanitizeJson($dataJson) {
        // Ensure input is a string (if passed as an array/object)
        $input = is_string($dataJson) ? $dataJson : json_encode($dataJson);

        $ffi = FFI::cdef("
            char* SanitizeJSON(char* input);
            void free(void* ptr);
        ", BASEPATH . "/ffi/lib/sanitize.so");

        $cResult = $ffi->SanitizeJSON($input);
        
        if ($cResult === null) {
            return null;
        }

        // Convert the C pointer back to a PHP string
        $safeJson = FFI::string($cResult);

        // Free the memory allocated by Go's C.CString
        $ffi->free($cResult);

        return $safeJson;
    }
}

/**
 * Refine the raw $_REQUEST data into native types
 */
if (!function_exists('refineRequest')) {
    function refineRequest(array $request, array $rules): array {
        $filters = [];
        
        foreach ($rules as $field => $ruleString) {
            if(!isset($request[$field]))
                continue;

            // Map Go-style rules back to PHP Filter constants
            $tags = explode(',', $ruleString);
            
            if (in_array('numeric', $tags)) {
                $filters[$field] = FILTER_VALIDATE_FLOAT; // Handles both int and float
            } elseif (in_array('email', $tags)) {
                $filters[$field] = FILTER_SANITIZE_EMAIL;
            } elseif (in_array('url', $tags)) {
                $filters[$field] = FILTER_SANITIZE_URL;
            } else {
                $filters[$field] = FILTER_DEFAULT;
            }
        }

        // filter_var_array returns the refined data or null/false on failure
        $refined = filter_var_array($request, $filters);
        
        // Manual cast for Booleans (since they often arrive as "true"/"false" strings)
        foreach ($request as $key => $value) {
            if ($value === 'true') $refined[$key] = true;
            if ($value === 'false') $refined[$key] = false;
        }

        return $refined;
    }
}


/**
 * Validasi Struct berdasarkan tipe data.
 * Mendukung Rules required, email, string, integer, float, boolean, dll.
 * https://pkg.go.dev/github.com/go-playground/validator#section-documentation
 */
if (!function_exists('validateStructData')) {
    function validateStructData($data, $rules) {
        static $ffi = null;
        if ($ffi === null) {
            $ffi = FFI::cdef("
                char* ValidateDynamic(char* input);
                void free(void* ptr);
            ", BASEPATH . "/ffi/lib/dynamic_validate.so");
        }

        // Wrap data and rules into one JSON object
        $payload = json_encode([
            "lang"  => "id", // en || id
            "data"  => $data,
            "rules" => $rules
        ]);

        $cResult = $ffi->ValidateDynamic($payload);
        $response = json_decode(FFI::string($cResult), true);
        $ffi->free($cResult);

        return $response;
    }
}

/**
 * Get Struct roles
 */
if (!function_exists('parseStructToRules')) {
    function parseStructToRules(string $className): array {
        $reflection = new ReflectionClass($className);
        $rules = [];

        foreach ($reflection->getProperties() as $property) {
            // FIX: Use the fully qualified class name for the attribute
            $attributes = $property->getAttributes(\App\Core\Database\SchemaProperty::class);
            
            if (empty($attributes)) continue;

            $attr = $attributes[0]->newInstance();
            $goTags = [];

            // ... (rest of your logic is correct)
            if ($attr->required) $goTags[] = 'required';
            if ($attr->omitempty) $goTags[] = 'omitempty';
            if ($attr->email)    $goTags[] = 'email';
            if ($attr->numeric)  $goTags[] = 'numeric';
            if ($attr->boolean)  $goTags[] = 'boolean';
            if ($attr->gt !== null) $goTags[] = "gt={$attr->gt}";
            if ($attr->gte !== null) $goTags[] = "gte={$attr->gte}";
            if ($attr->lt !== null) $goTags[] = "lt={$attr->lt}";
            if ($attr->lte !== null) $goTags[] = "lte={$attr->lte}";
            if ($attr->min !== null) $goTags[] = "min={$attr->min}";
            if ($attr->max !== null) $goTags[] = "max={$attr->max}";
            if (!empty($attr->custom)) $goTags[] = $attr->custom;

            $rules[$property->getName()] = implode(',', $goTags);
        }

        return $rules;
    }
}

/**
 * Get client IP
 *
 * @return string
 */
function clientIP()
{
    // return (new \App\Core\Security\Middleware\EnsureIpIsValid)->ip();

    // Get real visitor IP behind CDN such as Cloudflare
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }

    $client = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip == '::1' ? '127.0.0.1' : $ip;
}

/**
 * setHeaders function, to add header response
 *
 * @param  array $headers
 *
 * @return bool
 */
function setHeaders($headers = [])
{
    if(count($headers)) {
        foreach ($headers as $header) {
            if(is_array($header)) {
                foreach ($header as $key => $value)
                header("{$key}: {$value}");
            }
        }
    }
}

/**
 * Generates random keys for AES-256 encryption (32 bytes).
 * Output in Base64 format with prefix 'base64:'
 */
function generateAppKey() {
    // AES-256 requires a key that is 32 bytes (256 bits) long.
    $bytes = random_bytes(32);
    
    // Encode to base64 for safe text format
    return 'base64:' . base64_encode($bytes);
}

/**
 * Match encryption data
 *
 * @param  [string] $value
 * @param  [string] $encryptedData
 * @param  [string] $key
 *
 * @return mixed
 */
function matchEncryptedData($value, $encryptedData, $key = null)
{
    try {
        $encryption = new \App\Core\Support\EncryptDecrypt($key);
        return $encryption->match($value, $encryptedData);
    } catch (Throwable $ex) {
        if (config('app.debug')) {
            \write_log([
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                // 'trace' => $ex->getTraceAsString(),
            ], 'InjectHelper.matchEncryptedData', 'eeror', 'error_ENCRYPTION.log');
        }

        return false;
    }
}

/**
 * sort function to encypt data
 *
 * @param  [string] $value
 * @param  [string] $key
 *
 * @return string|null
 */
function encryptData($value, $key = null)
{
    try {
        $encryption = new \App\Core\Support\EncryptDecrypt($key);
        return $encryption->encrypt($value);
    } catch (Throwable $ex) {
        if (config('app.debug')) {
            \write_log([
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                // 'trace' => $ex->getTraceAsString(),
            ], 'InjectHelper.encryptData', 'error', 'error_ENCRYPTION.log');
        }

        return null;
    }
}

/**
 * sort function to decypt data
 *
 * @param  [string] $value
 * @param  [string] $key
 *
 * @return string|null
 */
function decryptData($value, $key = null)
{
    try {
        $encryption = new \App\Core\Support\EncryptDecrypt($key);
        return $encryption->decrypt($value);
    } catch (Throwable $ex) {
        if (config('app.debug')) {
            \write_log([
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                // 'trace' => $ex->getTraceAsString(),
            ], 'InjectHelper.decryptData', 'error', 'error_ENCRYPTION.log');
        }

        return null;
    }
}

/**
 * sort function to generate ulid
 *
 * @param  boolean $lowercased
 * @param  int  $timestamp
 *
 * @return string
 */
function generateUlid($lowercased = false, $timestamp = null): string
{
    if (!is_null($timestamp)) {
        return (string) \App\Core\Support\Ulid::fromTimestamp($timestamp, $lowercased);
    }

    return (string) \App\Core\Support\Ulid::generate($lowercased);
}

/**
 * sort function to generate random string
 *
 * @param  integer $len
 * @param  boolean $base64
 *
 * @return string
 */
function generateRandomString($len = 64, $base64 = false, $special = true): string
{
    if ($base64) {
        return base64_encode(\App\Core\Support\Hash::randomString($len, $special));
    }

    return \App\Core\Support\Hash::randomString($len, $special);
}

// Mendapatkan Header Origin
function get_request_origin() {
    // 1. Cek Header Origin (Utama)
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        return $_SERVER['HTTP_ORIGIN'];
    }

    // 2. Cek Header Referer (Fallback)
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        $parts = parse_url($referer);
        if (isset($parts['scheme']) && isset($parts['host'])) {
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            return $parts['scheme'] . '://' . $parts['host'] . $port;
        }
    }

    // 3. Jika tidak ada, mungkin request langsung (CLI atau Browser tab baru)
    return null;
}

// Untuk menangani CORS (Cross-Origin Resource Sharing)
// Tentukan origin yang diizinkan (dari file .env)
function handle_cors() {
    // 1. Ambil Origin. Jika tidak ada, coba Referer (tapi Origin lebih prioritas untuk CORS)
    // $currentOrigin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
    $currentOrigin = get_request_origin();
    
    // Jika request dari domain yang sama (bukan cross-origin), browser sering tidak kirim Origin.
    // Kita tetap izinkan lanjut tanpa set header CORS khusus.
    if (!$currentOrigin) return;

    // 2. Ambil string dari .env, pecah jadi array, dan bersihkan spasi
    $envOrigins = env('ALLOWED_ORIGINS', '*');
    $allowedOrigins = ($envOrigins !== '*') 
        ? array_map('trim', explode(',', $envOrigins)) 
        : ['*'];

    // 3. Bersihkan Host untuk pengecekan (hapus http:// dan :port)
    $cleanHost = parse_url($currentOrigin, PHP_URL_HOST);

    // 4. LOGIKA VALIDASI
    $isAllowed = false;
    if (in_array('*', $allowedOrigins)) {
        $isAllowed = true;
    } else {
        // Cek apakah host (localhost) ada di daftar whitelist
        if (in_array($cleanHost, $allowedOrigins)) {
            $isAllowed = true;
        }
    }

    if ($isAllowed) {
        // PENTING: Header harus berisi $currentOrigin LENGKAP (termasuk port)
        header("Access-Control-Allow-Origin: $currentOrigin");
        header("Access-Control-Allow-Credentials: true");
    }

    // 5. Header Pendukung (Tetap dikirim untuk preflight)
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY, hx-request, hx-target, hx-current-url, hx-trigger, hx-trigger-name");

    // 6. Handle "Preflight" OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        // Browser butuh status 200 atau 204 untuk preflight
        http_response_code(204); 
        exit();
    }
}


/**
 * Fungsi Validasi API Key
 * * @param string $headerName Nama header yang digunakan (default: X-API-KEY)
 * @return bool
 */
function validateApiKey($headerName = 'X-API-KEY') {

    // Proses hanya jika API Request
    if(!is_json_request())
        return true;

    // 1. Dapatkan header dari server
    // PHP mengubah "X-API-KEY" menjadi "HTTP_X_API_KEY" di $_SERVER
    $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($headerName));
    $apiKeyInput = $_SERVER[$serverKey] ?? null;
    // dd($apiKeyInput);

    if (!$apiKeyInput) {
        return false;
    }

    // 2. Ambil key asli dari config/env
    $secureKey = config('api.key'); // Misal: 'base64:...' atau string random

    // 3. Perbandingan yang aman dari "Timing Attack"
    // Gunakan hash_equals untuk membandingkan string sensitif
    return hash_equals($secureKey, 'base64:'.$apiKeyInput);
}

/**
 * checkRateLimit function
 *
 * @param  string $identifier : client identity IP, Location, etc...
 * @param  int $limit : limit hit 
 * @param  int $timeframeSeconds : time in second
 *
 * @return void
 */
function checkRateLimit($identifier, $limit, $timeframeSeconds) {
    $dirPath = storage_path('/framework/tmp/rate_limits/');
    $filePath =  $dirPath . md5($identifier) . '.txt';

    // Clean tmp-rate_limits
    if(file_exists($dirPath))
        cleanTmpFiles($dirPath, 1);

    // Create directory if it doesn't exist
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0775, true);
    }

    $timestamps = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $timestamps = json_decode($content, true) ?: [];
    }

    $currentTime = time();
    $newTimestamps = [];
    $requestCount = 0;

    // Filter out old timestamps and count recent requests
    foreach ($timestamps as $timestamp) {
        if ($currentTime - $timestamp < $timeframeSeconds) {
            $newTimestamps[] = $timestamp;
            $requestCount++;
        }
    }

    if ($requestCount >= $limit) {
        return false; // Rate limit exceeded
    }

    // Add current request timestamp
    $newTimestamps[] = $currentTime;
    file_put_contents($filePath, json_encode($newTimestamps));

    return true; // Request allowed
}

function checkSession()
{
    try {

        // // Load Gate
        // if(isset($_SESSION['user_id']) && isset($_SESSION['current_team_id'])) {
        //     \App\Core\Auth\Gate::loadAbilities($_SESSION['user_id'], $_SESSION['current_team_id']);
        // }

        if ($_SESSION['IPaddress'] !== $_SERVER['REMOTE_ADDR']) {
            throw new Exception('IP Address mixmatch (possible session hijacking attempt).');
        }

        if ($_SESSION['userAgent'] !== $_SERVER['HTTP_USER_AGENT']) {
            throw new Exception('Useragent mixmatch (possible session hijacking attempt).');
        }

        // if(!$this->loadUser($_SESSION['user_id']))
        //     throw new Exception('Attempted to log in user that does not exist with ID: ' . $_SESSION['user_id']);

        return true;

    } catch (Exception $e) {
        return false;
    }
}

function bp_session_start()
{
    ini_set('session.use_strict_mode', 1);
    @session_start();
    if (isset($_SESSION['destroyed'])) {

        // $ttl = (int)env('SESSION_REGENERATE', 300);
        $ttl = config('session.regenerate', 300);

        // $valid = (bool)($_SESSION['destroyed'] < time() - $ttl);
        // // dd($ttl);
        // dd($valid);

        // Do not allow to use too old session ID
        if (!empty($_SESSION['destroyed']) && $_SESSION['destroyed'] < time() - $ttl) {

            // Regenerate SessioId
            $oldSessionId = session_id();
            $headers = bp_session_regenerate_id($oldSessionId);
            setHeaders($headers);
        }
    }

    // $sessionStrictMode = ini_get('session.use_strict_mode');
    // write_log($sessionStrictMode, 'Helpers.inject_php.bp_session_start.$sessionStrictMode', 'debug');
}

function bp_session_regenerate_id($oldSessionId)
{
    $new_session_id = session_create_id();

    // add info for users with bad connection not receiving the new session id
    $_SESSION['new_session_id'] = $new_session_id;
    // Set destroy timestamp
    $_SESSION['destroyed'] = time();
    // Write and close current session;
    session_commit();

    // backup session variables
    $keepSession = $_SESSION;

    // Start session with new session ID
    ini_set('session.use_strict_mode', 0);
    session_id($new_session_id);

    if (session_status() == PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    $sessionName = session_name();
    $cookie = session_get_cookie_params();
    // $sessionExp = (env('SESSION_LIFETIME', 120) * 60);
    $sessionExp = (config('session.lifetime') * 60);
    $setcookie = ['Set-Cookie' => "{$sessionName}={$new_session_id}; Max-Age={$sessionExp}; Path={$cookie['path']};"];

    // use_strict_mode is mandatory for security reasons.
    ini_set('session.use_strict_mode', 1);

    @session_start();
    $_SESSION = $keepSession;
    // Write and close current session;
    session_commit();

    $saveHandler = ini_get('session.save_handler');

    if($saveHandler === 'files') {
        // Delete Old session file
        $sessionSavePath = session_save_path();
        $fileSessionOld = $sessionSavePath.'/sess_'.$oldSessionId;

        if (\file_exists($fileSessionOld)) {
            $status = unlink($fileSessionOld);
            // write_log($status, 'Helpers.inject_php.bp_session_regenerate_id.unlink-$fileSessionOld', 'debug');
        }
    } else {
        // Delete data redis PHPREDIS_SESSION
        if($saveHandler === 'redis')
            delDataFromRedis($oldSessionId, 'PHPREDIS_SESSION', config('redis.default.database'), true);
    }

    return $setcookie;
}


/**
 * slug function
 *
 * @param  [string] $title
 * @param  string $separator
 * @param  string $language
 * @param  array  $dictionary
 *
 * @return void
 */
function slug($title, $separator = '-', $language = 'en', $dictionary = ['@' => 'at'])
{
    // Convert all dashes/underscores into separator
    $flip = $separator === '-' ? '_' : '-';

    $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, (string) $title);

    // Replace dictionary words
    foreach ($dictionary as $key => $value) {
        $dictionary[$key] = $separator . $value . $separator;
    }

    $title = str_replace(array_keys($dictionary), array_values($dictionary), $title);

    // Remove all characters that are not the separator, letters, numbers, or whitespace
    $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', strtolower($title));

    // Replace all separator characters and whitespace by a single separator
    $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, (string) $title);

    return trim((string) $title, $separator);
}

function delDataFromRedis($id, $prefix = null, $db = null, $force = false)
{
    if(empty($id))
        return;

    // Connect to Redis
    $redis = new \Predis\Client([
        'host' => config('redis.cache.host'),
        'port' => config('redis.cache.port'),
        'database' => $db ?? config('redis.cache.database')
    ]);
    
    $prefix ??= 'bp_data';

    $data = $redis->get($prefix.':'.$id);

    if ((! is_null($data) && isset($data[0])) || $force)
        $redis->del($prefix.':'.$id);
}

function cleanTmpFiles($tmpDir, $daysOld = 3)
{
    // Calculate the timestamp for the threshold
    $thresholdTimestamp = strtotime("-$daysOld days");

    // Check if the directory exists and is readable
    if (!is_dir($tmpDir) || !is_readable($tmpDir)) {
        $message = "Error: Temporary directory '$tmpDir' does not exist or is not readable.\n";
        throw new Exception($message);
    }

    // Open the directory
    if ($handle = opendir($tmpDir)) {
        $message = '';
        while (false !== ($file = readdir($handle))) {
            // Skip '.' and '..'
            if ($file != "." && $file != ".." && $file != ".gitignore") {
                $filePath = $tmpDir . '/' . $file;

                // Check if it's a file and get its modification time
                if (is_file($filePath) && file_exists($filePath)) {
                    $fileModTime = filemtime($filePath);

                    // If the file's modification time is older than the threshold, delete it
                    if ($fileModTime < $thresholdTimestamp) {
                        if (unlink($filePath)) {
                            $message .= "Deleted old temporary file: $filePath\n";
                        } else {
                            $message .= "Failed to delete file: $filePath\n";
                        }
                    }
                }
            }
        }

        // Logging
        if(config('app.debug') && $message !== '') {
            write_log($message, 'InjectHelper.cleanTmpFiles', 'info', 'cleanTmp_'.date('d-m-Y').'.log');
        }

        closedir($handle);
    } else {
        $message = "Error: Could not open temporary directory '$tmpDir'.\n";
        throw new Exception($message);
    }
}
