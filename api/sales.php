<?php
session_start();
require_once 'db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'checkout') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || empty($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Empty cart']);
        exit;
    }

    $shift_id = $data['shift_id'] ?? null;
    $payment_method = $data['payment_method'] ?? 'Cash';
    $amount_paid = $data['amount_paid'] ?? 0;
    $customer_id = $data['customer_id'] ?? null;
    
    if (!$shift_id) {
        echo json_encode(['success' => false, 'message' => 'No active shift provided']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Calculate Total
        $total_amount = 0;
        foreach ($data['items'] as $item) {
            $total_amount += $item['quantity'] * $item['price'];
        }
        $change_amount = max(0, $amount_paid - $total_amount);
        
        $stmt = $pdo->prepare("INSERT INTO sales (shift_id, user_id, customer_id, total_amount, payment_method, amount_paid, change_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$shift_id, $_SESSION['user_id'], $customer_id, $total_amount, $payment_method, $amount_paid, $change_amount]);
        $sale_id = $pdo->lastInsertId();
        
        $itemStmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        
        foreach ($data['items'] as $item) {
            $subtotal = $item['quantity'] * $item['price'];
            $itemStmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $subtotal]);
            $stockStmt->execute([$item['quantity'], $item['id']]);
        }
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Sale completed', 
            'sale_id' => $sale_id,
            'change' => $change_amount
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error processing sale: ' . $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'receipt') {
    $sale_id = $_GET['id'] ?? null;
    if (!$sale_id) {
        echo json_encode(['success' => false, 'message' => 'Sale ID required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT s.*, u.username, c.name as customer_name FROM sales s 
                           JOIN users u ON s.user_id = u.id 
                           LEFT JOIN customers c ON s.customer_id = c.id 
                           WHERE s.id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if($sale) {
        $itemsStmt = $pdo->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
        $itemsStmt->execute([$sale_id]);
        $items = $itemsStmt->fetchAll();
        $sale['items'] = $items;
        
        echo json_encode(['success' => true, 'data' => $sale]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_shift_sales') {
    $shift_id = $_GET['shift_id'] ?? null;
    if (!$shift_id) {
        echo json_encode(['success' => false, 'message' => 'Shift ID required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales s 
                           LEFT JOIN customers c ON s.customer_id = c.id 
                           WHERE s.shift_id = ? ORDER BY s.sale_date DESC");
    $stmt->execute([$shift_id]);
    $sales = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $sales]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'email_receipt') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    
    $sale_id = $data['sale_id'] ?? null;
    $email = $data['email'] ?? null;

    if (!$sale_id || !$email) {
        echo json_encode(['success' => false, 'message' => 'Sale ID and Email are required']);
        exit;
    }
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        if (empty($settings['smtp_host'])) {
            throw new Exception("SMTP settings are not configured. Please configure them in the Admin Dashboard.");
        }

        // Trick print_receipt into giving us the PDF back instead of outputting it
        $_GET['id'] = $sale_id;
        $return_content = true;
        
        // Suppress session notices and echoes
        ob_start();
        require 'print_receipt.php'; 
        // Note: print_receipt.php sets $pdf_output
        $pdf_content = $pdf_output ?? null;
        ob_end_clean();

        if (!$pdf_content) {
            throw new Exception("Failed to statically generate PDF for email attachment.");
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_user'];
        $mail->Password   = $settings['smtp_pass'];
        $mail->SMTPSecure = ($settings['smtp_encryption'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $settings['smtp_port'];

        $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_name']);
        $mail->addAddress($email);

        $mail->addStringAttachment($pdf_content, "Receipt_#{$sale_id}.pdf", 'base64', 'application/pdf');

        $mail->isHTML(true);
        $mail->Subject = 'Your Receipt from ' . ($settings['company_name'] ?? 'JUSTSALE');
        
        $bodyHtml = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Thank you for your business!</h2>
            <p>Dear Customer,</p>
            <p>Your transaction has been securely processed. Please find your official receipt attached to this email as a PDF document.</p>
            <br>
            <p>Best Regards,<br><strong>" . htmlspecialchars($settings['company_name'] ?? 'JUSTSALE') . "</strong></p>
        </div>";

        $mail->Body = $bodyHtml;
        $mail->AltBody = "Thank you for your business. Please find your official receipt attached to this email.";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Receipt securely emailed to ' . htmlspecialchars($email)]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Delivery Failed: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
