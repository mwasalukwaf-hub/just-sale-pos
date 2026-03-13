<?php
session_start();
require_once 'db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, password_hash, role, fullname, mobile, email, photo, short_details FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['photo'] = $user['photo'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['mobile'] = $user['mobile'];
        $_SESSION['short_details'] = $user['short_details'];
        
        echo json_encode([
            'success' => true, 
            'role' => $user['role'],
            'message' => 'Login successful'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verify_password') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    } elseif ($action === 'me') {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'fullname' => $_SESSION['fullname'],
                    'role' => $_SESSION['role'],
                    'photo' => $_SESSION['photo'],
                    'email' => $_SESSION['email'],
                    'mobile' => $_SESSION['mobile'],
                    'short_details' => $_SESSION['short_details']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        // Handled at top level usually, but let's move it here for consistency if needed
        // For now, I'll just move the rest here
    }
    
    if ($action === 'update_me') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $id = $_SESSION['user_id'];
        $fullname = $_POST['fullname'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $email = $_POST['email'] ?? '';
        $short_details = $_POST['short_details'] ?? '';
        $password = $_POST['password'] ?? '';

        // Handle Photo upload
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('profile_') . '.' . $ext;
            if (!is_dir('../assets/uploads/users')) {
                mkdir('../assets/uploads/users', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo']['tmp_name'], '../assets/uploads/users/' . $filename)) {
                $photo_path = 'assets/uploads/users/' . $filename;
            }
        }

        $sql = "UPDATE users SET fullname = ?, mobile = ?, email = ?, short_details = ?";
        $params = [$fullname, $mobile, $email, $short_details];

        if ($photo_path) {
            $sql .= ", photo = ?";
            $params[] = $photo_path;
            $_SESSION['photo'] = $photo_path; // Update session
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
            
            // Refresh session data
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['mobile'] = $mobile;
            $_SESSION['short_details'] = $short_details;

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    } elseif ($action === 'forgot_password') {
        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a random temporary password
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $tempPass = substr(str_shuffle($chars), 0, 10);
            $hashedPass = password_hash($tempPass, PASSWORD_DEFAULT);

            // Update user password
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->execute([$hashedPass, $user['id']]);

            // Fetch SMTP settings
            $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key = 'company_name'");
            $s = [];
            while ($row = $stmtSet->fetch()) {
                $s[$row['setting_key']] = $row['setting_value'];
            }

            $host = $s['smtp_host'] ?? '';
            $port = $s['smtp_port'] ?? '';
            $smtpUser = $s['smtp_user'] ?? '';
            $smtpPass = $s['smtp_pass'] ?? '';
            $encryption = $s['smtp_encryption'] ?? 'tls';
            $fromEmail = $s['smtp_from_email'] ?? ($s['company_email'] ?? '');
            $fromName = $s['smtp_from_name'] ?? ($s['company_name'] ?? 'JUSTSALE POS');

            if (empty($host) || empty($port) || empty($fromEmail)) {
                echo json_encode(['success' => false, 'message' => 'SMTP settings not configured. Please contact administrator.']);
                exit;
            }

            // Send Email
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->SMTPAuth   = !empty($smtpUser);
                if (!empty($smtpUser)) {
                    $mail->Username   = $smtpUser;
                    $mail->Password   = $smtpPass;
                }
                if ($encryption === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($encryption === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->Port       = $port;

                // SSL verification fix
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Recovery - JUSTSALE POS';
                $mail->Body    = "
                    <h3>Password Recovery Request</h3>
                    <p>Hello <b>{$user['username']}</b>,</p>
                    <p>You requested a password reset for your JUSTSALE POS account.</p>
                    <div style='padding: 20px; background: #f4f4f4; border-radius: 5px; border: 1px solid #ddd; margin: 15px 0;'>
                        Your temporary password is: <b style='font-size: 1.2rem; color: #4361ee;'>{$tempPass}</b>
                    </div>
                    <p>Please log in with this password and change it immediately from your profile page.</p>
                    <p>If you didn't request this, please contact your administrator.</p>
                    <br>
                    <p>Regards,<br><b>{$fromName}</b></p>
                ";
                $mail->AltBody = "Hello {$user['username']}, Your temporary password is: {$tempPass}. Please log in and change it immediately.";

                $mail->send();
                echo json_encode(['success' => true, 'message' => 'A temporary password has been sent to your email. Check your inbox (or spam folder).']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to send email. Error: ' . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
        }
    } else {
        // Logic for login/verify_password already at top but could be here
        // If it falls through here, it might be an unknown POST action if not already handled
        if (!in_array($action, ['login', 'verify_password'])) {
            echo json_encode(['success' => false, 'message' => 'Unknown POST action']);
        }
    }
}
?>
