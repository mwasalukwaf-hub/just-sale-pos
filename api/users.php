<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    if ($_SESSION['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }
    $stmt = $pdo->query("SELECT id, username, fullname, mobile, email, photo, short_details, role, created_at FROM users ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }
    
    if ($action === 'create') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Cashier';
        $fullname = $_POST['fullname'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $email = $_POST['email'] ?? '';
        $short_details = $_POST['short_details'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            exit;
        }

        $photo_path = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('user_') . '.' . $ext;
            if (!is_dir('../assets/uploads/users')) {
                mkdir('../assets/uploads/users', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo']['tmp_name'], '../assets/uploads/users/' . $filename)) {
                $photo_path = 'assets/uploads/users/' . $filename;
            }
        }
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, fullname, mobile, email, photo, short_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$username, $hash, $role, $fullname, $mobile, $email, $photo_path, $short_details]);
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Username may already exist or database error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? null;
        $username = $_POST['username'] ?? '';
        $role = $_POST['role'] ?? 'Cashier';
        $fullname = $_POST['fullname'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $email = $_POST['email'] ?? '';
        $short_details = $_POST['short_details'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }

        // Handle photo update
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('user_') . '.' . $ext;
            if (!is_dir('../assets/uploads/users')) {
                mkdir('../assets/uploads/users', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo']['tmp_name'], '../assets/uploads/users/' . $filename)) {
                $photo_path = 'assets/uploads/users/' . $filename;
            }
        }

        $sql = "UPDATE users SET username = ?, role = ?, fullname = ?, mobile = ?, email = ?, short_details = ?";
        $params = [$username, $role, $fullname, $mobile, $email, $short_details];

        if ($photo_path) {
            $sql .= ", photo = ?";
            $params[] = $photo_path;
        }

        if (!empty($password)) {
            $sql .= ", password_hash = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown POST action']);
    }
}
?>
