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
    if ($action === 'po_items') {
        $poId = $_GET['id'] ?? null;
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'PO ID required']);
            exit;
        }

        // Get PO items and current received qty
        $sql = "SELECT pi.*, pr.name as product_name, pr.sku,
                       COALESCE((SELECT SUM(ri.quantity_received) FROM reception_items ri WHERE ri.purchase_item_id = pi.id), 0) as total_received
                FROM purchase_items pi
                JOIN products pr ON pi.product_id = pr.id
                WHERE pi.purchase_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$poId]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'record') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || empty($data['items']) || empty($data['purchase_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $purchase_id = $data['purchase_id'];
        $notes = $data['notes'] ?? '';

        try {
            $pdo->beginTransaction();

            // 0. Check PO status
            $chk = $pdo->prepare("SELECT status FROM purchases WHERE id = ?");
            $chk->execute([$purchase_id]);
            $poStatus = $chk->fetch();
            if (!$poStatus || $poStatus['status'] === 'Cancelled') {
                throw new Exception("Cannot receive items for a cancelled or non-existent PO.");
            }

            // 1. Create Reception header
            $stmt = $pdo->prepare("INSERT INTO receptions (purchase_id, received_by, notes) VALUES (?, ?, ?)");
            $stmt->execute([$purchase_id, $_SESSION['user_id'], $notes]);
            $reception_id = $pdo->lastInsertId();

            $riStmt = $pdo->prepare("INSERT INTO reception_items (reception_id, purchase_item_id, product_id, quantity_received) VALUES (?, ?, ?, ?)");
            $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ?, cost_price = ? WHERE id = ?");

            foreach ($data['items'] as $item) {
                if ($item['received_now'] <= 0) continue;

                $riStmt->execute([$reception_id, $item['purchase_item_id'], $item['product_id'], $item['received_now']]);
                // Update product stock and cost (assuming the cost in PO is the current one)
                $updateStock->execute([$item['received_now'], $item['cost_price'], $item['product_id']]);
            }

            // 2. Check overall PO status
            // Count total requested vs total received
            $checkSql = "SELECT SUM(quantity) as total_req, 
                               (SELECT SUM(quantity_received) FROM reception_items ri JOIN purchase_items pi2 ON ri.purchase_item_id = pi2.id WHERE pi2.purchase_id = pi.purchase_id) as total_rec
                        FROM purchase_items pi
                        WHERE pi.purchase_id = ?
                        GROUP BY pi.purchase_id";
            $cStmt = $pdo->prepare($checkSql);
            $cStmt->execute([$purchase_id]);
            $statusData = $cStmt->fetch();

            $newStatus = 'Partial';
            if ($statusData['total_rec'] >= $statusData['total_req']) {
                $newStatus = 'Received';
            }

            $pdo->prepare("UPDATE purchases SET status = ? WHERE id = ?")->execute([$newStatus, $purchase_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Delivery recorded. Stock updated. Status: ' . $newStatus]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
