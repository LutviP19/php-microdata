<?php

if (!defined('BASEPATH')) {
    define('BASEPATH', str_replace('/config', '', __DIR__));
}

/**
 * Config values for our application.
 *
 * @return array
 */
return [

    /**
     * Application config details.
     */
    'app' => [
        'name' => env('APP_NAME', 'Backend PHP'),
        'url' => env('APP_URL', 'http://localhost'),
        'path' => BASEPATH,
        'env' => env('APP_ENV', 'production'),
        'debug' => (bool) env('APP_DEBUG', false),
        'logdir' => BASEPATH . '/storage/logs/',
    ],

    /**
     * Database Credentials.
     */
    'default_db' => env('DB_CONNECTION', 'sqlite'),

    'database' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'dbname' => database_path(env('DB_DATABASE', 'database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'dbname' => env('DB_DATABASE', 'backend_php'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]) : [],
        ],
    ],

];
