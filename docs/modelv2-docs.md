
# `BaseModelV2` Framework Documentation

`BaseModelV2` is an abstract Active Record and QueryBuilderV2 bridge class designed for high-performance PHP applications. It supports both **PHP-FPM** environments and **FrankenPHP Coroutine-based** concurrency with automatic context awareness, connection pool isolation, soft deletes, lifecycle hooks, and fluid query building.

---

## Key Features

1. **Dual Engine Support (FrankenPHP & PHP-FPM):** Automatically handles Coroutine context switching and connection pooling without deadlocks, while seamlessly falling back to standard PHP-FPM.
2. **Active Record & Dynamic Query Scope:** Execute queries via static calls like `User::where(...)` or instance-based queries using `$user->newQuery()`.
3. **Soft Delete Capability:** Built-in soft deletion with scopes (`withTrashed()`, `onlyTrashed()`, `restoreById()`, `forceDeleteById()`).
4. **Lifecycle Hooks:** Extensible hooks (`beforeSave`, `beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`) for clean domain event driven code.
5. **Multi-Database Connection Switching:** On-the-fly connection targeting using `Model::onConnection('mysql_read')`.

---

## Configuration & Usage Guide

### Defining a Structs

Inherit from `App\Core\Database\BaseModelV2` and set the static configuration properties.

```php
<?php

namespace App\Structs\User;

use App\Core\Database\BaseModelV2;
use App\Core\Database\SchemaProperty;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class UserStruct extends BaseModelV2
{
    /**
     * Set Validations Rules based from SchemaProperty
     */
    #[SchemaProperty(description: 'User ULID id', required: true, max: 26)]
    public string $ulid;

    #[SchemaProperty(description: 'User display name', required: true, min: 3, max: 255)]
    public string $name;

    #[SchemaProperty(description: 'Primary email address', required: true, email: true)]
    public string $email;

    #[SchemaProperty(description: 'User password', required: true, min:8, max: 255)]
    public string $password;

    // Optional
    #[SchemaProperty(description: 'Client token for API', omitempty: true, min:3, max:100)]
    public string $client_token;

    #[SchemaProperty(description: 'User token for remember login', omitempty: true, min:3, max:100)]
    public string $remember_token;    

    /**
     * Inject custom DB connection
     */
    public function __construct(?PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);
    }
}

```

### Defining a Data Repository

Inherit from `App\Structs\User\UserStruct` and set the static configuration properties.

```php
<?php

namespace App\Data\User;

use App\Structs\User\UserStruct;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class UserData extends UserStruct
{
    /**
     * Target database table (Defaults to pluralized class name if left empty: "users")
     */
    protected static string $table = 'users';

    /**
     * Primary key column name
     */
    protected static string $primaryKey = 'id';

    /**
     * Enable or disable Soft Deletes
     */
    protected static bool $useSoftDeletes = true;

    /**
     * Soft delete timestamp column
     */
    protected static string $deletedAtColumn = 'deleted_at';
    
    /**
     * Inject custom DB connection
     */
    public function __construct(?PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);
    }

    /**
     * getUserProfile
     */
    public function getUserProfile(?int $id = null) { 
        if(is_null($id))
            return false;

        // Logic to getUserProfile
        return static::where(static::$primaryKey, '=', $id)->get();
    }
}

```

### Defining a Model Logic

Inherit from `App\Data\User\UserData` as repository data and set the static configuration properties.

```php
<?php

namespace App\Models;

use App\Data\Dashboard\UserData;
use PDO; // new PDO object
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection

class UserModel extends UserData
{
    /**
     * Inject custom DB connection
     */
    public function __construct(?PDO $pdo = null)
    {
        // // Global Set Custom connection
        // $pdo = Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        
        // Default connection
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);
    }

    public function index(?array $request = [])
    {
        // Logic for index+
    }

    public function store(?array $request = [])
    {
        // Logic for store 
    }

    public function edit(?array $request = [])
    {
        // Logic for edit 
    }

    public function update(?array $request = [])
    {
        // Logic for update 
    }

    public function destroy(?array $request = [])
    {
        // Logic for destroy 
    }
}
```

---

## Core Operations

### 1. Basic CRUD

```php
// Create
$userId = UserData::create([
    'name'  => 'John Doe',
    'email' => 'john@example.com'
]);

// Read
$users = UserData::all();
$user  = UserData::find(1);
$activeUsers = UserData::where('status', '=', 'active')->get();

// Update
UserData::updateById(1, [
    'name' => 'John Updated'
]);

// Delete
UserData::deleteById(1); // Performs soft delete if enabled
UserData::deleteById(1, true); // Force hard delete

```

### 2. Soft Deletes

```php
// Query including soft-deleted items
$allUsers = UserData::withTrashed()->get();

// Query only soft-deleted items
$trashedUsers = UserData::onlyTrashed()->get();

// Restore a soft-deleted record
UserData::restoreById(1);

// Permanently delete a record
UserData::forceDeleteById(1);

```

### 3. Lifecycle Hooks

Override these protected static methods in your child model class to automatically intercept lifecycle steps:

```php
protected static function beforeCreate(array &$data): void
{
    // Mutate data before creation
    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    $data['created_at'] = date('Y-m-d H:i:s');
}

protected static function afterCreate(string|false $id, array $data): void
{
    // Trigger notifications/events after creation
    write_log("User created with ID: {$id}", 'info', 'UserData.create');
}

```

### 4. Multi-Connection & Multi-Tenant Support

Switch database connections per query seamlessly across both FrankenPHP and FPM:

```php
// Run query on a secondary database connection (e.g., read replica or secondary tenant)
$logs = AuditLog::onConnection('logging_db')
    ->where('level', '=', 'error')
    ->get();

```

---

## Generator Stubs CLI / Code Generators (Planed)

Below are the command to build your basic model with CLI generator (e.g., `./generator DashboardModel`).

### Generator

Use this for basic template models.

```bash
 # Examples:
 *   ./generator DashboardModel
 *   ./generator AdminSettingsModel App/Models/Admin

 # Others:
 *   ./generator AdminSettingsController app/Controllers/Admin
 *   ./generator UserApiController App/Controllers/Api
 *   ./generator detail_pesanan views/order

```
