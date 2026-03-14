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

    } elseif ($action === 'get_license') {
        $id = $_GET['id'] ?? null;
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);

    } elseif ($action === 'get_user') {
        $id = $_GET['id'] ?? null;
        $stmt = $pdo->prepare("SELECT id, fullname, email FROM portal_users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);

    } elseif ($action === 'edit_license') {
        $id = $_POST['id'] ?? null;
        $customer = $_POST['customer'] ?? '';
        $limit = (int)($_POST['limit'] ?? 1);
        $expiry = $_POST['expiry'] ?: null;
        $userId = $_POST['user_id'] ?: null;

        $stmt = $pdo->prepare("UPDATE licenses SET customer_name = ?, max_activations = ?, expiry_date = ?, user_id = ? WHERE id = ?");
        $stmt->execute([$customer, $limit, $expiry, $userId, $id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'edit_user') {
        $id = $_POST['id'] ?? null;
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';

        $stmt = $pdo->prepare("UPDATE portal_users SET fullname = ?, email = ? WHERE id = ?");
        $stmt->execute([$fullname, $email, $id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'get_users_list') {
        $stmt = $pdo->query("SELECT id, fullname, email FROM portal_users ORDER BY fullname ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($action === 'get_licenses_all') {
        $stmt = $pdo->query("SELECT id, license_key, customer_name FROM licenses ORDER BY license_key ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($action === 'get_payment') {
        $id = $_GET['id'] ?? null;
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);

    } elseif ($action === 'edit_payment') {
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? 'Pending';
        $userId = $_POST['user_id'] ?: null;
        $licenseId = $_POST['license_id'] ?: null;

        $stmt = $pdo->prepare("UPDATE payments SET status = ?, user_id = ?, license_id = ? WHERE id = ?");
        $stmt->execute([$status, $userId, $licenseId, $id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_payment') {
        $id = $_POST['id'] ?? null;
        $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'add_version') {
        $version = $_POST['version_number'] ?? '';
        $date = $_POST['release_date'] ?? date('Y-m-d');
        $changelog = $_POST['changelog'] ?? '';
        $is_critical = isset($_POST['is_critical']) ? 1 : 0;
        $php_version = $_POST['min_php_version'] ?? '7.4';

        if (empty($version)) {
            echo json_encode(['success' => false, 'message' => 'Version number is required']);
            exit;
        }

        $download_url = '';

        // Handle File upload
        if (isset($_FILES['update_zip']) && $_FILES['update_zip']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../updates/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['update_zip']['name'], PATHINFO_EXTENSION));
            if ($fileExtension !== 'zip') {
                echo json_encode(['success' => false, 'message' => 'Only .zip files are allowed']);
                exit;
            }

            $fileName = 'v' . $version . '_' . time() . '.zip';
            $destPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['update_zip']['tmp_name'], $destPath)) {
                $download_url = 'https://justsalepos.franklin.co.tz/updates/' . $fileName;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ZIP package is required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO system_versions (version_number, release_date, download_url, changelog, is_critical, min_php_version) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$version, $date, $download_url, $changelog, $is_critical, $php_version]);

        echo json_encode(['success' => true, 'message' => 'Version v' . $version . ' pushed successfully!']);

    } elseif ($action === 'delete_version') {
        $id = $_POST['id'] ?? null;
        
        // Get the file path first to delete it from server
        $stmt = $pdo->prepare("SELECT download_url FROM system_versions WHERE id = ?");
        $stmt->execute([$id]);
        $url = $stmt->fetchColumn();

        if ($url) {
            $fileName = basename($url);
            $filePath = '../../updates/' . $fileName;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM system_versions WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
