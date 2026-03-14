<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(['success' => true, 'data' => $settings]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_full') {
    // Get all settings and include base64 logo if exists
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    if (!empty($settings['company_logo'])) {
        $logo_path = '../' . $settings['company_logo'];
        if (file_exists($logo_path)) {
            $data = file_get_contents($logo_path);
            $type = pathinfo($logo_path, PATHINFO_EXTENSION);
            $settings['company_logo_base64'] = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    }
    
    echo json_encode(['success' => true, 'data' => $settings]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $updates = [
        'company_name' => $_POST['company_name'] ?? '',
        'company_tin' => $_POST['company_tin'] ?? '',
        'company_vrn' => $_POST['company_vrn'] ?? '',
        'company_address' => $_POST['company_address'] ?? '',
        'company_phone' => $_POST['company_phone'] ?? '',
        'company_city' => $_POST['company_city'] ?? '',
        'company_country' => $_POST['company_country'] ?? '',
        'company_email' => $_POST['company_email'] ?? '',
        'company_website' => $_POST['company_website'] ?? '',
        'company_currency_code' => $_POST['company_currency_code'] ?? 'USD',
        'company_currency_name' => $_POST['company_currency_name'] ?? 'US Dollar',
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => $_POST['smtp_port'] ?? '',
        'smtp_user' => $_POST['smtp_user'] ?? '',
        'smtp_pass' => $_POST['smtp_pass'] ?? '',
        'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
        'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
        'smtp_from_name' => $_POST['smtp_from_name'] ?? '',
        'sku_template' => $_POST['sku_template'] ?? 'PROD-{MMYYYY}-00000',
        'sku_next_number' => $_POST['sku_next_number'] ?? '1',
        'receipt_header' => $_POST['receipt_header'] ?? '',
        'receipt_footer' => $_POST['receipt_footer'] ?? '',
        'tax_percent' => $_POST['tax_percent'] ?? '0',
        'receipt_show_logo' => $_POST['receipt_show_logo'] ?? 'yes',
        'receipt_customer_pos' => $_POST['receipt_customer_pos'] ?? 'top',
        'receipt_show_tin' => $_POST['receipt_show_tin'] ?? 'yes',
    ];

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($updates as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        // Handle File upload for Logo
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileTmpPath = $_FILES['company_logo']['tmp_name'];
            $fileName = $_FILES['company_logo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = 'logo_' . time() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $stmt->execute(['company_logo', 'uploads/' . $newFileName]);
                }
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'test_smtp') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $host = $_POST['smtp_host'] ?? '';
    $port = $_POST['smtp_port'] ?? '';
    $user = $_POST['smtp_user'] ?? '';
    $pass = $_POST['smtp_pass'] ?? '';
    $encryption = $_POST['smtp_encryption'] ?? 'tls';
    $from_email = $_POST['smtp_from_email'] ?? '';
    $from_name = $_POST['smtp_from_name'] ?? 'JUSTSALE Tester';

    if (empty($host) || empty($port) || empty($from_email)) {
        echo json_encode(['success' => false, 'message' => 'Host, port, and From Email are required to test.']);
        exit;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = !empty($user);
        if (!empty($user)) {
            $mail->Username   = $user;
            $mail->Password   = $pass;
        }
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port       = $port;

        // SSL verification fix for many server environments
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($from_email); // Send test email to self

        $mail->isHTML(true);
        $mail->Subject = 'JUSTSALE - SMTP Connection Test';
        $mail->Body    = '<b>Success!</b><br>Your SMTP configuration is working correctly.';
        $mail->AltBody = 'Success! Your SMTP configuration is working correctly.';

        $mail->send();
        echo json_encode(['success' => true, 'message' => "Connection successful! A test email was sent to $from_email."]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Connection failed. Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
