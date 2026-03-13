<?php
// Central API: Payment Initialization (Flutterwave)
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required to purchase.']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'initiate') {
    $plan = $_POST['plan'] ?? 'starter';
    $amount = ($plan === 'pro') ? 1200000 : 150000;
    
    $tx_ref = "JUST-" . time() . "-" . rand(1000, 9999);
    $email = $_SESSION['email'];
    $phone = $_SESSION['phone'] ?? '';
    $name = $_SESSION['fullname'];

    // Insert pending payment into DB
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, tx_ref, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$_SESSION['user_id'], $amount, $tx_ref]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        exit;
    }

    // Prepare Flutterwave Request
    $request = [
        'tx_ref' => $tx_ref,
        'amount' => $amount,
        'currency' => 'TZS',
        'payment_options' => 'card,mobilemoneytanzania',
        'redirect_url' => BASE_URL . 'api/callback.php',
        'customer' => [
            'email' => $email,
            'phonenumber' => $phone,
            'name' => $name
        ],
        'customizations' => [
            'title' => 'JUSTSALE POS License',
            'description' => "Subscription for $plan plan",
            'logo' => BASE_URL . 'assets/img/logo.png'
        ]
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.flutterwave.com/v3/payments",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($request),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . FLW_SECRET_KEY,
            "Content-Type: application/json"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    
    $res = json_decode($response, true);
    if ($res && $res['status'] === 'success') {
        echo json_encode(['success' => true, 'link' => $res['data']['link']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Flutterwave Error: ' . ($res['message'] ?? 'Unknown error')]);
    }
}
