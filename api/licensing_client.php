<?php
// api/licensing_client.php
// Centralized helper for managing license status on the client application

class LicensingClient {
    private static $server_url = "https://justsalepos.franklin.co.tz/api/license.php";
    private static $config_file = __DIR__ . '/../.license.lock';

    /**
     * Generates a unique Hardware ID (HWID) for this environment.
     * Combines hostname and system-specific markers.
     */
    public static function getHWID() {
        $hostname = gethostname();
        $os = PHP_OS;
        
        // On Windows, we can use SERIAL NUMBER of disk or Mac Address
        // For simplicity and portability across local/cloud, we use a salted hash of environment unique nodes
        $data = $hostname . $os . php_uname('m');
        
        // If we are on windows, attempt to get a more unique serial if possible
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $serial = shell_exec('wmic bios get serialnumber');
            if ($serial) $data .= trim($serial);
        }

        return hash('sha256', $data . 'JUSTSALE_SALT');
    }

    /**
     * Checks if the application is currently licensed locally.
     */
    public static function checkLocalLicense() {
        if (!file_exists(self::$config_file)) return false;

        $data = json_decode(file_get_contents(self::$config_file), true);
        if (!$data || !isset($data['license_key'])) return false;

        // Heartbeat check every 30 days (2592000 seconds)
        if (isset($data['last_check']) && (time() - $data['last_check'] > 2592000)) {
            // Attempt to verify with server. If it fails due to NO INTERNET, we still allow access 
            // unless it's been even longer (e.g., a grace period).
            $verified = self::verifyWithServer($data['license_key']);
            if (!$verified) {
                // If server is unreachable, we check if we should still allow (Grace period 35 days total)
                if (time() - $data['last_check'] > 3024000) {
                    return false; // Hard block after 35 days
                }
            }
            return true;
        }

        return true;
    }

    /**
     * Attempts to activate the software with a key.
     */
    public static function activate($license_key) {
        $hwid = self::getHWID();
        $hostname = gethostname();

        $payload = [
            'license_key' => $license_key,
            'hwid' => $hwid,
            'hostname' => $hostname
        ];

        $ch = curl_init(self::$server_url . "?action=activate");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => "Connection Error: " . $error];
        }

        $result = json_decode($response, true);
        if ($result && isset($result['success']) && $result['success']) {
            // Save local lock file
            file_put_contents(self::$config_file, json_encode([
                'license_key' => $license_key,
                'activated_at' => time(),
                'last_check' => time(),
                'hwid' => $hwid
            ]));
            return ['success' => true, 'message' => $result['message']];
        }

        return ['success' => false, 'message' => $result['message'] ?? "Server returned error (Code: $http_code). Please ensure you have uploaded the server files."];
    }

    /**
     * Manual verification with the remote server.
     */
    public static function verifyWithServer($license_key) {
        $hwid = self::getHWID();
        $payload = ['license_key' => $license_key, 'hwid' => $hwid];

        $ch = curl_init(self::$server_url . "?action=verify");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Short timeout for verification
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($result && $result['success']) {
            // Update cache time
            if (file_exists(self::$config_file)) {
                $data = json_decode(file_get_contents(self::$config_file), true);
                $data['last_check'] = time();
                file_put_contents(self::$config_file, json_encode($data));
            }
            return true;
        }

        // If verification fails, delete the lock file
        if (file_exists(self::$config_file)) unlink(self::$config_file);
        return false;
    }
}
