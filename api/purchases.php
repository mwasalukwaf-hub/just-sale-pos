<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $sql = "SELECT p.*, s.name as supplier_name, u.username as creator
                FROM purchases p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                JOIN users u ON p.user_id = u.id
                ORDER BY p.purchase_date DESC";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($action === 'details') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name, s.contact_person, s.phone, s.email, s.address, s.tin, u.username as creator
                                FROM purchases p
                                LEFT JOIN suppliers s ON p.supplier_id = s.id
                                JOIN users u ON p.user_id = u.id
                                WHERE p.id = ?");
        $stmt->execute([$id]);
        $purchase = $stmt->fetch();

        if ($purchase) {
            $itemStmt = $pdo->prepare("SELECT pi.*, pr.name as product_name, pr.sku
                                        FROM purchase_items pi
                                        JOIN products pr ON pi.product_id = pr.id
                                        WHERE pi.purchase_id = ?");
            $itemStmt->execute([$id]);
            $purchase['items'] = $itemStmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $purchase]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Purchase not found']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'reverse') {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Check if already cancelled
            $stmt = $pdo->prepare("SELECT status FROM purchases WHERE id = ?");
            $stmt->execute([$id]);
            $p = $stmt->fetch();
            if (!$p || $p['status'] === 'Cancelled') {
                throw new Exception("Purchase record not found or already reversed.");
            }

            // 2. Get TOTAL received quantities for this PO and reverse stock
            $recStmt = $pdo->prepare("SELECT product_id, SUM(quantity_received) as total_received 
                                      FROM reception_items 
                                      WHERE reception_id IN (SELECT id FROM receptions WHERE purchase_id = ?)
                                      GROUP BY product_id");
            $recStmt->execute([$id]);
            $receptions = $recStmt->fetchAll();

            $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            foreach ($receptions as $rec) {
                if ($rec['total_received'] > 0) {
                    $updateStock->execute([$rec['total_received'], $rec['product_id']]);
                }
            }

            // 3. Update status to Cancelled
            $pdo->prepare("UPDATE purchases SET status = 'Cancelled' WHERE id = ?")->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Purchase order cancelled. Stock adjusted based on received quantities.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
