<?php
declare(strict_types=1);


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';


if (!function_exists('cleanTmpFiles')) {
    function cleanTmpFiles($tmpDir, $daysOld = 3) {
        // Calculate the timestamp for the threshold
        $thresholdTimestamp = strtotime("-$daysOld days");

        // Check if the directory exists and is readable
        if (!is_dir($tmpDir) || !is_readable($tmpDir)) {
            echo "Error: Temporary directory '$tmpDir' does not exist or is not readable.\n";
            exit;
        }

        // Open the directory
        if ($handle = opendir($tmpDir)) {
            while (false !== ($file = readdir($handle))) {
                // Skip '.' and '..'
                if ($file != "." && $file != ".." && $file != ".gitignore") {
                    $filePath = $tmpDir . '/' . $file;

                    // Check if it's a file and get its modification time
                    if (is_file($filePath) && file_exists($filePath)) {
                        $fileModTime = filemtime($filePath);

                        // If the file's modification time is older than the threshold, delete it
                        if ($fileModTime < $thresholdTimestamp) {
                            if (unlink($filePath)) {
                                echo "Deleted old temporary file: $filePath\n";
                            } else {
                                echo "Failed to delete file: $filePath\n";
                            }
                        }
                    }
                }
            }
            closedir($handle);
        } else {
            echo "Error: Could not open temporary directory '$tmpDir'.\n";
        }
    }
}

// Clean tmp-rate_limits
// $tmpDir = BASEPATH . "/storage/framework/tmp/rate_limits";
$tmpDir = storage_path('/framework/tmp/rate_limits');
if(file_exists($tmpDir))
cleanTmpFiles($tmpDir, 1);

// Clean Session
$tmpDir = storage_path('/framework/sessions');
cleanTmpFiles($tmpDir, 0);

// Clean Cache
$tmpDir = storage_path('/framework/cache');
cleanTmpFiles($tmpDir, 0);