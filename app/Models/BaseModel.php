<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Database\Model;

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

    public function __construct(PDO $pdo = null)
    {
        $conn = $pdo ?: Connection::make();
        parent::__construct($conn);

        // Set PDO connection
        $this->pdo = $conn;
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
}