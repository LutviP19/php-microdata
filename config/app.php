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
        'name' => env('APP_NAME', 'PHP Microdata Server'),
        'url' => env('APP_URL', 'http://localhost'),
        'path' => BASEPATH,
        'env' => env('APP_ENV', 'production'),
        'debug' => (bool) env('APP_DEBUG', false),
        'logdir' => BASEPATH . '/storage/logs/',
        'key' => env('APP_KEY', 'base64:AA2L6m2LEf0WzxHpVuHxkLZEd7vsm6TWDGUUAjmYGnk='),
        'hash_key' => env('HASH_KEY', 'base64:RUHnrVLvGQXm8SLvFv2zI+YQH8KNGOhbiy1gGAqGFt0='),
        'jwt_secret' => env('JWT_SECRET', '152*vd61174Df9@eba3b4Y6ed9d452adae762j!b13H4Deym36i6Qo1f9m4d^n76'),
        'sodium_key' => env('SODIUM_KEY', 'd9cffa8b79abd6ff9260e1aa23ffa65af2846cbbf21e8f0910cd7a1b09178df8'),
        'sodium_private_key' => env('SODIUM_PRIVATE_KEY', '6a1a0b5ce76f343f488a71eeee8719b82900d2df8976942dc42e7bcdd8ffb52f529e7b68eb1175261dc3c100c587be4eb09f262d3f620b3d775d972765e6544c'),
        'sodium_public_key' => env('SODIUM_PUBLIC_KEY', '529e7b68eb1175261dc3c100c587be4eb09f262d3f620b3d775d972765e6544c'),
        'sodium_prefix' => env('SODIUM_PREFIX', 'v1.access.microdata'),
        'encrpytion_id' => env('APP_ENC_ID', false),
        'cache_driver' => env('CACHE_DRIVER', 'files'),
        'queue_driver' => env('QUEUE_DRIVER', 'redis'),
    ],

    /**
     * Api config details.
     */
    'api' => [
        'key' => env('API_KEY', 'base64:FxfDQdiN9IguAgG5NSfESiNryDdAQf9aBiZKLIklNoE='),
        'whitelist_ips' => null, // null||[]: no filter, (Array)IP/CIDR: ['127.0.0.1', '192.168.1.0/24', '10.0.0.0/8']
    ],

    /**
     * Clients Api Credentials and default setup.
     */
    'clients' => [
        'client_web' => [
            'x-api-token' => env('CLENT_WEB_API_TOKEN', ''),

        ],
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
                // Deteksi otomatis konstanta SSL CA (Mendukung PHP < 8.5 & 8.5+)
                (defined('\Pdo\Mysql::ATTR_SSL_CA') ? \Pdo\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),

                // Konstanta standar PDO (Tidak berubah di 8.5)
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,

                // Tambahan: Jika Anda menggunakan fitur Unbuffered (Optional)
                // (defined('\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY') ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY)
                //    => true,
                // Gunakan 1001 langsung untuk menjamin kompatibilitas di PHP 8.3 - 8.5
                1001 => true,
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

    /**
     * Message Broker Credentials.
     */
    'default_mb' => env('MB_CONNECTION', 'rabbitmq'),

    'broker' => [
        'rabbitmq' => [
            'scheme' => env('MB_SCHEME', 'amqps'),
            'host' => env('MB_HOST', '127.0.0.1'),
            'port' => env('MB_PORT', '5672'),
            'username' => env('MB_USERNAME', 'guest'),
            'password' => env('MB_PASSWORD', 'guest'),
            'queue_name' => env('MB_QUEUE_NAME', 'backend_php-queue'),
        ],
    ],
];
