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
            if ($attr->email)    $goTags[] = 'email';
            if ($attr->numeric)  $goTags[] = 'numeric';
            if ($attr->gte !== null) $goTags[] = "gte={$attr->gte}";
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
 * checkRateLimit function
 *
 * @param  string $identifier : client identity IP, Location, etc...
 * @param  int $limit : limit hit 
 * @param  int $timeframeSeconds : time in second
 *
 * @return void
 */
function checkRateLimit($identifier, $limit, $timeframeSeconds) {
    $dirPath = storage_path('/framework/tmp/rate_limits');
    $filePath =  $dirPath .'/'. md5($identifier) . '.txt';

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

        // if(!is_numeric($_SESSION['user_id']))
        //     throw new Exception('No session started.');

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

        $ttl = (int)env('SESSION_REGENERATE', 300);

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
    $sessionExp = (env('SESSION_LIFETIME', 120) * 60);
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
        delDataFromRedis($oldSessionId, 'PHPREDIS_SESSION', '0', true);
    }

    return $setcookie;
}
