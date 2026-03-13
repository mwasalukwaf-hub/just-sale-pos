<?php
// api/activate.php
require_once 'licensing_client.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'check') {
    $isLicensed = LicensingClient::checkLocalLicense();
    echo json_encode(['success' => true, 'isLicensed' => $isLicensed]);

} elseif ($action === 'activate') {
    $data = json_decode(file_get_contents('php://input'), true);
    $key = $data['license_key'] ?? '';

    if (empty($key)) {
        echo json_encode(['success' => false, 'message' => 'License key is required.']);
        exit;
    }

    $result = LicensingClient::activate($key);
    echo json_encode($result);

} elseif ($action === 'get_hwid') {
    echo json_encode(['success' => true, 'hwid' => LicensingClient::getHWID()]);
}
