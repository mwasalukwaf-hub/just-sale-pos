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
    if ($action === 'receive_goods') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $supplier_id = $data['supplier_id'] ?? null;
        $total_amount = $data['total_amount'] ?? 0;
        
        try {
            $pdo->beginTransaction();
            // Status defaults to 'Pending' in DB
            $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, total_amount, user_id, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$supplier_id, $total_amount, $_SESSION['user_id']]);
            $purchase_id = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, cost_price, subtotal) VALUES (?, ?, ?, ?, ?)");

            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['cost_price'];
                $itemStmt->execute([$purchase_id, $item['product_id'], $item['quantity'], $item['cost_price'], $subtotal]);
                // NO stock update here anymore. Stock updates happen on Reception.
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Purchase request created successfully. Stock will be updated upon reception.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'adjust_stock') {
        $product_id = $_POST['product_id'] ?? null;
        $type = $_POST['adjustment_type'] ?? '';
        $qty = $_POST['quantity'] ?? 0;
        $reason = $_POST['reason'] ?? '';

        if (!$product_id || !$type || !$qty) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO stock_adjustments (product_id, adjustment_type, quantity, reason, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $type, $qty, $reason, $_SESSION['user_id']]);

            if ($type === 'In') {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$qty, $product_id]);
            } elseif ($type === 'Out' || $type === 'Damage' || $type === 'Loss') {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$qty, $product_id]);
            } elseif ($type === 'Set Stock') {
                $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?")->execute([$qty, $product_id]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Stock adjusted successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'movements') {
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';

        $sql = "SELECT * FROM (
            (SELECT r.received_date as date, 'Reception' as type, CONCAT('GRN-', r.id, ' (PO-', p.id, ') - ', s.name) as identifier, ri.quantity_received as qty, pr.name as product_name, u.username
             FROM receptions r
             JOIN purchases p ON r.purchase_id = p.id
             JOIN suppliers s ON p.supplier_id = s.id
             JOIN reception_items ri ON r.id = ri.reception_id
             JOIN products pr ON ri.product_id = pr.id
             JOIN users u ON r.received_by = u.id)
            UNION ALL
            (SELECT r.received_date as date, 'Reversal' as type, CONCAT('REVERSED PO-', p.id, ' (GRN-', r.id, ') - ', s.name) as identifier, -ri.quantity_received as qty, pr.name as product_name, u.username
             FROM receptions r
             JOIN purchases p ON r.purchase_id = p.id
             JOIN suppliers s ON p.supplier_id = s.id
             JOIN reception_items ri ON r.id = ri.reception_id
             JOIN products pr ON ri.product_id = pr.id
             JOIN users u ON r.received_by = u.id
             WHERE p.status = 'Cancelled')
            UNION ALL
            (SELECT sale_date as date, 'Sale' as type, CONCAT('Receipt #', s.id) as identifier, -si.quantity as qty, pr.name as product_name, u.username
             FROM sales s
             JOIN sale_items si ON s.id = si.sale_id
             JOIN products pr ON si.product_id = pr.id
             JOIN users u ON s.user_id = u.id)
            UNION ALL
            (SELECT adjustment_date as date, adjustment_type as type, reason as identifier, 
                CASE 
                    WHEN adjustment_type IN ('Out', 'Damage', 'Loss') THEN -quantity 
                    ELSE quantity 
                END as qty, 
                pr.name as product_name, u.username
             FROM stock_adjustments sa
             JOIN products pr ON sa.product_id = pr.id
             JOIN users u ON sa.user_id = u.id)
        ) as movements WHERE 1=1";

        $params = [];
        if ($start_date) {
            $sql .= " AND DATE(date) >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $sql .= " AND DATE(date) <= ?";
            $params[] = $end_date;
        }

        $sql .= " ORDER BY date DESC LIMIT 1000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
