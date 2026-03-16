<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


// use App\Core\Database\Model;
use App\Core\Support\Session;

use PDO; // new PDO object

class DashboardModel extends CoreModel
{

    /**
     * static table name for this model.
     *
     * @var string
     */
    protected static $tableM = "users";

    public function __construct(PDO $pdo = null)
    {
        // // Custom connection
        // $driver = '';
        // $name = '';
        // $host = '';
        // $port = '';
        // $username = '';
        // $password = '';
        // $options = [];
        // $conn = $pdo ?: Connection::custom($driver, $name, $host, $port, $username, $password, $options);
        // parent::__construct($conn);
        
        // Default connection
        parent::__construct($pdo);

        // Set default table
        $this->table = self::$tableM;
    }

    public function index(?array $request = [])
    {
        // // Test Session
        // $sql = 'SELECT * FROM '.$this->table.' LIMIT 10';
        // $data = CoreModel::table($this->table)->execQuery($sql, [], false, false, true);
        // dd($data);

        // // Testing Regenerate SessioId
        // $oldSessionId = session_id();
        // $headers = bp_session_regenerate_id($oldSessionId);
        // setHeaders($headers);

        // // Session::set('jwtId', generateUlid());
        // dd(Session::get('jwtId'));



        // $selectCols = $cols ?? '*';
        // $sql = 'SELECT '.$selectCols.' FROM '.$this->table.' WHERE id = ? LIMIT 1';
        // $result = Model::table($this->table)->execQuery($sql, [$id ?? 1], false, true, false);
        // dd($result, true);

        // dd($request, true);
        // dd(config('app.url'), true);
        $modelA = [
            'title' => $request['title'] ?? 'Testing model',
        ];

        $data = [
            'data' => $modelA,
            // 'errors' => $errors ?? [],
            'status' => $status ?? 200,
            // 'message' => $message ?? 'testing index',
        ];

        return $data;
    }

    public function store(?array $request = [])
    {
        // $errors = [
        //     'input_a' => 'This field is required.',
        // ];
        // $status = 400;
        // $message = 'Invalid input store.';

        $data = [
            'data' => $request ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing store',
        ];

        return $data;
    }

    public function edit(?array $request = [])
    {
        $data = [
            'data' => $request ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 200,
            'message' => $message ?? 'testing edit',
        ];

        return $data;
    }

    public function update(?array $request = [])
    {
        $data = [
            'data' => $request ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing update',
        ];

        return $data;
    }

    public function destroy(?array $request = [])
    {
        $data = [
            'data' => $request ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing destroy',
        ];

        return $data;
    }
}
