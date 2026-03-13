<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'current') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM shifts WHERE user_id = ? AND status = 'Open' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $shift = $stmt->fetch();
            if ($shift) {
                // Calculate expected cash in drawer
                $cashStmt = $pdo->prepare("SELECT SUM(total_amount) as cash_total FROM sales WHERE shift_id = ? AND payment_method = 'CASH'");
                $cashStmt->execute([$shift['id']]);
                $cashSales = $cashStmt->fetch()['cash_total'] ?? 0;
                
                $shift['expected_cash'] = $shift['opening_balance'] + $cashSales;
                
                echo json_encode(['success' => true, 'shift' => $shift]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No active shift']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'open') {
        $opening_balance = $_POST['opening_balance'] ?? 0;
        $terminal_name = $_POST['terminal_name'] ?? 'Main Terminal';
        
        try {
            // Automatically close any existing open shifts for this user before opening a new one
            $closeOldSmt = $pdo->prepare("UPDATE shifts SET status = 'Closed', closing_time = CURRENT_TIMESTAMP WHERE user_id = ? AND status = 'Open'");
            $closeOldSmt->execute([$user_id]);

            $stmt = $pdo->prepare("INSERT INTO shifts (user_id, terminal_name, opening_balance, status) VALUES (?, ?, ?, 'Open')");
            if ($stmt->execute([$user_id, $terminal_name, $opening_balance])) {
                echo json_encode(['success' => true, 'message' => 'Shift opened', 'shift_id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error opening shift']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'close') {
        $closing_balance = $_POST['closing_balance'] ?? 0;
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM shifts WHERE user_id = ? AND status = 'Open' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $shift = $stmt->fetch();
            
            if (!$shift) {
                echo json_encode(['success' => false, 'message' => 'No active shift to close']);
                exit;
            }
            
            $updateStmt = $pdo->prepare("UPDATE shifts SET closing_time = CURRENT_TIMESTAMP, closing_balance = ?, status = 'Closed' WHERE id = ?");
            if ($updateStmt->execute([$closing_balance, $shift['id']])) {
                echo json_encode(['success' => true, 'message' => 'Shift closed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error closing shift']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown POST action']);
    }
}
