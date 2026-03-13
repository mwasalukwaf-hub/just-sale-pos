<?php
// server_files/admin/api_admin.php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_auth'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'toggle_bypass') {
        $val = $_POST['value'] ?? 'enabled';
        $stmt = $pdo->prepare("UPDATE portal_settings SET setting_value = ? WHERE setting_key = 'global_license_check'");
        $stmt->execute([$val]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'create_license') {
        $key = $_POST['key'] ?? '';
        $customer = $_POST['customer'] ?? 'Manual Activation';
        $limit = (int)($_POST['limit'] ?? 1);

        if (empty($key)) {
            $key = "JS-ADM-" . strtoupper(substr(md5(time() . rand()), 0, 8));
        }

        $stmt = $pdo->prepare("INSERT INTO licenses (license_key, customer_name, status, max_activations) VALUES (?, ?, 'Active', ?)");
        $stmt->execute([$key, $customer, $limit]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'update_status') {
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? 'Active';
        
        $stmt = $pdo->prepare("UPDATE licenses SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_license') {
        $id = $_POST['id'] ?? null;
        $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_user') {
        $id = $_POST['id'] ?? null;
        $stmt = $pdo->prepare("DELETE FROM portal_users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
