<?php

/**
 * Cache class
 * @author LutviP19 <lutvip19@gmail.com>
 */

 
namespace App\Core\Support;


use Exception;
use Predis\Client as PredisClient;

class Cache 
{
    // Object class
    private $redisClient;
    private $storagePath;
    private $defaultExpiry = 3600;

    public function __construct() 
    {
        $this->redisClient = null;
        
        // Lazy Initialization of Redis (Only if set as CACHE_DRIVER)
        if(env('CACHE_DRIVER') === 'redis') {
            try {
                $this->redisClient = new PredisClient([
                    'host' => Config::get('redis.cache.host'),
                    'port' => Config::get('redis.cache.port'),
                    'database' => Config::get('redis.cache.database')
                ]);
            } catch (Exception $e) {
                $this->redisClient = null;
            }
        } else {
            // Define cache folder (Adapt to your folder structure)
            $this->storagePath = storage_path('/framework/cache/');
        }
    }

    /**
     * Remember Pattern: Fetch cache or save if it doesn't exist
     */
    public function remember($key, $callback, $expiry = null) 
    {
        $expiry = $expiry ?: $this->defaultExpiry;
        $data = $this->get($key);

        if ($data !== null) {
            return $data;
        }

        $data = $callback();
        $this->set($key, $data, $expiry);

        return $data;
    }

    /**
     * Retrieve Data
     */
    public function get($key) 
    {
        // Strategy 1: Redis
        if ($this->redisClient) {
            try {
                $data = $this->redisClient->get($key);
                if ($data) return unserialize($data);
            } catch (Exception $e) {
                $this->redisClient = null; // Fallback ke file
            }
        }

        // Strategy 2: Fallback Files
        $file = $this->storagePath . md5($key) . '.cache';
        if (file_exists($file)) {
            $content = unserialize(file_get_contents($file));
            if (time() < $content['expiry']) {
                return unserialize($content['data']);
            }
            unlink($file); // Delete if expired
        }

        return null;
    }

    /**
     * Save Data
     */
    public function set($key, $data, $expiry = 3600) 
    {
        //  Only cache if data is not empty
        if(!empty($data)) {

            //  Only cache if total data not 0
            if((isset($data['total']) && $data['total'] === 0) || (isset($data['data']['total']) && $data['data']['total'] === 0)) {
                // dd($data["total"]);
                return;
            }
        
            $serialized = serialize($data);

            // Save to Redis
            if ($this->redisClient) {
                try {
                    $this->redisClient->setex($key, $expiry, $serialized);
                    return;
                } catch (Exception $e) {
                    $this->redisClient = null;
                }
            }

            // Save to File (Fallback)
            if (!is_dir($this->storagePath)) mkdir($this->storagePath, 0775, true);
            $content = serialize([
                'expiry' => time() + $expiry,
                'data'   => $serialized
            ]);
            file_put_contents($this->storagePath . md5($key) . '.cache', $content);
        }
    }

    /**
     * Hapus Cache (Flush)
     */
    public function flush($key) 
    {
        // Delete in Redis
        if ($this->redisClient) {
            try { $this->redisClient->del($key); } catch (Exception $e) {}
        }

        // Delete in Files
        $file = $this->storagePath . md5($key) . '.cache';
        if (file_exists($file)) unlink($file);
    }
}
