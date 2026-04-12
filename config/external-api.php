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
            'User-Agent' => 'PHP-FFI-App',
            'Content-Type' => 'application/json',
            'X-API-KEY' => str_replace('base64:', '', config('api.key'))
        ]
    ],
    'dashboard_store' => [
        'method' => 'POST',
        'url' => 'http://localhost:8000/api/v1/dashboard',
        'headers' => [
            'User-Agent' => 'PHP-FFI-App',
            'Content-Type' => 'application/json'
        ]
    ],
];