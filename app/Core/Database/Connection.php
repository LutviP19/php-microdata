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
    public static function make()
    {
        try {
            $driver = Config::get('default_db');

            if ($driver !== 'sqlite') {
                $dbname   = Config::get("database.{$driver}.dbname");
                $host     = Config::get("database.{$driver}.host");
                $port     = Config::get("database.{$driver}.port");
                $username = Config::get("database.{$driver}.username");
                $password = Config::get("database.{$driver}.password");
                $options  = Config::get("database.{$driver}.options");
                // dd("{$driver}:host={$host};port={$port};dbname={$name}");

                $pdo = new PDO(
                    "{$driver}:host={$host};port={$port};dbname={$dbname}",
                    $username,
                    $password,
                    $options
                );

            } else {
                $databaseFile = Config::get("database.{$driver}.dbname");
                // dd("sqlite:{$databaseFile}");

                $pdo = new PDO("sqlite:{$databaseFile}");
                // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for better error handling
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
    public static function custom($driver = '', $dbname = '', $host = '', $port = '', $username = '', $password = '', $options = [])
    {
        try {
            $driver = $driver ?: Config::get('default_db');
            $options  = $options ?: Config::get("database.{$driver}.options");
            // dd($username);


            if ($driver !== 'sqlite') {
                // dd("{$driver}:host={$host};port={$port};dbname={$dbname}");
                // dd(['username' => $username, 'password' => $password, 'options' => $options]);

                $pdo = new PDO(
                    "{$driver}:host={$host};port={$port};dbname={$dbname}",
                    $username,
                    $password,
                    $options
                );            

            } else {
                $databaseFile = database_path($dbname);
                // dd("sqlite:{$databaseFile}");

                $pdo = new PDO("sqlite:{$databaseFile}");
                // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for better error handling
            }
            // dd($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        
            return $pdo;

        } catch (PDOException $e) {
            // die($e->getMessage());
            if(env('APP_ENV') !== 'local') {
                if(env('APP_DEBUG'))
                \write_log($e->getMessage(), 'App\Core\Database\Connection', 'error', 'error_DB.log');
                
                json_response([], 403, 'Auth errors', ['auth' => 'Invalid credentials.']);
            }  else {                
                json_response([], 403, 'Auth errors', ['auth' => $e->getMessage()]);
            }
            die();
        }
    }

}
