<?php 
declare(strict_types=1);

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

use App\Core\Http\NativeCurlStreamer;
use App\Core\Support\App;
use App\Core\Support\Cookie;

// CURL - from Client Web
$streamer = new NativeCurlStreamer();
$start = microtime(true);
$requestMethod = $_SERVER["REQUEST_METHOD"];
$allowMethod = ['POST'];
// dd($requestMethod);

if(!in_array($requestMethod, $allowMethod)) {
    invalid_method_response();
}

// auth_login RestAPI
$singleTask = App::externalApi('auth_login', [
    'body' => json_encode([
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                // List Events should be run
                'calledEvents' => ['order.created', 'order.payment', 'order.notif']
            ])
]);

$single = $streamer->singleStream($singleTask);
// dd($single, true);

$statusCode = $single['statusCode'];
$errors = $single['error'];
$data = json_decode((string) $single['body'] ?? "{}", true);

// Catch cURL Errors (if there is a connection failure, DNS, timeout, etc.)
if ($errors) {
    // $error = is_string($single['error']) ? [$single['error']] : $single['error'];
    $error = ["The server responds with an HTTP Status Code: {$statusCode}"];
    // dd($error);
    
    json_response(null, $statusCode, "login_error", $error);
}

// Parsing REST-API responses
if(!empty($data)) {
    $statusCode = $data['statusCode'] ?? $statusCode;
    $message = $data['message'] ?? "auth_login";
    $errors = $data['errors'] ?? $errors;

    // Parsing Cookies using helpers
    if (empty($errors) && isset($single['header']) && isset($single['headerSize'])) {
        $parsedCookies = parse_curl_cookies($single['header'], $single['headerSize']);

        // Cookie::set() from responses
        foreach ($parsedCookies as $name => $attr) {
            // Configure cookie options
            $options = [
                'expires'  => $attr['expires'] ?? 0,
                'path'     => $attr['path'] ?? '/',
                'domain'   => $attr['domain'] ?? null,
                'secure'   => $attr['secure'] ?? false,
                'httponly' => $attr['httponly'] ?? true,
                'samesite' => $attr['samesite'] ?? 'Lax',
            ];

            // Call the updated Cookie class
            Cookie::set($name, $attr['value'], $options);
        }
    }

    json_response($data['data'] ?? null, $statusCode, $message, $errors);
}

// http_response_code($statusCode);
// dd($single, true);
json_response($data, $statusCode, "Login Failed!", $single);
