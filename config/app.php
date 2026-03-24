<?php

/**
 *  @author LutviP19 <lutvip19@gmail.com>
 *  main app configurations
 */

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
        'key' => env('APP_KEY', 'base64:AA2L6m2LEf0WzxHpVuHxkLZEd7vsm6TWDGUUAjmYGnk='),
        'hash_key' => env('HASH_KEY', 'base64:RUHnrVLvGQXm8SLvFv2zI+YQH8KNGOhbiy1gGAqGFt0='),
    ],

    /**
     * Api config details.
     */
    'api' => [
        'key' => env('API_KEY', 'base64:FxfDQdiN9IguAgG5NSfESiNryDdAQf9aBiZKLIklNoE='),
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

    /**
     * Session
     */
    'session' => [
        'csrf_token' => 'csrf_token',
        'lifetime' => (int) env('SESSION_LIFETIME', 120), // in minutes
        'regenerate' => (int) env('SESSION_REGENERATE', 300), // in secoonds
        'encrypt' => env('SESSION_ENCRYPT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
     */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', slug(env('APP_NAME', 'backendphp'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
