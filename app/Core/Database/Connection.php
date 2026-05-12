<?php

/**
 * Connection class
 * @package Backend-PHP
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Database;

use PDO;
use PDOException;
use App\Core\Support\Config;

class Connection
{
    /**
     * Method make PDO connection
     *
     * @return void
     */
    public static function make(): PDO
    {
        try {
            $driver = Config::get("default_db");

            if ($driver !== "sqlite") {
                $dbname = Config::get("database.{$driver}.dbname");
                $host = Config::get("database.{$driver}.host");
                $port = Config::get("database.{$driver}.port");
                $username = Config::get("database.{$driver}.username");
                $password = Config::get("database.{$driver}.password");
                $options = Config::get("database.{$driver}.options");

                $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname}";
                if ($driver === "mysql") {
                    $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                }

                // dd($dsn);
                $pdo = new PDO($dsn, $username, $password, $options);
            } else {
                $databaseFile = Config::get("database.{$driver}.dbname");
                $pdo = new PDO("sqlite:{$databaseFile}");

                // Set error mode for better error handling
                if (config("app.debug")) {
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
            }
            // dd($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

            return $pdo;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Method make Custom PDO connection
     *
     * @return void
     */
    public static function custom(
        string $driver = "",
        string $dbname = "",
        string $host = "",
        string $port = "",
        string $username = "",
        string $password = "",
        ?array $options = [],
    ): PDO {
        try {
            $driver = $driver ?: Config::get("default_db");
            $options = array_merge($options, Config::get("database.{$driver}.options"));
            // dd($options);

            if ($driver !== "sqlite") {
                $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname}";
                if ($driver === "mysql") {
                    $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                }

                // dd($dsn);
                $pdo = new PDO($dsn, $username, $password, $options);
            } else {
                $databaseFile = database_path($dbname);
                $pdo = new PDO("sqlite:{$databaseFile}");

                // Set error mode for better error handling
                if (config("app.debug")) {
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
            }
            // dd($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

            return $pdo;
        } catch (PDOException $e) {
            // die($e->getMessage());
            if (config("app.env") === "production") {
                if (config("app.debug")) {
                    \write_log($e->getMessage(), \App\Core\Database\Connection::class, "error", "error_DB.log");
                }

                json_response([], 403, "Auth errors", ["auth" => "Invalid credentials."]);
            } else {
                json_response([], 403, "Auth errors", ["auth" => $e->getMessage()]);
            }
            die();
        }
    }
}
