<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Database\Model;

use PDO;
use Exception;
use PDOException;

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
        parent::__construct($pdo);
    }


}