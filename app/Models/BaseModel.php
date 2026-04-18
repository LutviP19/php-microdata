<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Database\Model;

use App\Core\Auth\JWT;
use PDO;
use Exception;
use PDOException;
use App\Core\Database\Connection;

class BaseModel extends Model
{
    /**
     * static table name for this model.
     *
     * @var string
     */
    protected static $tableM;

    protected $primaryKey = 'id';

    protected $jwt;

    public function __construct(?PDO $pdo = null)
    {
        $conn = $pdo ?? Connection::make();
        parent::__construct($conn);

        $this->jwt = new JWT();
    }

    protected function setRatelimiter($identifier, $perSeconds = 120, $limit = 10)
    {
        $identifier = str_replace(" ", "_", $identifier);
        $identifier = $identifier.'-'.\clientIP();
        if (false === checkRateLimit($identifier, $limit, $perSeconds)) {
            $after = $perSeconds / 60;
            $afteText = $after > 1 ? "{$after} minutes" : "{$after} minute";
            $errors = [
                'busy' => ["Please try again after {$afteText}."]
            ];
            
            json_response([], 429, 'Too many requests', $errors);
        }
    }

    protected function generateTokenJWT(array $payload = [])
    {
        // Sample Payload
        $payload = [
            'user_id' => 123,
            'username' => 'dev_user',
            'user_permissions' => [ 
                "asset-create", 
                "asset-view", 
                "asset-edit", 
                "asset-delete", 
                "user-manage", 
                "report-view" 
            ],
            'exp' => time() + (60 * 60) // Expired dalam 1 jam
        ];

        $expToken = (int) (config('app.session.lifetime') / 2);
        $payload = array_merge($payload, ['exp' => time() + ($expToken * 60)]);

        // Generate Token
        return $this->jwt->encode($payload);
    }

    protected function validateJWT($token, ?string $permission = null)
    {        
        // Cara Mengecek Permission di Sisi Server
        $decoded = $this->jwt->decode($token);

        if ($decoded && $permission) {
            $permissions = $decoded['user_permissions'] ?? [];

            // Contoh pengecekan akses untuk fitur tertentu
            if (in_array($permission, $permissions)) {
                return true;
            } else {
                return false;
            }

            return $decoded;
        }

        return null;
    }

    /**
     * Ambil semua data
     */
    public function all($columns = '*') {
        $stmt = $this->pdo->prepare("SELECT $columns FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil satu data berdasarkan ID
     */
    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Insert data secara dinamis berdasarkan array
     */
    public function insertData(array $data) {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data berdasarkan ID
     */
    public function updateData($id, array $data) {
        $fields = "";
        foreach ($data as $key => $value) {
            $fields .= "$key = ?, ";
        }
        $fields = rtrim($fields, ', ');

        $sql = "UPDATE {$this->table} SET $fields WHERE {$this->primaryKey} = ?";
        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Soft Delete atau Hard Delete
     */
    public function deleteData($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}