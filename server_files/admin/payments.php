<?php
// server_files/admin/payments.php
require_once '../config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $payments = $pdo->query("SELECT p.*, u.fullname as customer_name FROM payments p JOIN portal_users u ON p.user_id = u.id ORDER BY p.created_at DESC")->fetchAll();
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History | JUSTSALE Central</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; color: #f8fafc; }
        .sidebar { background: #1e293b; height: 100vh; padding: 40px 20px; position: fixed; width: 260px; }
        .main-content { margin-left: 260px; padding: 50px; }
        .nav-link { color: #94a3b8; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; }
        .nav-link:hover, .nav-link.active { background: rgba(67, 97, 238, 0.1); color: #4361ee; }
        .card-custom { background: #1e293b; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="fw-bold text-primary mb-5"><i class="fa-solid fa-crown me-2"></i>CENTRAL</h3>
    <nav class="nav flex-column">
        <a class="nav-link" href="index.php"><i class="fa-solid fa-gauge me-3"></i> Dashboard</a>
        <a class="nav-link" href="licenses.php"><i class="fa-solid fa-key me-3"></i> Manage Licenses</a>
        <a class="nav-link" href="users.php"><i class="fa-solid fa-users me-3"></i> Portal Users</a>
        <a class="nav-link active" href="payments.php"><i class="fa-solid fa-receipt me-3"></i> Payments</a>
        <hr class="opacity-10 my-4">
        <a class="nav-link text-danger" href="logout.php"><i class="fa-solid fa-power-off me-3"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    <h1 class="fw-black mb-1">Financial Records</h1>
    <p class="text-muted mb-5">All transactions processed via Flutterwave</p>

    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th class="border-0 opacity-50 small uppercase">Reference</th>
                        <th class="border-0 opacity-50 small uppercase">Customer</th>
                        <th class="border-0 opacity-50 small uppercase">Amount</th>
                        <th class="border-0 opacity-50 small uppercase">Status</th>
                        <th class="border-0 opacity-50 small uppercase">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="small font-monospace">
                            <div><?= $p['tx_ref'] ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;"><?= $p['transaction_id'] ?></div>
                        </td>
                        <td class="fw-bold"><?= $p['customer_name'] ?></td>
                        <td>
                            <div class="fw-black text-primary"><?= number_format($p['amount']) ?> <?= $p['currency'] ?></div>
                        </td>
                        <td>
                            <?php if($p['status'] == 'Successful'): ?>
                                <span class="badge bg-success rounded-pill px-3 py-2">Successful</span>
                            <?php elseif($p['status'] == 'Pending'): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill px-3 py-2">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td class="small opacity-50"><?= date('M d, Y H:i', strtotime($p['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; if(empty($payments)) echo '<tr><td colspan="5" class="text-center py-5 text-muted">No transactions recorded yet.</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
