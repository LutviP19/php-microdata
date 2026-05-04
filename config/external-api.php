<?php

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 *  main external API endpoints configurations
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', str_replace('/config', '', __DIR__));
}

/**
 * Config values for our external API endpoints and Setup basic Headers.
 *
 * @return array
 */
return [
    'dashboard_get' => [
        'method' => 'GET',
        'url' => 'http://localhost:8000/api/v1/dashboard',
        'headers' => [
            'User-Agent' => 'PHP-Microdata',
            'Content-Type' => 'application/json',
            'X-API-KEY' => str_replace('base64:', '', config('api.key'))
        ]
    ],
    'dashboard_store' => [
        'method' => 'POST',
        'url' => 'http://localhost:8000/api/v1/dashboard',
        'headers' => [
            'User-Agent' => 'PHP-Microdata',
            'Content-Type' => 'application/json'
        ]
    ],

    // Client - Webhook
    'microdata_client_web' => [
        'method' => 'POST',
        'url' => 'http://localhost:8008/api/v1/webhook',
        'headers' => [
            'User-Agent' => 'PHP-Microdata',
            'Content-Type' => 'application/json',
            'Accept' => 'application/x-protobuf;q=0.9, application/json;q=0.8, text/plain;q=0.5',
            'X-Api-Token' => 'eyJpdiI6InE2TUNrNGFsbDJYclUxMUh0S1pNS2c9PSIsInZhbHVlIjoiMlRRUGE0R2FWWERMU1UycmoycXZFYXhXMEhvcFZ6TXFuTHByZ1BMNnNORT0iLCJtYWMiOiIwZjA5ZmFiMmNkOGM3YTFlZWEwNTQ2OWYxYTE0N2ZmOWNiZTVkN2U4OWQxMzcyYTNkOGU3YjllNzc0OGFmZDVkIiwidGFnIjoiIn0=',
        ]
    ],

    // Client - Testing
    'testing_client_web' => [
        'method' => 'GET',
        'url' => 'http://demo.local:8008/api/v1/testing',
        'headers' => [
            'User-Agent' => 'PHP-Microdata',
            'Content-Type' => 'application/json',
            'Accept' => 'application/x-protobuf;q=0.9, application/json;q=1.8, text/plain;q=0.5',
            'Accept-Encoding' => 'gzip',
            'X-Api-Token' => 'eyJpdiI6InE2TUNrNGFsbDJYclUxMUh0S1pNS2c9PSIsInZhbHVlIjoiMlRRUGE0R2FWWERMU1UycmoycXZFYXhXMEhvcFZ6TXFuTHByZ1BMNnNORT0iLCJtYWMiOiIwZjA5ZmFiMmNkOGM3YTFlZWEwNTQ2OWYxYTE0N2ZmOWNiZTVkN2U4OWQxMzcyYTNkOGU3YjllNzc0OGFmZDVkIiwidGFnIjoiIn0=',
        ]
    ],

    // Services
    'ms_enabled_experimental' => [
        'method' => 'PATCH',
        'url' => env('MEILISEARCH_URL', 'http://localhost:7700') . '/experimental-features',
        'headers' => [
            'User-Agent' => 'PHP-Microdata',
            'Authorization' => 'Bearer ' . env('MEILISEARCH_KEY', 'ms'),
            'Accept'        => 'application/json',
            'Content-Type' => 'application/json'
        ]
    ],
];