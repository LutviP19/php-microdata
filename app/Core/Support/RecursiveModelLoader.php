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
        $findListedModels = $this->findListedModels($cleanName);
        // dd($findListedModels);
        if ($findListedModels) {                 
            $cleanName = $findListedModels;
            $modelName = $findListedModels . "Model";
        } else {
            return null;
            // $modelName = $cleanName . "Model";
        }
        
        $modelPath = $this->basePath . "/Models/" . $modelName . ".php";
        // dd($modelName);
        // dd($modelPath);

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
            'page'       => strtolower($cleanName),
            'modelName'  => $modelName,
            'modelPath'  => $modelPath,
            'structName' => $structName,
            'structPath' => $this->basePath . "/Structs/{$parentFolder}/{$structName}.php",
            'dataName'   => $dataName,
            'dataPath'   => $this->basePath . "/Data/{$parentFolder}/{$dataName}.php",
            'model'     => $parentFolder
        ];
    }

    // Handle 404
    public function notFoundHandler($model, $modelPath, $jsonOnly = false) {
        if(is_json_request()) {
            $message = "Model '$model' Not Found";
            $errors = [
                'model' => 'Model not found at: ' . str_replace(realpath(config('app.path')), '', $modelPath),
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
     * Logika untuk mencari folder induk dari config modules (Dashboard, Stats, dsb)
     */
    private function findListedModels($page) {
        if(!isset($this->moduleConfig[$page]))
            return null;

        $models = \explode("|", \str_replace(['/(',')/i'], '', $this->moduleConfig[$page]));
        // dd($page);
        // dd($models);

        foreach ($models as $model) {
            $modelPath = $this->basePath . "/Models/" . $model . "Model.php";
            if (file_exists($modelPath)) {
                return $model;
            }
        }
        
        // Jika tidak ada di config, gunakan empty string
        return null;
    }

    /**
     * Logika untuk menentukan folder induk (Dashboard, Stats, dsb)
     */
    private function determineParentFolder($name) {
        foreach ($this->moduleConfig as $folder => $pattern) {
            if (preg_match($pattern, $name)) {
                $modelPath = $this->basePath . "/Models/" . $folder;
                if (is_dir($modelPath))
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