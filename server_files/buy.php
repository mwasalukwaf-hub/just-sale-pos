<?php
// buy.php - Checkout page for licensing portal
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade My System | JUSTSALE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f4f7f6; }
        .checkout-card { max-width: 500px; margin: 80px auto; border-radius: 25px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
        .price-badge { background: #4361ee; color: white; padding: 10px 20px; border-radius: 12px; font-weight: 800; font-size: 1.5rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="card checkout-card p-4 p-md-5">
        <div class="text-center mb-5">
            <div class="text-primary mb-3"><i class="fa-solid fa-shield-halved fa-3x"></i></div>
            <h3 class="fw-black">Confirm Purchase</h3>
            <p class="text-muted">Secure license generation via Flutterwave</p>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-4 mb-3">
                <span class="fw-bold">Professional Package</span>
                <span class="text-primary fw-bold">Annual</span>
            </div>
            <div class="text-center my-4">
                <div class="price-badge">TZS 1,200,000</div>
                <p class="small text-muted mt-2">Includes priority support and 5 installations.</p>
            </div>
        </div>

        <button id="btnPay" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-lg">
            Proceed to Payment <i class="fa-solid fa-arrow-right ms-2"></i>
        </button>

        <div class="mt-4 text-center">
            <img src="https://flutterwave.com/images/flutterwave-logo.svg" alt="Flutterwave" style="height: 25px; opacity: 0.6;">
            <p class="small text-muted mt-2">Mobile Money, Card, and Bank payments accepted.</p>
        </div>
        
        <a href="dashboard.php" class="btn btn-link w-100 text-decoration-none text-muted small mt-2">Cancel and Return</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('btnPay').onclick = async () => {
        const btn = document.getElementById('btnPay');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Connecting...';

        try {
            const res = await fetch('api/payment.php?action=initiate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'plan=pro'
            });
            const data = await res.json();

            if (data.success && data.link) {
                window.location.href = data.link; // Redirect to Flutterwave
            } else {
                Swal.fire('Payment Failed', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'Proceed to Payment <i class="fa-solid fa-arrow-right ms-2"></i>';
            }
        } catch (e) {
            Swal.fire('Error', 'Communication error with payment server.', 'error');
            btn.disabled = false;
        }
    };
</script>

</body>
</html>
