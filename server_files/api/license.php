<?php
// Licensing API (Deploy at https://justsalepos.franklin.co.tz/api/license.php)
header('Content-Type: application/json');

require_once '../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'L-SERVER-ERR: DB Connection failed']);
    exit;
}

// 0. CHECK GLOBAL BYPASS
$stmt = $pdo->prepare("SELECT setting_value FROM portal_settings WHERE setting_key = 'global_license_check'");
$stmt->execute();
$globalCheck = $stmt->fetchColumn();

// If global check is disabled, EVERYTHING is always valid
if ($globalCheck === 'disabled') {
    echo json_encode(['success' => true, 'status' => 'valid', 'message' => 'L-MASTER-BYPASS: Active']);
    exit;
}

$action = $_GET['action'] ?? '';
$payload = json_decode(file_get_contents('php://input'), true);

if ($action === 'activate') {
    $key = $payload['license_key'] ?? '';
    $hwid = $payload['hwid'] ?? '';
    $hostname = $payload['hostname'] ?? '';

    if (empty($key) || empty($hwid)) {
        echo json_encode(['success' => false, 'message' => 'License key and HWID are required.']);
        exit;
    }

    // 1. Check if license exists and is active
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
    $stmt->execute([$key]);
    $license = $stmt->fetch();

    if (!$license) {
        echo json_encode(['success' => false, 'message' => 'Invalid license key.']);
        exit;
    }

    if ($license['status'] !== 'Active') {
        echo json_encode(['success' => false, 'message' => 'This license is ' . $license['status'] . '.']);
        exit;
    }

    if ($license['expiry_date'] && strtotime($license['expiry_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'License has expired.']);
        exit;
    }

    // 2. Check current activations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activations WHERE license_id = ?");
    $stmt->execute([$license['id']]);
    $currentCount = $stmt->fetchColumn();

    // Check if THIS HWID is already activated for this key
    $stmt = $pdo->prepare("SELECT * FROM activations WHERE license_id = ? AND hwid = ?");
    $stmt->execute([$license['id'], $hwid]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Re-activation on same machine is okay
        echo json_encode(['success' => true, 'message' => 'Re-activated successfully.', 'license_id' => $license['id']]);
        exit;
    }

    if ($currentCount >= $license['max_activations']) {
        echo json_encode(['success' => false, 'message' => 'Maximum activation limit reached.']);
        exit;
    }

    // 3. Register activation
    $stmt = $pdo->prepare("INSERT INTO activations (license_id, hwid, hostname, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$license['id'], $hwid, $hostname, $_SERVER['REMOTE_ADDR']]);

    echo json_encode(['success' => true, 'message' => 'License activated successfully.']);

} elseif ($action === 'verify') {
    $key = $payload['license_key'] ?? '';
    $hwid = $payload['hwid'] ?? '';

    if (empty($key) || empty($hwid)) {
        echo json_encode(['success' => false, 'message' => 'Key and HWID missing.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT l.* FROM licenses l 
                           JOIN activations a ON l.id = a.license_id 
                           WHERE l.license_key = ? AND a.hwid = ?");
    $stmt->execute([$key, $hwid]);
    $license = $stmt->fetch();

    if (!$license) {
        echo json_encode(['success' => false, 'message' => 'License verification failed.']);
        exit;
    }

    if ($license['status'] !== 'Active') {
        echo json_encode(['success' => false, 'message' => 'License status: ' . $license['status']]);
        exit;
    }

    // Update check-in time
    $stmt = $pdo->prepare("UPDATE activations SET last_check_in = CURRENT_TIMESTAMP WHERE hwid = ?");
    $stmt->execute([$hwid]);

    echo json_encode(['success' => true, 'status' => 'valid']);

} elseif ($action === 'check_update') {
    $currentVersion = $_GET['current_version'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM system_versions ORDER BY v_entry DESC LIMIT 1");
    $stmt->execute();

    if ($latest) {
        $updateAvailable = version_compare($latest['version_number'], $currentVersion, '>');
        echo json_encode([
            'success' => true,
            'update_available' => $updateAvailable,
            'latest_version' => $latest['version_number'],
            'download_url' => $latest['download_url'],
            'changelog' => $latest['changelog'],
            'is_critical' => (bool)$latest['is_critical']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No version information found.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
