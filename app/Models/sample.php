<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models;


class MyModel 
{
    public function index(?array $request = [])
    {
        $modelA = [
            'title' => $request['title'] ?? 'Testing model',
        ];

        $data = [
            'data' => $modelA,
            // 'errors' => $errors ?? [],
            'status' => $status ?? 201,
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
            'data' => $modelA ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing edit',
        ];

        return $data;
    }

    public function update(?array $request = [])
    {
        $data = [
            'data' => $modelA ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing update',
        ];

        return $data;
    }

    public function destroy(?array $request = [])
    {
        $data = [
            'data' => $modelA ?? [],
            'errors' => $errors ?? [],
            'status' => $status ?? 201,
            'message' => $message ?? 'testing destroy',
        ];

        return $data;
    }
}

/**
 * Set variable $modelClass.
 */
$modelClass = new MyModel();
