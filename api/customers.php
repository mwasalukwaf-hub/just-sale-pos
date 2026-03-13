<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $search = $_GET['search'] ?? '';
    try {
        if ($search) {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR mobile LIKE ? OR email LIKE ? LIMIT 20");
            $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC LIMIT 20");
        }
        $customers = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $customers]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $name = $_POST['name'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $email = $_POST['email'] ?? '';
    $tin = $_POST['tin'] ?? '';
    $vrn = $_POST['vrn'] ?? '';
    $address = $_POST['address'] ?? '';

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Customer name is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO customers (name, mobile, email, tin, vrn, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $mobile, $email, $tin, $vrn, $address]);
        echo json_encode(['success' => true, 'message' => 'Customer added successfully', 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
