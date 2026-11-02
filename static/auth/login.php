<?php 
declare(strict_types=1);

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// dd(config('app'));

use App\Core\Http\NativeCurlStreamer;
use App\Core\Support\App;

// Testing CURL - from Client Web
$streamer = new NativeCurlStreamer();
$start = microtime(true);

// Test JSON
$singleTask = App::externalApi('auth_login', [
    'body' => json_encode([
                'email' => $_REQUEST['email'],
                'password' => $_REQUEST['password'],
                // List Events should be run
                'calledEvents' => ['order.created', 'order.payment', 'order.notif']
            ])
]);

$single = $streamer->singleStream($singleTask);
// dd($single, true);
// dd($single['body'], true);

// Set Cookie
if(!isset($single['error'])) {
    $data = json_decode((string) $single['body'], true);

    // Set Header for Cookie
    if(isset($data['data']['sessionId'])) {
        $sessionName = APP_SESSID;
        $sessionId = $data['data']['sessionId'];
        $sessionExp = config('session.exptime');
        
        $headers = [
            'Set-Cookie' =>  "{$sessionName}={$sessionId}; Max-Age={$sessionExp}; Path=/; SameSite=Lax; HttpOnly"
        ];
        setHeaders($headers);
    }

    $statusCode = $data['statusCode'];
    http_response_code($statusCode);
    dd($data, true);
}

http_response_code(401);
dd(json_decode($single['body'], true), true);
// dd($single['statusCode'], true);
