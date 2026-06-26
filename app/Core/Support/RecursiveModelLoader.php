<?php
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Support;

class RecursiveModelLoader
{
    protected $basePath;
    protected $moduleConfig;
    protected $dataMapping;

    public function __construct(private readonly ?array $config = [])
    {
        $this->basePath = path_join(config("app.path"), "app");
        // Load konfigurasi modul
        $this->moduleConfig = $config["modules"] ?? [];
        $this->dataMapping = $config["data_mapping"] ?? [];
    }

    /**
     * Mencari komponen model, struct, dan data berdasarkan slug $page
     */
    public function resolve($page)
    {
        $modelName = $version = null;
        $baseModelPath = $this->basePath . "/Models/";

        // // Fix docker path
        // $baseModelPath = str_starts_with($baseModelPath, '/app') ? substr($baseModelPath, 4) : $baseModelPath;
        // // dd($baseModelPath);

        // Jika input adalah "v1-dashboard"
        if (str_contains((string) $page, "-")) {
            $parts = explode("-", (string) $page, 2); // ['v1', 'dashboard']
            $version = strtolower($parts[0]); // v1
            $module = $parts[1]; // dashboard

            // Cek validasi format versi v[angka]
            if (!preg_match('/^v[0-9]+$/i', $version)) {
                return null; // Format salah
            }

            // Tentukan folder berdasarkan versi (misal: app/Models/V1/DashboardModel.php)
            $subFolder = $version; // v1
            $cleanName = str_replace(" ", "", ucwords(str_replace("-", " ", $module)));

            $baseModelPath = $baseModelPath . "{$subFolder}/";
        } else {
            // Normalisasi slug (e.g., 'dashboard-stats' -> 'DashboardStats')
            $cleanName = str_replace(" ", "", ucwords(str_replace(["-", "_"], " ", $page)));
        }
        // dd($baseModelPath); //debug: $page | $version | $baseModelPath | $cleanName

        // 1. Identifikasi Model (Models biasanya di root folder Models)
        $findListedModels = $this->findListedModels($page, $version, $baseModelPath);
        // dd($findListedModels);
        if ($findListedModels) {
            $cleanName = $findListedModels;
            $modelName = $findListedModels . "Model";
        } else {
            // Jika file tidak ada langsung return null
            if (!\file_exists($baseModelPath . $cleanName . "Model.php")) {
                return null;
            }

            $modelName = $cleanName . "Model";
        }

        $modelPath = $baseModelPath . $modelName . ".php";
        // dd($modelName); //debug: $modelName | $modelPath

        // Jika model tidak ditemukan di root, kita asumsikan $page mungkin mengandung folder
        // Namun sesuai struktur Anda, DashboardModel.php ada di root.
        if (!file_exists($modelPath)) {
            return null;
        }

        // 2. Identifikasi Parent Folder (e.g., Dashboard)
        // Kita ambil dari kata pertama sebelum camelCase atau mapping manual
        // Untuk case Anda: StatistcStruct berada di folder 'Dashboard'
        $parentFolder = $this->determineParentFolder($cleanName, $baseModelPath, $version);
        $parentFolder = $parentFolder . ($version ? "/" . $version : "/");
        // dd($parentFolder);

        // 3. Tentukan Nama Struct dan Data
        $structName = $cleanName . "Struct";
        $dataName = $this->getDataName($cleanName); // e.g., Stats -> StatsData

        // Fix Docker - baseModelPath
        if (!is_json_request() && str_contains($baseModelPath, '/app/app')) {
            $baseModelPath = str_replace('/app/app', '/app', $baseModelPath);
        }
        // dd($baseModelPath);

        // Fix Docker - modelPath
        if (!is_json_request() && str_contains($modelPath, '/app/app')) {
            $modelPath = str_replace('/app/app', '/app', $modelPath);
        }
        // dd($modelPath);

        $baseDataPath = str_replace("Models/" . $version, "Data/" . $parentFolder, $baseModelPath);
        $baseStructsPath = str_replace("Models/" . $version, "Structs/" . $parentFolder, $baseModelPath);
        // dd($baseStructsPath);

        return [
            "page" => strtolower((string) $cleanName),
            "modelName" => $modelName,
            "modelPath" => $modelPath,
            "structName" => $structName,
            "structPath" => $baseStructsPath . "{$structName}.php",
            "dataName" => $dataName,
            "dataPath" => $baseDataPath . "{$dataName}.php",
            "model" => str_replace("-", "/", $page),
        ];
    }

    // Handle 404
    public function notFoundHandler($model, $modelPath, $jsonOnly = false)
    {
        if (is_json_request()) {
            $message = "Model '$model' Not Found";
            $errors = [
                "model" => "Model not found at: " . str_replace(realpath(config("app.path")), "", $modelPath),
            ];
            json_response([], 404, $message, $errors);
        } else {
            if (!$jsonOnly) {
                $isPageExists = false;
                http_response_code(isHtmx() ? 200 : 404);
                include config("app.path") . "/views/error/404.php";
                die();
            }
        }
    }

    /**
     * Logika untuk mencari folder induk dari config modules (Dashboard, Stats, dsb)
     */
    private function findListedModels($page, $version, $baseModelPath)
    {
        $cleanName = formatRoutePath($page, true); // reserve "v1-dashboard" menjadi "Dashboard/v1"
        // dd($cleanName);

        if (!isset($this->moduleConfig[$cleanName])) {
            return null;
        }

        $models = \explode("|", \str_replace(["/(", ")/i"], "", $this->moduleConfig[$cleanName]));
        // dd($baseModelPath); //debug: $baseModelPath | $models

        // Scan modelPath
        $baseDataPath = str_replace("Models/" . $version, "Data/" . $cleanName, $baseModelPath);
        // $baseStructsPath = str_replace('Models/'.$version, 'Structs/' . $cleanName, $baseModelPath); // Scan Structs jika diperlukan
        // dd($baseDataPath);
        foreach ($models as $model) {
            $dataPath = $baseDataPath . $model . "Data.php";
            // dd($dataPath);
            // $structPath = $baseStructsPath . $model . "Struct.php";
            // if (file_exists($dataPath) && file_exists($structPath)) {
            if (file_exists($dataPath)) {
                return $model;
            }
        }

        // Jika tidak ada di config, gunakan empty string
        return null;
    }

    /**
     * Logika untuk menentukan folder induk (Dashboard, Stats, dsb)
     */
    private function determineParentFolder($name, $baseModelPath, $version = null)
    {
        foreach ($this->moduleConfig as $folder => $pattern) {
            if ($version && isset($this->moduleConfig[$version][$name])) {
                $pattern = $this->moduleConfig[$version][$name];
            }

            if (is_string($pattern) && preg_match($pattern, (string) $name)) {
                $modelPath = $baseModelPath . $folder;
                if (is_dir($modelPath)) {
                    return $folder;
                }
            }
        }

        // Jika tidak ada di config, gunakan namanya sendiri sebagai folder
        return $name;
    }

    /**
     * Mendapatkan nama file Data secara dinamis dari config mapping
     */
    private function getDataName($name)
    {
        // Default: NamaModel + Data
        return $this->dataMapping[$name] ?? $name . "Data";
    }
}