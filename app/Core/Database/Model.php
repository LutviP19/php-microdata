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

    /**
     * PDO connection.
     *
     * @var PDO
     */
    private $pdo = null;

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

    protected $limitToStream = 50; // limit maks untuk menggunakan stream
    protected $timeCachedCount = 300; // waktu maksimal untuk menyimpan cache pagination count 300 (5 menit)

    public function __construct(PDO $pdo = null)
    {
        // if we have a different db connection.
        $conn = $pdo ?: Connection::make();

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
     * execQuery
     *
     * @param string $query
     * @param array $params
     * @param bool $lastInsertId
     * @return mixed
     */
    public function execQuery($query, array $params, $lastInsertId = false, $fetch = false, $fetchAll = false, $stream = false)
    {
        $this->setParams($params);        
        $exec = $this->setSQL($query)->query();        

        if (!$exec) return false;

        if ($lastInsertId) {
            return $this->getPDO()->lastInsertId();
        }

        if ($fetch) {
            return $exec->fetch();
        }

        // --- FITUR BARU: STREAMING ---        
        if ($stream) {
            return (function() use ($exec) {
                while ($row = $exec->fetch()) {
                    yield $row;
                }
            })();
        }

        if ($fetchAll) {
            return $exec->fetchAll();
        }

        return true;
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

        // // EXTRA SETUP
        // if ($this->pdo) {
        //     // Add this line so that all query results automatically become an Associative Array
        //     $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        //     // Disabled Buffer to decrease usage of RAM
        //     $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        // }
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
        return $this->sql;
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
    protected function setSQL($sql)
    {
        $this->sql = $sql;

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

            if($query->execute($this->getParams())) {
                $this->params = [];
                return $query;
            }

            return false;

        } catch (PDOException $e) {
            throw $e;
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
    public function paginate($query, array $params = [], $page = 1, $limit = 10)
    {
        // dd($this->table);
        // Offset Calculation
        $page = (int)$page > 0 ? (int)$page : 1;
        $offset = ($page - 1) * $limit;

        // Add LIMIT and OFFSET in Query
        $paginatedQuery = $query . " LIMIT $limit OFFSET $offset";

        // Eksekusi Data dengan Mode STREAM otomatis sesuai limitToStream
        $shouldStream = ($limit > $this->limitToStream);
        $dataGenerator = $this->execQuery($paginatedQuery, $params, false, false, !$shouldStream, $shouldStream);
        // dd($shouldStream, true);


        // Cache data
        $cache = new \App\Core\Support\Cache();        
        $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
        $queryString = md5($cleanQuery);
        ksort($params);
        $paramSignature = !empty($params) ? md5(json_encode($params).$queryString) : $queryString;
        $cacheKeyId = "paginate_count:{$this->table}:p{$page}:l{$limit}:{$paramSignature}";
        $paginationMeta = $cache->remember($cacheKeyId, function() use ($query, $params, $page, $limit, $offset) {

            // Calculate Total Data (for info pagination)
            $countQuery = "SELECT COUNT(*) AS total_count FROM ($query) AS total";
            $total = $this->execQuery($countQuery, $params, false, true);

            // Parsing data (Supports Objects and Arrays)
            if ($total) {
                $totalRows = is_object($total) ? (int)$total->total_count : (int)$total['total_count'];
            }

            // Metadata calculations
            $lastPage = ceil($totalRows / $limit);
            
            return [
                'total' => $totalRows,
                'current_page' => $page,
                'last_page' => $lastPage,
                'limit' => $limit,
                'from' => $offset + 1,
                'to' => min($offset + $limit, $totalRows)
            ];
        }, $this->timeCachedCount);
        // dd($paginationMeta, true);

        // Kembalikan metadata DAN generator datanya
        return [
            'data' => $dataGenerator,
            'meta' => $paginationMeta
        ];
    }

    /**
     * Method get Cached Data with Fallback
     * @param string $key Unique identifier for cache
     * @param string $query Main query
     * @param array $params Parameters for the query
     * @param int|null $expiry Expiry time in seconds (600 = 5min)
     */
    public function getCachedData($key, $query, $params = [], $expiry = 600) {
        $cache = new \App\Core\Support\Cache();
        
        return $cache->remember($key, function() use ($query, $params) {
            return $this->execQuery($query, $params, false, false, true);
        }, $expiry);
    }
}