<?php
require_once 'config.php';
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch User's Licenses
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $licenses = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | My JUSTSALE Licenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        .sidebar { height: 100vh; background: #1e293b; color: white; padding: 30px 20px; }
        .nav-link { color: rgba(255,255,255,0.7); margin-bottom: 10px; border-radius: 10px; padding: 12px 15px; }
        .nav-link.active { background: #4361ee; color: white; }
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .license-key { font-family: monospace; background: #f1f5f9; padding: 10px; border-radius: 8px; border: 1px dashed #cbd5e1; display: block; width: 100%; border: none; text-align: left; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 d-none d-md-block sidebar shadow">
            <h4 class="fw-bold text-primary mb-5"><i class="fa-solid fa-cash-register me-2"></i>JUSTSALE</h4>
            <nav class="nav flex-column">
                <a class="nav-link active" href="#"><i class="fa-solid fa-key me-3"></i> My Licenses</a>
                <a class="nav-link" href="buy"><i class="fa-solid fa-cart-shopping me-3"></i> Buy Key</a>
                <a class="nav-link" href="help" target="_blank"><i class="fa-solid fa-circle-question me-3"></i> User Manual</a>
                <a class="nav-link" href="settings"><i class="fa-solid fa-user me-3"></i> Profile</a>
                <hr class="opacity-20">
                <a class="nav-link text-danger" href="logout"><i class="fa-solid fa-sign-out-alt me-3"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 py-5 px-md-5">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-black">Account Dashboard</h2>
                    <p class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['fullname']) ?></p>
                </div>
                <a href="buy" class="btn btn-primary px-4 py-2 rounded-pill fw-bold"><i class="fa-solid fa-plus me-2"></i> New License</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success card-custom p-4 mb-4 border-start border-success border-5">
                    <h5 class="fw-bold mb-1">🎉 Payment Successful!</h5>
                    <p class="mb-2">Your new license key has been generated. Use it to activate your POS installation.</p>
                    <code class="fs-4"><?= htmlspecialchars($_GET['key']) ?></code>
                </div>
            <?php endif; ?>

            <div class="card card-custom p-4">
                <h5 class="fw-bold mb-4">Your Active Licenses</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>License Key</th>
                                <th>Status</th>
                                <th>Activations</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($licenses as $l): ?>
                            <tr>
                                <td style="width: 350px;">
                                    <div class="input-group">
                                        <input type="text" class="form-control border-0 bg-light font-monospace small" value="<?= $l['license_key'] ?>" readonly id="key-<?= $l['id'] ?>">
                                        <button class="btn btn-light border-0" onclick="copyKey('key-<?= $l['id'] ?>')"><i class="fa-solid fa-copy"></i></button>
                                    </div>
                                </td>
                                <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><?= $l['status'] ?></span></td>
                                <td><span class="fw-bold text-primary">0 / <?= $l['max_activations'] ?></span></td>
                                <td class="text-muted small"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                                <td><button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i></button></td>
                            </tr>
                            <?php endforeach; if(empty($licenses)) echo '<tr><td colspan="5" class="text-center py-5 text-muted">No licenses found. Start by purchasing one.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function copyKey(id) {
        let copyText = document.getElementById(id);
        copyText.select();
        document.execCommand("copy");
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Copied to clipboard',
            showConfirmButton: false,
            timer: 1500
        });
    }
</script>
</body>
</html>
