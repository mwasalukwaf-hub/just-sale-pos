<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'licensing_client.php';

$action = $_GET['action'] ?? '';

if ($action === 'check') {
    $licenseData = getLicenseData();
    if (!$licenseData) {
        echo json_encode(['success' => false, 'message' => 'Active license required for updates.']);
        exit;
    }

    $serverUrl = "https://justsalepos.franklin.co.tz/api/license.php?action=check_update&current_version=" . SYSTEM_VERSION;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serverUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        echo json_encode(['success' => false, 'message' => 'Could not connect to update server.']);
        exit;
    }

    echo $response;

} elseif ($action === 'download') {
    // 1. Get update info
    $url = $_POST['url'] ?? '';
    $version = $_POST['version'] ?? '';

    if (empty($url) || empty($version)) {
        echo json_encode(['success' => false, 'message' => 'Missing download URL or version.']);
        exit;
    }

    // 2. Prepare directories
    $tempDir = __DIR__ . '/../temp_update';
    if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
    
    $zipFile = $tempDir . '/update_' . $version . '.zip';

    // 3. Download the file
    $ch = curl_init($url);
    $fp = fopen($zipFile, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 mins
    $result = curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Download complete.', 'zip_path' => $zipFile]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Download failed.']);
    }

} elseif ($action === 'apply') {
    $zipPath = $_POST['zip_path'] ?? '';
    if (!file_exists($zipPath)) {
        echo json_encode(['success' => false, 'message' => 'Update package not found.']);
        exit;
    }

    $zip = new ZipArchive;
    if ($zip->open($zipPath) === TRUE) {
        $extractPath = __DIR__ . '/../temp_update/extracted';
        if (!is_dir($extractPath)) mkdir($extractPath, 0777, true);
        
        $zip->extractTo($extractPath);
        $zip->close();

        // Recursively copy files
        if (!function_exists('recurse_copy')) {
            function recurse_copy($src, $dst, $extractRoot) {
                $dir = opendir($src);
                @mkdir($dst);
                while (false !== ($file = readdir($dir))) {
                    if (($file != '.') && ($file != '..')) {
                        if (is_dir($src . '/' . $file)) {
                            recurse_copy($src . '/' . $file, $dst . '/' . $file, $extractRoot);
                        } else {
                            $exclude = ['api/db.php', 'api/license.json', 'install.html', 'server_files/config.php'];
                            $relative_path = ltrim(str_replace($extractRoot, '', $src . '/' . $file), '/');
                            
                            if (in_array($relative_path, $exclude) && file_exists($dst . '/' . $file)) {
                                continue;
                            }
                            copy($src . '/' . $file, $dst . '/' . $file);
                        }
                    }
                }
                closedir($dir);
            }
        }

        $baseDir = realpath(__DIR__ . '/..');
        recurse_copy($extractPath, $baseDir, $extractPath);

        // Run migrations if exists
        $migrationFile = $extractPath . '/migrate.sql';
        if (file_exists($migrationFile)) {
            $sql = file_get_contents($migrationFile);
            if ($sql) {
                try {
                    $pdo->exec($sql);
                } catch (Exception $e) { }
            }
        }

        // Cleanup
        if (!function_exists('deleteUpdateDir')) {
            function deleteUpdateDir($dirPath) {
                if (!is_dir($dirPath)) return;
                $files = glob($dirPath . '/{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
                foreach ($files as $file) {
                    if (is_dir($file)) deleteUpdateDir($file);
                    else unlink($file);
                }
                rmdir($dirPath);
            }
        }
        deleteUpdateDir(__DIR__ . '/../temp_update');
        
        echo json_encode(['success' => true, 'message' => 'Update applied successfully! System is now at the latest version.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to open update package.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
