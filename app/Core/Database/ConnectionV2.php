<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use RuntimeException;
use App\Core\Support\Config;

/**
 * Connection class
 * 
 * @author LutviP19 <lutvip19@gmail.com>
 * @package PHP-Microdata(PMD)
 */
class ConnectionV2
{
    /**
     * Created a default PDO instance based on the 'default_db' option in the configuration file.
     *
     * @return PDO
     * @throws PDOException|RuntimeException
     */
    public static function make(?string $connectionName = null): PDO
    {
        $connectionName = $connectionName ?: Config::get('default_db', 'pgsql');
        return static::fromConfig($connectionName);
    }

    /**
     * Create a PDO instance from a specific connection name in the configuration (e.g. 'mysql', 'pgsql', 'db_reporting').
     *
     * @param string $connectionName The name of the entry in the config database
     * @return PDO
     * @throws RuntimeException|PDOException
     */
    public static function fromConfig(string $connectionName = 'default_db'): PDO
    {
        // 1. Resolve connection name if called with default parameter 'default_db'
        if (!$connectionName || is_null($connectionName) || empty($connectionName)) {
            $connectionName = config('default_db');
        }

        // 2. Retrieve the entire configuration array for the target connection
        $config = Config::get("database.{$connectionName}");

        // 3. Resolve to default with helper
        if(!$connectionName || !$config) {
            $defaultConn = config('default_db');
            $config = config("database.{$defaultConn}");
        }

        if (!is_array($config)) {
            throw new RuntimeException("The database configuration for connection '{$connectionName}' was not found.");
        }

        $driver = $config['driver'] ?? 'pgsql';        

        // 3. Handling khusus SQLite
        if ($driver === 'sqlite') {
            $dbPath = $config['dbname'] ?? (function_exists('database_path') ? database_path('database.sqlite') : 'database.sqlite');
            $options = $config['options'] ?? [];

            return static::custom(
                driver: 'sqlite',
                dbname: $dbPath,
                options: $options
            );
        }

        // 4. General and driver specific options (MySQL, MariaDB, POSTGRESQL, SQLSrv)
        $options = $config['options'] ?? [];

        return static::custom(
            driver:      $driver,
            dbname:      $config['dbname'] ?? '',
            host:        $config['host'] ?? '127.0.0.1',
            port:        (string) ($config['port'] ?? ($driver === 'pgsql' ? '5432' : '3306')),
            username:    $config['username'] ?? '',
            password:    $config['password'] ?? '',
            options:     $options,
            charset:     $config['charset'] ?? 'utf8mb4',
            sslmode:     $config['sslmode'] ?? 'prefer',
            search_path: $config['search_path'] ?? 'public'
        );
    }

    /**
     * Create a low-level PDO instance (custom parameters) with a dynamic DSN builder.
     *
     * @param string $driver Database driver (mysql, mariadb, pgsql, sqlsrv, sqlite)
     * @param string $dbname Nama database /path file sqlite
     * @param string $host Hostname /IP address
     * @param string $port Port database
     * @param string $username Username database
     * @param string $password Password database
     * @param array $options Opsi atribut PDO
     * @param string $charset Charset encoding (Example: utf8mb4)
     * @param string $sslmode Opsi SSL untuk PostgreSQL
     * @param string $search_path Schema search_path untuk PostgreSQL
     * @return PDO
     * @throws PDOException
     */
    public static function custom(
        string $driver,
        string $dbname,
        string $host = '127.0.0.1',
        string $port = '5432',
        string $username = '',
        string $password = '',
        array $options = [],
        string $charset = 'utf8mb4',
        string $sslmode = 'prefer',
        string $search_path = 'public'
    ): PDO {
        try {
            $driver = strtolower($driver ?: Config::get("default_db", "pgsql"));
            // dd($driver);

            // Arrange DSN based on Driver
            switch ($driver) {
                case 'sqlite':
                    // If the path is not absolute, wrap it with the database_path helper if available
                    $databaseFile = (function_exists('database_path') && !file_exists($dbname)) 
                        ? database_path($dbname) 
                        : $dbname;
                    $dsn = "sqlite:{$databaseFile}";
                    break;

                case 'pgsql':
                    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode};options='--search_path={$search_path}'";
                    break;

                case 'sqlsrv':
                    $dsn = "sqlsrv:Server={$host},{$port};Database={$dbname}";
                    break;

                case 'mariadb':
                case 'mysql':
                default:
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    break;
            }

            // Get default options
            $options = !empty($options) ? (array) $options : (array) \config("database.{$driver}.options", []);

            // Create a PDO instance
            // dd($dsn);
            if ($driver === 'sqlite') {
                $pdo = new PDO((string) $dsn, null, null, $options);
            } else {
                $pdo = new PDO((string) $dsn, (string) $username, (string) $password, $options);
            }

            // Set the default Error Mode to Exception if it is not already set in options
            if (!isset($options[PDO::ATTR_ERRMODE]) && \config('debug')) {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }

            return $pdo;

        } catch (PDOException $e) {
            // Write an error log if the helper/function write_log is available
            if (function_exists('write_log') && config('app.debug')) {
                $logPath = '/Core/Database/Connection';
                
                write_log('[DB Connection Error] ' . $e->getMessage(), $logPath, 'error', 'error_DB-Conn.log');
            }

            // Re-throw the exception so that it is caught by the main DatabasePoolManager /Runner
            throw $e;
        }
    }
}