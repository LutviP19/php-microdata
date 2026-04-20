<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Database\Model;

use App\Core\Auth\SodiumAuth;
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
    protected $auth;

    private int $expToken;
    private int $refreshTokenTimeout;

    public function __construct(?PDO $pdo = null)
    {
        $conn = $pdo ?? Connection::make();
        parent::__construct($conn);

        $this->expToken = (int) (config('app.session.lifetime') / 2); // 1 hour
        $this->refreshTokenTimeout = 7; // X days
        $this->jwt = new JWT();
        $this->auth = new SodiumAuth();
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
        // Saat register/login karyawan baru
        $newUlid = generateUlid();


        // Simpan ke DB jika belum ada ulid
        // $db->query("UPDATE {$this->table} SET ulid) = ? WHERE id = ?", [$newUlid, $user_id]);
        
        $accessTokenPayload = [
            // 'uid'  => $user->ulid,
            'uid'  => 12345,
            'ulid' => $newUlid,
            'username' => 'dev_user',
            'type' => 'access',
            'fingerprint' => get_device_fingerprint(),
            'exp' => $this->expToken, // Expired dalam 1 jam
            'role' => [ 
                "asset-create", 
                "asset-view", 
                "asset-edit", 
                "asset-delete", 
                "user-manage", 
                "report-view" 
            ]
        ];

        // Merged payload
        $accessTokenPayload = array_merge($accessTokenPayload, $payload);

        $refreshTokenPayload = [
            // 'uid'  => $user->ulid,
            'uid'  => 12345,
            'ulid' => $newUlid,
            'type' => 'refresh',
            'fingerprint' => get_device_fingerprint(),
            'exp'  => time() + (3600 * 24 * $this->refreshTokenTimeout) // 7 Hari
        ];
        
        $dataToken = [
            'access_token'  => $this->jwt->encode($accessTokenPayload),
            'refresh_token' => $this->jwt->encode($refreshTokenPayload)
        ];

        // Generate Token JWT
        return $dataToken;
    }

    protected function validateJWT($token, ?string $permission = null)
    {        
        // Cara Mengecek Permission di Sisi Server
        $decoded = $this->jwt->decode($token);

        if(!$decoded)
            return null;

        if ($decoded && $permission) {
            $permissions = $decoded['user_permissions'] ?? [];

            // Cek dengan Gate
            if(\App\Core\Auth\Gate::authorizeJwt($permission))
                return $permissions;
        }

        return $decoded;
    }

    protected function generateTokenSodium(array $payload = [])
    {
        $newUlid = generateUlid();
        // dd($newUlid);

        // Simpan ke DB jika belum ada ulid
        // $db->query("UPDATE {$this->table} SET ulid) = ? WHERE id = ?", [$newUlid, $user_id]);

        $accessTokenPayload = [         
            // 'uid'  => $user->ulid,
            'uid'  => 12345,
            'ulid' => $newUlid,
            'role' => 'backend_dev',
            'type' => 'access',
            'fingerprint' => get_device_fingerprint(),
            'exp'  => $this->expToken // 1 Jam
        ];

        // Merged payload
        $accessTokenPayload = array_merge($accessTokenPayload, $payload);

        $refreshTokenPayload = [
            // 'uid'  => $user->ulid,
            'uid'  => 12345,
            'ulid' => $newUlid,
            'type' => 'refresh',
            'fingerprint' => get_device_fingerprint(),
            'exp'  => time() + (3600 * 24 * $this->refreshTokenTimeout) // 7 Hari
        ];

        $tipeRefreshToken = $this->auth->encode($refreshTokenPayload);
        $dataToken = [
            'access_token'  => $this->auth->encode($accessTokenPayload),
            'refresh_token' => $tipeRefreshToken
        ];

        // Generate Token Sodium
        return $dataToken;
    }

    protected function authorizeSodium($token, ?string $permission = null)
    {        
        // Cara Mengecek Permission di Sisi Server
        $decoded = $this->jwt->decode($token);

        if(!$decoded)
            return null;

        if ($decoded && $permission) {
            $permissions = $decoded['user_permissions'] ?? [];
            
            // Cek dengan Gate
            if(\App\Core\Auth\Gate::authorize($permission))
                return $permissions;
        }

        return $decoded;
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