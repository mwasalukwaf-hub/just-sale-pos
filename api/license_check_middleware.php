<?php
// api/license_check_middleware.php
// Backend gatekeeper to prevent API access if software is not licensed

require_once 'licensing_client.php';

// Allow certain actions to pass through (like activation itself)
$current_file = basename($_SERVER['PHP_SELF']);
$allowed_files = ['activate.php', 'auth.php', 'installer.php']; 

if (!in_array($current_file, $allowed_files)) {
    if (!LicensingClient::checkLocalLicense()) {
        header('Content-Type: application/json');
        http_response_code(402); // Payment Required / Licensing Required
        echo json_encode(['success' => false, 'message' => 'L-CHECK: System license missing or expired.', 'license_required' => true]);
        exit;
    }
}
