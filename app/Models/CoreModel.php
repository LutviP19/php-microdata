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

class CoreModel extends Model
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
        $this->setPDO($conn);
    
        // Set default table
        // $this->table = self::$tableM;
    }


}