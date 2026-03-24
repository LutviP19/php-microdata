<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Support;


class RecursiveModelLoader {
    protected $basePath;
    protected $moduleConfig;

    public function __construct($config = []) {
        $this->basePath = realpath(config('app.path') . "/app");
        // Load konfigurasi modul        
        $this->moduleConfig = $config['modules'] ?? [];
        $this->dataMapping = $config['data_mapping'] ?? [];
    }

    /**
     * Mencari komponen model, struct, dan data berdasarkan slug $page
     */
    public function resolve($page) {
        // Normalisasi slug (e.g., 'dashboard-stats' -> 'DashboardStats')
        $cleanName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $page)));
        
        // 1. Identifikasi Model (Models biasanya di root folder Models)
        $modelName = $cleanName . "Model";
        $modelPath = $this->basePath . "/Models/" . $modelName . ".php";

        // Jika model tidak ditemukan di root, kita asumsikan $page mungkin mengandung folder
        // Namun sesuai struktur Anda, DashboardModel.php ada di root.
        if (!file_exists($modelPath)) {
            return null; 
        }

        // 2. Identifikasi Parent Folder (e.g., Dashboard) 
        // Kita ambil dari kata pertama sebelum camelCase atau mapping manual
        // Untuk case Anda: StatistcStruct berada di folder 'Dashboard'
        $parentFolder = $this->determineParentFolder($cleanName);

        // 3. Tentukan Nama Struct dan Data
        $structName = $cleanName . "Struct";
        $dataName   = $this->getDataName($cleanName); // e.g., Stats -> StatsData

        return [
            'page'       => $page,
            'modelName'  => $modelName,
            'modelPath'  => $modelPath,
            'structName' => $structName,
            'structPath' => $this->basePath . "/Structs/{$parentFolder}/{$structName}.php",
            'dataName'   => $dataName,
            'dataPath'   => $this->basePath . "/Data/{$parentFolder}/{$dataName}.php",
            'folder'     => $parentFolder
        ];
    }

    // Handle 404
    public function notFoundHandler($model, $modelPath, $jsonOnly = false) {
        if(is_json_request()) {
            $message = "Model '$model' Not Found";
            $errors = [
                'model' => 'Model not found at: ' . str_replace(config('app.path'), '', $modelPath)
            ];
            json_response([], 404, $message, $errors);
        } else {
            if(!$jsonOnly) {
                $isPageExists = false;
                http_response_code(404);
                include config('app.path') . "/views/error/404.php";
                exit();
            }
        }
    }

    /**
     * Logika untuk menentukan folder induk (Dashboard, dsb)
     */
    private function determineParentFolder($name) {
        foreach ($this->moduleConfig as $folder => $pattern) {
            if (preg_match($pattern, $name)) {
                return $folder;
            }
        }
        
        // Jika tidak ada di config, gunakan namanya sendiri sebagai folder
        return $name; 
    }

    /**
     * Mendapatkan nama file Data secara dinamis dari config mapping
     */
    private function getDataName($name) {
        // Cek apakah ada mapping khusus di config
        if (isset($this->dataMapping[$name])) {
            return $this->dataMapping[$name];
        }
        
        // Default: NamaModel + Data
        return $name . "Data";
    }
}