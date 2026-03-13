<?php
require_once 'api/db.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

echo "Testing SMTP Connection...\n";
echo "Host: " . $settings['smtp_host'] . "\n";
echo "Port: " . $settings['smtp_port'] . "\n";
echo "User: " . $settings['smtp_user'] . "\n";
echo "Encryption: " . $settings['smtp_encryption'] . "\n";
echo "From Email: " . $settings['smtp_from_email'] . "\n";

$mail = new PHPMailer(true);
try {
    //Enable SMTP debugging
    //SMTP::DEBUG_SERVER = 2
    $mail->SMTPDebug = 2; 
    $mail->Debugoutput = 'echo';

    $mail->isSMTP();
    $mail->Host       = $settings['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_user'];
    $mail->Password   = $settings['smtp_pass'];
    $mail->SMTPSecure = ($settings['smtp_encryption'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $settings['smtp_port'];

    // If using self-signed certs (e.g. localhost/mailpit)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_name']);
    $mail->addAddress($settings['smtp_from_email']); // send to self

    $mail->isHTML(false);
    $mail->Subject = 'SMTP Test';
    $mail->Body    = 'This is a test email.';

    $mail->send();
    echo "\nMessage has been sent successfully\n";
} catch (Exception $e) {
    echo "\nMessage could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
