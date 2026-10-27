<?php

namespace App\Core\Database;

use PDO;
use PDOStatement;
use App\Core\Database\RawSql;

/**
 * QueryBuilder v2 class
 * Automatic database driver detection (MySQL/PostgreSQL)
 * 
 * @author LutviP19 <lutvip19@gmail.com>
 * @package: PHP-Microdata(PMD)
 */
class QueryBuilderV2
{
    protected PDO $pdo;
    protected string $driver;

    /**
     * Mode fetch default (PDO::FETCH_ASSOC)
     */
    protected int $fetchMode = \PDO::FETCH_ASSOC;

    protected string $table = '';
    protected array $selects = ['*'];
    protected array $joins = [];
    protected array $wheres = [];
    protected array $bindings = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;
    protected array $orders = [];

    // Feature Flags & Soft Delete Config
    protected bool $useSoftDeletes = false;
    protected string $deletedAtColumn = 'deleted_at';
    protected bool $withTrashed = false;
    protected bool $onlyTrashed = false;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make(config('default_db'));
        $this->driver = strtolower($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    /**
     *  Creates a new QueryBuilder instance for the specified table.
     *The $pdo parameter is optional. If null, will search for the default connection.
     * Supports summoning:
     * -QueryBuilder::table('users')
     * -QueryBuilder::table($pdo, 'users')
     * -QueryBuilder::table(null, 'users')
     */
    public static function table(PDO|string|null $pdo = null, string $table = ''): self
    {
        // 1. If the first argument is a string (eg: QueryBuilder::table('users')),
        //    allow flexible invocation without PDO passing.
        if (is_string($pdo)) {
            $table = $pdo;
            $pdo = null;
        }

        // 2. Resolve PDO if null
        $resolvedPdo = $pdo ?? static::resolveDefaultPdo();

        $instance = new static($resolvedPdo);
        $instance->table = $table;

        return $instance;
    }

    /* =========================================================================
     * SOFT DELETE CONFIGURATION & SCOPES
     * ========================================================================= */

    public function useSoftDeletes(bool $enable = true, string $column = 'deleted_at'): self
    {
        $this->useSoftDeletes = $enable;
        $this->deletedAtColumn = $column;
        return $this;
    }

    public function withTrashed(): self
    {
        $this->withTrashed = true;
        return $this;
    }

    public function onlyTrashed(): self
    {
        $this->onlyTrashed = true;
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = sprintf('%s IS NULL', $this->quoteIdentifier($column));
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = sprintf('%s IS NOT NULL', $this->quoteIdentifier($column));
        return $this;
    }

    /* =========================================================================
     * CORE QUERY BUILDER METHODS
     * ========================================================================= */

    /**
     * Specifies the columns to be selected in SELECT.
     *
     * Supported usage examples:
     * -select('id', 'name', 'email')
     * -select(['id', 'name', 'email'])
     * -select('id, name, email')
     * -select() //Default '*'
     */
    public function select(array|string ...$columns): self
    {
        // 1. If there are no arguments at all, set default ['*']
        if (empty($columns)) {
            $this->selects = ['*'];
            return $this;
        }

        $parsedColumns = [];

        foreach ($columns as $column) {
            if (is_array($column)) {
                $parsedColumns = array_merge($parsedColumns, $column);
            } elseif (is_string($column)) {
                $items = array_map('trim', explode(',', $column));
                $parsedColumns = array_merge($parsedColumns, $items);
            }
        }

        $this->selects = array_values(array_unique($parsedColumns));

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'])) {
            $type = 'INNER';
        }

        $this->joins[] = sprintf(
            '%s JOIN %s ON %s %s %s',
            $type,
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($first),
            $operator,
            $this->quoteIdentifier($second)
        );

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Added WHERE condition with AND logic (Default)
     */
    public function where(string $column, string $operator, mixed $value, string $boolean = 'AND'): self
    {
        $placeholder = ':w_' . count($this->bindings);

        //Save the condition along with the boolean operator (AND/OR)
        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $this->quoteIdentifier($column),
            'operator' => $operator,
            'value'    => $placeholder,
            'boolean'  => strtoupper($boolean)
        ];

        $this->bindings[$placeholder] = $value;

        return $this;
    }

    public function whereLike(string $column, string $value, string $boolean = 'AND'): self
    {
        // Use ILIKE for PostgreSQL, LIKE for MySQL/MariaDB
        $operator = ($this->driver === 'pgsql') ? 'ILIKE' : 'LIKE';
        
        // (Assuming you use named placeholders like :w_0 in the previous example)
        $placeholder = ':w_' . count($this->bindings);
        
        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $this->quoteIdentifier($column),
            'operator' => $operator,
            'value'    => $placeholder,
            'boolean'  => strtoupper($boolean)
        ];

        $this->bindings[$placeholder] = '%' . trim($value, '%') . '%';

        return $this;
    }

    /**
     * Explicit aliases for WHERE ... AND ... clauses
     */
    public function whereAnd(string $column, string $operator, mixed $value): self
    {
        return $this->where($column, $operator, $value, 'AND');
    }

    /**
     * Explicit aliases for WHERE...OR... clauses (Optional bonus)
     */
    public function whereOr(string $column, string $operator, mixed $value): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = $sql;
        foreach ($bindings as $key => $val) {
            $this->bindings[$key] = $val;
        }
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = sprintf('%s %s', $this->quoteIdentifier($column), $direction);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    public static function raw(string $value): RawSql
    {
        return new RawSql($value);
    }

    public function quoteIdentifier(string|RawSql $identifier): string
    {
        if ($identifier instanceof RawSql) {
            return $identifier->getValue();
        }

        if ($identifier === '*') {
            return '*';
        }

        if (str_contains($identifier, '.')) {
            $parts = explode('.', $identifier);
            return implode('.', array_map([$this, 'quoteIdentifier'], $parts));
        }

        if ($this->driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Apply Soft Delete Scope automatically if enabled
     */
    protected function applySoftDeleteScope(): void
    {
        if (!$this->useSoftDeletes) {
            return;
        }

        if ($this->onlyTrashed) {
            $this->whereNotNull($this->deletedAtColumn);
        } elseif (!$this->withTrashed) {
            $this->whereNull($this->deletedAtColumn);
        }
    }

    public function toSql(): string
    {
        $this->applySoftDeleteScope();

        $cols = implode(', ', array_map([$this, 'quoteIdentifier'], $this->selects));
        $sql = sprintf('SELECT %s FROM %s', $cols, $this->quoteIdentifier($this->table));

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= $this->compileWheres();
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitValue !== null) {
            $sql .= sprintf(' LIMIT %d', $this->limitValue);
        }

        if ($this->offsetValue !== null) {
            $sql .= sprintf(' OFFSET %d', $this->offsetValue);
        }

        return $sql;
    }

    /**
     * Built-in helper for compiling the $this->wheres array into a SQL string
     */
    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sqlConditions = [];

        foreach ($this->wheres as $index => $where) {

            if (is_array($where) && isset($where['column'], $where['operator'], $where['value'])) {
                $condition = sprintf('%s %s %s', $where['column'], $where['operator'], $where['value']);
            } else {
                $condition = is_string($where) ? $where : '';
            }

            if ($index === 0) {
                // The first condition is without AND/OR affixes
                $sqlConditions[] = $condition;
            } else {
                // The 2nd and subsequent conditions begin with a boolean (AND /OR)
                $sqlConditions[] = sprintf('%s %s', $where['boolean'], $condition);
            }
        }

        return 'WHERE ' . implode(' ', $sqlConditions);
    }

    public function get(): mixed
    {
        $stmt = $this->executePrepared($this->toSql(), $this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): mixed
    {
        $this->limit(1);
        $stmt = $this->executePrepared($this->toSql(), $this->bindings);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /* =========================================================================
     * AGGREGATES & PAGINATION
     * ========================================================================= */

    protected function aggregate(string $function, string $column = '*'): mixed
    {
        $columnSql = ($column === '*') ? '*' : $this->quoteIdentifier($column);

        // 1. Save the current property state
        $previousSelects = $this->selects;
        $previousOrders  = $this->orders;
        $previousLimit   = $this->limitValue;
        $previousOffset  = $this->offsetValue;

        // 2. Set SELECT directly as a pure aggregate expression without passing identifier wrapping
        //    Use QueryBuilder::raw() if available, or bypass it in the compiler
        if (method_exists(static::class, 'raw')) {
            $this->selects = [static::raw(sprintf('%s(%s) AS aggregate', strtoupper($function), $columnSql))];
        } else {
            $this->selects = [sprintf('%s(%s) AS aggregate', strtoupper($function), $columnSql)];
        }

        // 3. Reset ORDER BY, LIMIT, and OFFSET so that the total count is accurate
        $this->orders = [];
        $this->limitValue  = null;
        $this->offsetValue = null;

        // 4. Execute queries
        $sql  = $this->toSql();
        $stmt = $this->executePrepared($sql, $this->bindings);
        $result = $stmt->fetch();

        // 5. Restore the original state of QueryBuilder
        $this->selects = $previousSelects;
        $this->orders  = $previousOrders;
        $this->limitValue   = $previousLimit;
        $this->offsetValue  = $previousOffset;

        return is_array($result) 
            ? ($result['aggregate'] ?? null) 
            : ($result?->aggregate ?? null);
    }

    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    public function avg(string $column): float
    {
        return (float) $this->aggregate('AVG', $column);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $total = $this->count();

        $offset = ($page - 1) * $perPage;
        $this->limit($perPage)->offset($offset);

        $items = $this->get();
        $lastPage = (int) ceil($total / $perPage);

        $paginationMeta = [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, $lastPage),
            'from'         => $total > 0 ? $offset + 1 : null,
            'to'           => $total > 0 ? min($offset + $perPage, $total) : null,
            'has_more'     => $page < $lastPage,
        ];

        return [
            'data'         => $items,
            "meta"         => $paginationMeta,
        ];
    }

    /* =========================================================================
     * CUD OPERATIONS (INSERT, UPDATE, DELETE, RESTORE)
     * ========================================================================= */

    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $quotedColumns = array_map([$this, 'quoteIdentifier'], $columns);

        $placeholders = [];
        $bindings = [];

        foreach ($data as $key => $val) {
            $ph = ':' . $key;
            $placeholders[] = $ph;
            $bindings[$ph] = $val;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($this->table),
            implode(', ', $quotedColumns),
            implode(', ', $placeholders)
        );

        $this->executePrepared($sql, $bindings);
        return true;
    }

    public function insertGetId(array $data, string $primaryKey = 'id'): string|false
    {
        if ($this->driver === 'pgsql') {
            $columns = array_keys($data);
            $quotedColumns = array_map([$this, 'quoteIdentifier'], $columns);

            $placeholders = [];
            $bindings = [];
            foreach ($data as $key => $val) {
                $ph = ':' . $key;
                $placeholders[] = $ph;
                $bindings[$ph] = $val;
            }

            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s) RETURNING %s',
                $this->quoteIdentifier($this->table),
                implode(', ', $quotedColumns),
                implode(', ', $placeholders),
                $this->quoteIdentifier($primaryKey)
            );

            $stmt = $this->executePrepared($sql, $bindings);
            return $stmt->fetchColumn();
        }

        $this->insert($data);

        return $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        // $this->applySoftDeleteScope();

        $setClauses = [];
        $updateBindings = [];

        foreach ($data as $column => $value) {
            // Clean up the column names so they can safely be used as PDO placeholder names
            $safeColumnName = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
            $ph = ':u_' . $safeColumnName;

            $setClauses[] = sprintf('%s = %s', $this->quoteIdentifier($column), $ph);
            $updateBindings[$ph] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s %s',
            $this->quoteIdentifier($this->table),
            implode(', ', $setClauses),
            $this->compileWheres()
        );

        // IMPORTANT: Combine UPDATE bindings ($updateBindings) with WHERE bindings ($this->bindings)
        $allBindings = array_merge($updateBindings, $this->bindings);

        $stmt = $this->executePrepared($sql, $allBindings);

        return $stmt->rowCount();
    }

    /**
     * Delete Data (Automatic Soft Delete if enabled, or Hard Delete if disabled/forced)
     */
    public function delete(bool $force = false): int
    {
        if ($this->useSoftDeletes && !$force) {
            return $this->update([
                $this->deletedAtColumn => date('Y-m-d H:i:s')
            ]);
        }

        return $this->forceDelete();
    }

    /**
     * Permanently Delete Record from DB
     */
    public function forceDelete(): int
    {
        $this->applySoftDeleteScope();

        $sql = sprintf('DELETE FROM %s', $this->quoteIdentifier($this->table));

        if (!empty($this->wheres)) {
            $sql .= $this->compileWheres();
        }

        $stmt = $this->executePrepared($sql, $this->bindings);
        return $stmt->rowCount();
    }

    /**
     * Restore Soft-Deleted Data
     */
    public function restore(): int
    {
        if (!$this->useSoftDeletes) {
            return 0;
        }

        // Run a special restore for data in the trash
        $this->onlyTrashed();

        return $this->update([
            $this->deletedAtColumn => null
        ]);
    }

    public function queryRaw(string $sql, array $bindings = []): mixed
    {
        $stmt = $this->executePrepared($sql, $bindings);
        return $stmt->fetchAll();
    }

    public function statementRaw(string $sql, array $bindings = []): int
    {
        $stmt = $this->executePrepared($sql, $bindings);
        return $stmt->rowCount();
    }

    protected function executePrepared(string $sql, array $bindings = []): PDOStatement
    {
        // 1. Make sure $this->pdo is not null (take fallback if it is not already set)
        $pdo = $this->pdo ?? static::resolveDefaultPdo();

        // write_log($sql, 'app/Core/Database/QueryBuilderV2.php', 'debug', 'debug_DB-SQL.log');

        // 2. Execute the query directly on the PDO instance
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt;
    }

    /**
     * Built-in helper to retrieve PDO fallback
     */
    protected static function resolveDefaultPdo(): PDO
    {
        if (class_exists(\App\Core\Database\Connection::class)) {
            return \App\Core\Database\Connection::make();
        }

        throw new \RuntimeException("Koneksi PDO tidak tersedia dan tidak dapat di-resolve otomatis.");
    }
}