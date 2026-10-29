<?php

namespace App\Core\Database;

use App\Core\Database\ConnectionV2 as Connection;
use App\Core\Database\QueryBuilderV2 as QueryBuilder;
use PDO;
use RuntimeException;
use ReflectionClass;

/**
 * BaseModel Class
 *
 * Has Active Record, Soft Delete, and Lifecycle Hooks features.
 * @author LutviP19 <lutvip19@gmail.com>
 * @package: PHP-Microdata(PMD)
 */
abstract class BaseModelV2
{
    /**
     * PDO properties for this instance (Instance-based)
     *
     */
    protected ?PDO $pdo = null;

    /**
     * Properti PDO fallback global (Static-based)
     */
    protected static ?PDO $staticPdo = null;

    protected static string $table = '';
    protected static string $primaryKey = 'id';

    /**
     * Configure Soft Delete on Model
     */
    protected static bool $useSoftDeletes = false;
    protected static string $deletedAtColumn = 'deleted_at';

    // CHUNK-FFI
    protected $rustEngine;
    public $structClass;

    public $limitToStream = 50; // max limit to automatically use stream mode
    public $timeCachedCount = 300; // Maximum time to cache pagination count 300 (5 minutes)

    /**
     * Constructor accepts dynamic PDO.
     * If null, automatically takes a fallback connection (Connection::make() /Pool).
     */
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            // Priority 1: Inject manually from Constructor
            // $this->pdo = $pdo;
            $this->setPDO($pdo);
        } else {
            // Priority 2: Resolution via Fallback / Context
            $this->pdo = static::getConnection();
        }
    }


    /**
     * getDatabaseName function
     *
     * @param PDO $pdo
     * @return string
     */
    public static function getDatabaseName(PDO $pdo): string
    {
        try {
            // Check the name of the database driver used
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'pgsql') {
                // PostgreSQL
                return (string) $pdo->query('SELECT CURRENT_DATABASE()')->fetchColumn();
            }

            if ($driver === 'sqlite') {
                // SQLite
                return 'sqlite';
            }

            // MySQL / MariaDB (Default)
            return (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

        } catch (\Throwable $e) {
            return 'Unknown';
        }
    }

    /**
     * Changing PDO connections on this model instance fluidly
     */
    public function setPdo(?PDO $pdo): static
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * Gets the PDO belonging to the current instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo ?? static::getConnection();
    }

    /**
     * Method to CHANGE /OVERRIDE connections dynamically
     */
    public static function setConnection(?PDO $pdo): void
    {
        // save it to static properties
        static::$staticPdo = $pdo;
    }

    /**
     * Get a fallback PDO connection (Auto-resolving if not set)
     */
    public static function getConnection(): PDO
    {
        // ---------------------------------------------------------------------
        // PRIORITY FOR PDO
        // ---------------------------------------------------------------------
        // A. If set manually via setConnection() in FPM
        if (static::$staticPdo !== null) {
            return static::$staticPdo;
        }

        // B. Fallback FPM /CLI biasa via Connection::make()
        static::$staticPdo = Connection::make();
        return static::$staticPdo;

        throw new RuntimeException("PDO Connection belum di-set dan class Connection not found.");
    }

    public static function getTableName(): string
    {
        if (!empty(static::$table)) {
            return static::$table;
        }

        $className = (new ReflectionClass(static::class))->getShortName();
        return strtolower(str_replace(['Struct','Data','Model'], "", $className)) . 's';
    }

    /**
     * Setting up QueryBuilder with Soft Delete configuration from Model
     */
    public static function query(): QueryBuilder
    {
        $connection = static::getConnection();

        $builder = QueryBuilder::table($connection, static::getTableName());

        if (static::$useSoftDeletes) {
            $builder->useSoftDeletes(true, static::$deletedAtColumn);
        }

        return $builder;
    }

    /* =========================================================================
     * LIFECYCLE HOOKS (Dapat Di-override di Child Model)
     * ========================================================================= */

    protected static function beforeSave(array &$data): void
    {
    }
    protected static function beforeCreate(array &$data): void
    {
    }
    protected static function afterCreate(string|false $id, array $data): void
    {
    }

    protected static function beforeUpdate(mixed $id, array &$data): void
    {
    }
    protected static function afterUpdate(mixed $id, array $data, int $affectedRows): void
    {
    }

    protected static function beforeDelete(mixed $id): void
    {
    }
    protected static function afterDelete(mixed $id, int $affectedRows): void
    {
    }

    /* =========================================================================
     * SOFT DELETE SHORTCUT SCOPES
     * ========================================================================= */

    public static function withTrashed(): QueryBuilder
    {
        return static::query()->withTrashed();
    }

    public static function onlyTrashed(): QueryBuilder
    {
        return static::query()->onlyTrashed();
    }

    public static function restoreById(mixed $id): int
    {
        return static::query()
            ->where(static::$primaryKey, '=', $id)
            ->restore();
    }

    public static function forceDeleteById(mixed $id): int
    {
        return static::query()
            ->where(static::$primaryKey, '=', $id)
            ->forceDelete();
    }

    /* =========================================================================
     * CRUD OPERATOR DENGAN LIFECYCLE HOOKS
     * ========================================================================= */

    public static function all(): mixed
    {
        return static::query()->get();
    }

    public static function find(mixed $id): mixed
    {
        return static::query()
            ->where(static::$primaryKey, '=', $id)
            ->first();
    }

    public static function where(string $column, string $operator, mixed $value): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function select(array|string $columns = ['*']): QueryBuilder
    {
        $cols = is_array($columns) ? $columns : func_get_args();
        return static::query()->select($cols);
    }

    /**
     * Insert new record by triggering Lifecycle Hook
     */
    public static function create(array $data): string|false
    {
        static::beforeSave($data);
        static::beforeCreate($data);

        $id = static::query()->insertGetId($data, static::$primaryKey);

        static::afterCreate($id, $data);

        return $id;
    }

    /**
     * Update records based on ID by triggering a Lifecycle Hook
     */
    public static function updateById(mixed $id, array $data): int
    {
        static::beforeSave($data);
        static::beforeUpdate($id, $data);

        $affected = static::query()
            ->where(static::$primaryKey, '=', $id)
            ->update($data);

        static::afterUpdate($id, $data, $affected);

        return $affected;
    }

    /**
     * Delete records (Soft/Hard) based on ID by triggering Lifecycle Hook
     */
    public static function deleteById(mixed $id, bool $force = false): int
    {
        static::beforeDelete($id);

        $affected = static::query()
            ->where(static::$primaryKey, '=', $id)
            ->delete($force);

        static::afterDelete($id, $affected);

        return $affected;
    }

    public static function paginate(int $perPage = 15, int $page = 1, bool $isCacheCount = true): array
    {
        return static::query()->paginate($perPage, $page, $isCacheCount);
    }

    public static function getPaginationRange(int $currentPage, int $totalPages, int $delta = 1): array
    {
        $range         = [];
        $rangeWithDots = [];
        $l             = null;

        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $delta && $i <= $currentPage + $delta)) {
                $range[] = $i;
            }
        }

        foreach ($range as $i) {
            if ($l) {
                if ($i - $l === 2) {
                    $rangeWithDots[] = $l + 1;
                } elseif ($i - $l !== 1) {
                    $rangeWithDots[] = '...';
                }
            }
            $rangeWithDots[] = $i;
            $l = $i;
        }

        return $rangeWithDots;
    }

    public static function count(string $column = '*'): int
    {
        return static::query()->count($column);
    }

    public static function rawQuery(string $sql, array $bindings = []): mixed
    {
        return static::query()->queryRaw($sql, $bindings);
    }

    public static function rawStatement(string $sql, array $bindings = []): int
    {
        return static::query()->statementRaw($sql, $bindings);
    }

    /**
     * Instantly uses a custom connection for this query.
     */
    public static function onConnection(string $connectionName): QueryBuilder
    {
        $pdo = Connection::fromConfig($connectionName);
        $builder = QueryBuilder::table($pdo, static::getTableName());

        if (static::$useSoftDeletes) {
            $builder->useSoftDeletes(true, static::$deletedAtColumn);
        }

        return $builder;
    }

    /**
     * Gets the QueryBuilder that is bound to this instance's PDO
     */
    public function newQuery(): QueryBuilder
    {
        $builder = new QueryBuilder($this->pdo);

        $table = property_exists(static::class, 'table')
            ? static::$table
            : static::getTableName();

        return $builder::table($this->pdo, $table);
    }

    /**
     * Protected helper to convert arrays to stdClass automatically
     */
    protected static function toObject(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map(fn ($item) => is_array($item) ? (object) $item : $item, $data);
        }
        return is_array($data) ? (object) $data : $data;
    }

    /**
     * Forward The call method QueryBuilder (Like where, orderBy, paginate)
     */
    public function __call($method, $parameters)
    {
        return $this->newQuery()->$method(...$parameters);
    }

    /**
     * Still supports static call (Product::where) using the default connection
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static();
        return $instance->newQuery()->$method(...$parameters);
    }
}
