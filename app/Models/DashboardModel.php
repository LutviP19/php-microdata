<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


use App\Core\Database\Model;

class DashboardModel extends Model
{
    public function __construct()
    {
        parent::__construct();

        // Set default table
        $this->table = 'roles';
    }

    public function index(?array $request = [])
    {

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
