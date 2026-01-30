<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', str_replace('config', '', __DIR__));
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
        'name' => 'Backend PHP',
        'url' => 'http://localhost',
        'path' => BASE_PATH,
        'env' => 'local',
        'debug' => true,
        // 'logdir' => __DIR__ . '/../storage/logs/',
    ],

    /**
     * Database Credentials.
     */
    'default_db' => 'mysql',

    'database' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => '',
            'dbname' => database_path('database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'backend_php',
            'username' => 'root',
            'password' => '',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]) : [],
        ],
    ],

];
