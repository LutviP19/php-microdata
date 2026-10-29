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
                'email' => 'test@microdata.local',
                'password' => 'abcde1234',
                // List Events should be run
                'calledEvents' => ['order.created', 'order.payment', 'order.notif']
            ])
]);

$single = $streamer->singleStream($singleTask);
// dd($single, true);
dd(json_decode($single['body'], true), true);
// dd($single['statusCode'], true);



