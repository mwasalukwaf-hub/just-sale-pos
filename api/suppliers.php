<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $tin = $_POST['tin'] ?? '';

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Supplier name required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, tin) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$name, $contact, $email, $phone, $address, $tin]);
            echo json_encode(['success' => true, 'message' => 'Supplier created successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $tin = $_POST['tin'] ?? '';

        if (!$id || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=?, tin=? WHERE id=?");
        try {
            $stmt->execute([$name, $contact, $email, $phone, $address, $tin, $id]);
            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Supplier deleted']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($action === 'details') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id=?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch();
        
        if ($supplier) {
            $stmt = $pdo->prepare("SELECT * FROM purchases WHERE supplier_id=? ORDER BY purchase_date DESC");
            $stmt->execute([$id]);
            $supplier['purchases'] = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $supplier]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        }
    }
}
