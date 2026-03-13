<?php
// Central API: Payment Callback Verification
require_once '../config.php';

if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $tx_ref = $_GET['tx_ref'];
    $transaction_id = $_GET['transaction_id'];

    if ($status === 'successful') {
        // 1. Verify with Flutterwave API
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . FLW_SECRET_KEY,
                "Content-Type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        
        $res = json_decode($response, true);

        if ($res && $res['status'] === 'success' && $res['data']['status'] === 'successful') {
            $amountPaid = $res['data']['amount'];
            $currency = $res['data']['currency'];

            try {
                $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Check if already processed
                $stmt = $pdo->prepare("SELECT * FROM payments WHERE tx_ref = ?");
                $stmt->execute([$tx_ref]);
                $payment = $stmt->fetch();

                if ($payment && $payment['status'] === 'Pending') {
                    $pdo->beginTransaction();

                    // Update payment record
                    $stmt = $pdo->prepare("UPDATE payments SET status = 'Successful', transaction_id = ? WHERE tx_ref = ?");
                    $stmt->execute([$transaction_id, $tx_ref]);

                    // Generate License Key
                    $licenseKey = "JS-" . strtoupper(substr(md5(time() . rand()), 0, 4)) . "-" . strtoupper(substr(md5(time() . $tx_ref), 0, 4)) . "-" . strtoupper(substr(md5($transaction_id), 0, 4));
                    
                    // Determine activations based on plan/amount (example logic)
                    $maxActivations = ($amountPaid >= 1000000) ? 5 : 1; 

                    // Insert License
                    $stmt = $pdo->prepare("INSERT INTO licenses (license_key, customer_name, status, max_activations, user_id) VALUES (?, ?, 'Active', ?, ?)");
                    $stmt->execute([$licenseKey, "Customer #".$payment['user_id'], $maxActivations, $payment['user_id']]);
                    $licenseId = $pdo->lastInsertId();

                    // Link license back to payment
                    $stmt = $pdo->prepare("UPDATE payments SET license_id = ? WHERE id = ?");
                    $stmt->execute([$licenseId, $payment['id']]);

                    $pdo->commit();
                    
                    // Redirect to dashboard with success
                    header("Location: " . BASE_URL . "dashboard.php?success=1&key=" . $licenseKey);
                    exit;
                }
            } catch (Exception $e) {
                die("Critical Internal Error: " . $e->getMessage());
            }
        }
    }
}

// If failed or cancelled
header("Location: " . BASE_URL . "dashboard.php?error=payment_failed");
exit;
