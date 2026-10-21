<?php

/**
 * Model class
 * @package Backend-PHP
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Database;

use Exception;
use PDO;
use PDOException;

class Model
{
    /**
     * static instance so we can modify the table
     * and primary key property with extending to
     * a model.
     *
     * @var Model|null
     */
    private static $instance = null;

    // /**
    //  * PDO connection.
    //  *
    //  * @var PDO
    //  */
    // private $pdo = null;

    /**
     * Deklarasikan properti secara eksplisit untuk menghindari
     * error Dynamic Property di PHP 8.2+
     */
    protected ?\PDO $pdo;

    protected ?string $sql = null;

    /**
     * Parameters for prepared statements.
     *
     * @var array|[]
     */
    private $params = [];

    /**
     * Current table to query from.
     *
     * @var string
     */
    protected $table;

    // CHUNK-FFI
    protected $rustEngine;
    public $structClass;

    public $limitToStream = 50; // limit maks untuk otomatis menggunakan mode stream
    public $timeCachedCount = 300; // waktu maksimal untuk menyimpan cache pagination count 300 (5 menit)

    public function __construct(?PDO $pdo = null)
    {
        // if we have a different db connection.
        $conn = $pdo ?? Connection::make();

        $this->setPDO($conn);
    }

    // Destructor to close the database connection.
    public function __destruct()
    {
        // Assigning null to the connection variable closes the PDO connection
        $this->pdo = null;
        // Assigning null to the Current table variable
        $this->table = null;
    }

    

    /**
     * Create a new instance or return the
     * previously created instance.
     *
     * @return App\Core\Database\Model
     */
    protected static function instance()
    {
        return self::$instance ?: new static();
    }

    /**
     * Allow specifying table name so we can query
     * database without models.
     *
     * @param string $table
     * @return Model
     */
    public static function table($table)
    {
        $builder = self::instance();

        $builder->table = $table;

        return self::$instance = $builder;
    }

    /**
     * Set current PDO connection.
     *
     * @param PDO|null $pdo
     * @return void
     */
    protected function setPDO($pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get current PDO connection.
     *
     * @return PDO
     */
    protected function getPDO()
    {
        return $this->pdo ?: Connection::make();
    }

    /**
     * Get the current SQL statement.
     *
     * @return string
     */
    protected function getSQL()
    {
        // Check the name of the database driver used
        if (in_array(config('default_db'), ['mariadb', 'mysql'])) {
            return str_replace(["to_date"], ["`to_date`"], $this->sql);
        } else {
            return $this->sql;
        }

    }

    /**
     * Set parameters for current statement.
     *
     * @param array|[] $params
     * @return App\Core\Database\Model
     */
    protected function setParams($params = [])
    {
        $this->params = array_merge($this->params, $params);
        // write_log($this->params, 'Database.Model.setParams', 'debug', 'debug-model.log');

        return $this;
    }

    /**
     * Get parameters for current statement.
     *
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }

    /**
     * Set the current SQL statement.
     *
     * @param string $sql
     * @return App\Core\Database\Model
     */
    // protected function setSQL($sql)
    // {
    //     $this->sql = $sql;

    //     return $this;
    // }
    protected function setSQL(string $query): self
    {
        $this->sql = $query;
        return $this;
    }

    /**
     * Query the current SQL statement.
     *
     * @return PDOStatement|false
     * @throws PDOException
     */
    protected function query()
    {
        try {
            $query = $this->getPDO()->prepare($this->getSQL());

            // write_log($query, 'Database.Model.query.query', 'debug', 'debug-model.log');
            // write_log($this->getSQL(), 'Database.Model.query.getSQL', 'debug', 'debug-model.log');

            if ($query->execute($this->getParams())) {
                $this->params = [];
                return $query;
            }

            return false;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * execQuery
     *
     * @param string $query
     * @param array $params
     * @param bool $lastInsertId
     * @return mixed
     */
    public function execQuery(
        $query,
        array $params,
        $lastInsertId = false,
        bool $fetch = false,
        bool $fetchAll = false,
        bool $stream = false,
        bool $chunk = false,
    ) {
        $this->setParams($params);
        $exec = $this->setSQL($query)->query();

        // PAKSA ke FETCH_ASSOC agar tidak double (angka & nama)
        $exec->setFetchMode(\PDO::FETCH_ASSOC);

        if (!$exec) {
            return false;
        }

        $isUnbuffered = 1;

        // ---NEW FEATURE: CHUNK-FFI
        // dd($chunk);
        // 1001 is the absolute integer value for MYSQL_ATTR_USE_BUFFERED_QUERY
        // Retrieve the attribute value for the CHUNK-FFI flag
        if (in_array(config('default_db'), ['mariadb', 'mysql'])) {
            $rawAttr = $this->pdo->getAttribute(1001);

            // Detection logic: if rawAttr is 0 or false, then Unbuffered is ON
            $isUnbuffered = $rawAttr === false || $rawAttr === 0;
        }

        if ($isUnbuffered && $fetchAll && $chunk) {
            // dd('FITUR BARU: CHUNK-FFI');
            return $this->handleFFIChunking($exec);
        } else {
            if ($lastInsertId) {
                return $this->getPDO()->lastInsertId();
            }

            if ($fetch) {
                return $exec->fetch();
            }

            // ---NEW FEATURE: STREAMING ---
            if ($stream) {
                return (function () use ($exec) {
                    while ($row = $exec->fetch()) {
                        yield $row;
                    }
                })();
            }

            if ($fetchAll) {
                return $exec->fetchAll();
            }
        }

        return true;
    }

    private function handleFFIChunking(\PDOStatement $exec): mixed
    {
        // Dynamically retrieve casting rules based on the struct class being used
        // Assumption: $this->structClass holds a class name like 'App\Structs\ProductStruct'
        $castRules = getCastRules($this->structClass ?? \App\Structs\DefaultStruct::class);
        // dd($this->structClass);
        // dd($castRules);

        $this->rustEngine = new \App\Core\FFI\DataEngine();
        $this->rustEngine->clear();

        $chunkSize = 50000;
        $currentChunk = [];
        try {
            while ($row = $exec->fetch()) {
                // --- DYNAMIC CASTING ---
                foreach ($castRules as $column => $type) {
                    if (isset($row[$column])) {
                        $row[$column] = match ($type) {
                            "int" => (int) $row[$column],
                            "float" => (float) $row[$column],
                            "bool" => (bool) $row[$column],
                            default => (string) $row[$column],
                        };
                    }
                }

                $currentChunk[] = $row;

                if (count($currentChunk) >= $chunkSize) {
                    $this->rustEngine->appendChunk($currentChunk);
                    $currentChunk = [];
                }
            }

            if (!empty($currentChunk)) {
                $this->rustEngine->appendChunk($currentChunk);
                unset($currentChunk);
            }
        } catch (\Throwable $e) {
            die("Dynamic FFI Chunking Error: " . $e->getMessage());
            return false;
        } finally {
            $exec->closeCursor();
            // dd($this->rustEngine->debugFirstItem());
            return $this->rustEngine->streamAll();
        }
    }

    /**
     * Handle Pagination
     *
     * @param string $query Main query without LIMIT/OFFSET
     * @param array $params Parameters for the query
     * @param int $page Current page
     * @param int $limit Amount of data per page
     * @return array [data, total, current_page, last_page, limit]
     */
    public function paginate($query, array $params = [], $page = 1, $limit = 10, $queryCount = null)
    {
        // Sanitize & Ensure Integer data type for security from SQL Injection
        $page = max(1, (int) $page);
        $limit = max(1, (int) $limit);
        $offset = ($page - 1) * $limit;

        // Remove the semicolon at the end of $query (if any) and eliminate extra spaces
        $query = rtrim(trim($query), ';');

        // Add LIMIT and OFFSET in Query
        $paginatedQuery = $query . " LIMIT $limit OFFSET $offset";

        // Data Execution with STREAM Mode is automatic according to limitToStream
        $shouldStream = $limit >= $this->limitToStream;
        $rawData = $this->execQuery($paginatedQuery, $params, false, false, !$shouldStream, $shouldStream);
        // If the data is in the form of a Generator (Streaming), convert it to an Array so that the JSON Controller is not Null
        $dataResult = $rawData instanceof \Generator ? iterator_to_array($rawData) : $rawData;
        // dd($shouldStream, true);

        // Cache data
        $cache = new \App\Core\Support\Cache();
        $cleanQuery = preg_replace("/\s+/", " ", trim($query));
        $queryString = md5((string) $cleanQuery);
        ksort($params);
        $paramSignature = !empty($params) ? md5(json_encode($params) . $queryString) : $queryString;
        $cacheKeyId = "paginate_count:{$this->table}:p{$page}:l{$limit}:{$paramSignature}";

        $callback = function () use ($query, $queryCount, $params, $page, $limit, $offset) {
                // Calculate Total Data (for info pagination)
                $finalQuery = !is_null($queryCount) 
                    ? $queryCount 
                    : "SELECT COUNT(*) AS total_count FROM ($query) AS total";
                $total = $this->execQuery($finalQuery, $params, false, true);

                // Parsing data (Supports Objects and Arrays)
                $totalRows = 0;
                if ($total) {
                    if (is_object($total) && isset($total->total_count)) {
                        $totalRows = (int) $total->total_count;
                    } elseif (is_array($total) && isset($total['total_count'])) {
                        $totalRows = (int) $total['total_count'];
                    }
                }

                // Metadata calculations
                $safeLimit = max(1, (int) $limit);
                $lastPage = (int) ceil($totalRows / $safeLimit);

                return [
                    "total" => $totalRows,
                    "current_page" => $page,
                    "last_page" => $lastPage,
                    "limit" => $limit,
                    "from" => $offset + 1,
                    "to" => min($offset + $limit, $totalRows),
                ];
            };

        $paginationMeta = $cache->remember(
            $cacheKeyId,
            $callback,
            $this->timeCachedCount,
        );
        // dd($paginationMeta, true);

        if($paginationMeta['total'] === 0) {
            $paginationMeta = $callback();
        }

        // Return the metadata AND the data generator
        return [
            "data" => $dataResult,
            "meta" => $paginationMeta,
        ];
    }

    /**
     * Method get Cached Data with Fallback
     * @param string $key Unique identifier for cache
     * @param string $query Main query
     * @param array $params Parameters for the query
     * @param int|null $expiry Expiry time in seconds (600 = 5min)
     */
    public function getCachedData($key, $query, $params = [], $expiry = 600)
    {
        $cache = new \App\Core\Support\Cache();

        return $cache->remember($key, fn () => $this->execQuery($query, $params, false, false, true), $expiry);
    }
}
