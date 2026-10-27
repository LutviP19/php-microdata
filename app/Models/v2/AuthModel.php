<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Models\v2;


// use App\Core\Support\EncryptDecrypt;
// use App\Core\Support\Session;
use App\Data\Auth\v2\{AuthData};
use App\Core\Database\ConnectionV2 as Connection; // Uncomment to use custom DB connection
use PDO;

class AuthModel 
{

    /**
     * PDO properties for this instance (Instance-based)
     *
     */
    protected ?PDO $conn = null;

    public function __construct(?PDO $pdo = null)
    {
        // Use Default connection
        $this->conn = $pdo ?? Connection::make(config('default_db'));
    }

    public function index(?array $request = [])
    {
        // Create instance
        // dd($this->conn);
        $mainData = new AuthData($this->conn);
        // $statsData = new StatsData(null);

        $dataAuth = null;
        $dataStats = null;
        
        // // Cache data
        // $cache = new \App\Core\Support\Cache();

        // Ambil data untuk paginate result
        $page = (int) ($request['page'] ?? 1);
        $limit = (int) ($request['limit'] ?? 10); // total data perpage


        // // Ambil data stats dari cache selama 5 menit
        // $dataStats = $cache->remember('auth_index', function() use ($statsData) {
        //     return $statsData->getAllData();
        // }, 300);

        // // Query dasar
        // $query = "SELECT * FROM ".$mainData::getTableName()." ORDER BY updated_at DESC";

        
        // Set limit agar bisa auto Stream
        $mainData->limitToStream = 2;
        // $mainData::rawQuery($query); // Using Raw SQL
        $mainData->orderBy('updated_at', 'DESC');

        // Cek limit agar otomatis masuk ke Mode Stream
        if($limit > $mainData->limitToStream) {            
            $dataAuth = $mainData->paginate($limit, $page);
        } else {
            $cleanQuery = preg_replace('/\s+/', ' ', trim($query));
            $queryString = md5((string) $cleanQuery);
            $cacheKeyId = "auth_data:{$mainData::getTableName()}:" . $queryString . ":p{$page}:l{$limit}";
            $dataAuth = $cache->remember($cacheKeyId, fn() => $mainData->paginate($limit, $page), 300);
        }

        $modelA = [
            // 'result' => $result,            
            'request' => $request,
            'table' => $mainData::getTableName(),
            'title' => $request['title'] ?? 'Auth model v1',
            'identity' => \App\Core\Auth\Identity::get(),
            'stats_data' => $dataStats,
            'pagination_data' => $dataAuth, // Ini mode cache
        ];

        $data = [            
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing index v1',
        ];
        // $data = array_merge($data, $dataAuth);

        // Output Stream
        if($limit > $mainData->limitToStream) {
            $realData = $dataAuth['data'];
            $paginationMeta = $dataAuth['meta'];
            
            unset($modelA['pagination_data']);
            return json_response_stream(
                $data['status'], 
                $data['message'],
                $realData, 
                $paginationMeta,
                $modelA
            );
        }


        // Return normal 
        return handle_response_error($data, $modelA);
    }

    public function store(?array $request = [])
    {
        // Set model Data
        $modelData = new AuthData($this->conn);

        $modelA = [
            'request' => $request ?? [],
            'table' => $modelData::getTableName(),
            'title' => $request['title'] ?? 'Testing store'
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 201,
            'message' => $message ?? 'testing store',
        ];

        return handle_response_error($data, $modelA);
    }

    public function edit(?array $request = [])
    {
        // Set model Data
        $modelData = new AuthData($this->conn);

        $modelA = [
            'request' => $request,
            'table' => $modelData::getTableName(),
            'data' => $modelData->getAllData($request['id']) ?: null,
            'title' => $request['title'] ?? 'Edit model'
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing edit',
        ];

        return handle_response_error($data, $modelA);
    }

    public function update(?array $request = [])
    {
        // Set model Data
        $modelData = new AuthData($this->conn);

        $modelA = [
            'request' => $request ?? [],
            'table' => $modelData::getTableName(),
            'title' => $request['title'] ?? 'Testing update'
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 201,
            'message' => $message ?? 'testing update',
        ];

        return handle_response_error($data, $modelA);
    }

    public function destroy(?array $request = [])
    {
        // Set model Data
        $modelData = new AuthData($this->conn);

        $modelA = [
            'request' => $request ?? [],
            'table' => $modelData::getTableName(),
            'title' => $request['title'] ?? 'Destroy model'
        ];

        $data = [
            'data' => $modelA,
            'status' => $status ?? 200,
            'message' => $message ?? 'testing destroy',
        ];

        return handle_response_error($data, $modelA);
    }
}
