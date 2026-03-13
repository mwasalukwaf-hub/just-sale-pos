<?php
// server_files/admin/index.php
require_once '../config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Stats
    $total_licenses = $pdo->query("SELECT COUNT(*) FROM licenses")->fetchColumn();
    $active_activations = $pdo->query("SELECT COUNT(*) FROM activations")->fetchColumn();
    $total_revenue = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'Successful'")->fetchColumn() ?: 0;
    $portal_users = $pdo->query("SELECT COUNT(*) FROM portal_users")->fetchColumn();

    // System Settings
    $license_bypass = $pdo->query("SELECT setting_value FROM portal_settings WHERE setting_key = 'global_license_check'")->fetchColumn();

} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Panel | JUSTSALE Central</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; color: #f8fafc; }
        .sidebar { background: #1e293b; height: 100vh; padding: 40px 20px; position: fixed; width: 260px; }
        .main-content { margin-left: 260px; padding: 50px; }
        .stat-card { background: #1e293b; border: none; border-radius: 20px; padding: 25px; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .nav-link { color: #94a3b8; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; font-weight: 500; }
        .nav-link:hover, .nav-link.active { background: rgba(67, 97, 238, 0.1); color: #4361ee; }
        .table-custom { background: #1e293b; color: white; border-radius: 20px; overflow: hidden; }
        .table-custom th { border: none; color: #94a3b8; text-transform: uppercase; font-size: 0.75rem; padding: 20px; }
        .table-custom td { border-top: 1px solid rgba(255,255,255,0.05); padding: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="fw-bold text-primary mb-5"><i class="fa-solid fa-crown me-2"></i>CENTRAL</h3>
    <nav class="nav flex-column">
        <a class="nav-link active" href="index.php"><i class="fa-solid fa-gauge me-3"></i> Dashboard</a>
        <a class="nav-link" href="licenses.php"><i class="fa-solid fa-key me-3"></i> Manage Licenses</a>
        <a class="nav-link" href="users.php"><i class="fa-solid fa-users me-3"></i> Portal Users</a>
        <a class="nav-link" href="payments.php"><i class="fa-solid fa-receipt me-3"></i> Payments</a>
        <hr class="opacity-10 my-4">
        <a class="nav-link text-danger" href="logout.php"><i class="fa-solid fa-power-off me-3"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    <?php if(isset($error)): ?>
        <div class="alert alert-danger mb-4"><?= $error ?></div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-black m-0">System Overview</h1>
            <p class="text-muted">Master control for JUSTSALE POS ecosystem</p>
        </div>
        <div class="form-check form-switch bg-dark p-3 rounded-4 border border-secondary">
            <input class="form-check-input ms-0 me-2" type="checkbox" id="bypassSwitch" <?= ($license_bypass === 'disabled') ? '' : 'checked' ?>>
            <label class="form-check-label fw-bold small" for="bypassSwitch">Global Licensing: <span id="statusLabel" class="<?= ($license_bypass === 'disabled') ? 'text-danger' : 'text-success' ?>"><?= strtoupper($license_bypass) ?></span></label>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-bold mb-2">TOTAL LICENSES</div>
                <h2 class="fw-black mb-0"><?= $total_licenses ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-bold mb-2">ACTIVE SITES</div>
                <h2 class="fw-black mb-0 text-primary"><?= $active_activations ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-bold mb-2">TOTAL REVENUE (TZS)</div>
                <h2 class="fw-black mb-0 text-success"><?= number_format($total_revenue) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-bold mb-2">CUSTOMERS</div>
                <h2 class="fw-black mb-0"><?= $portal_users ?></h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card table-custom p-0 border-0 shadow-lg">
                <div class="p-4 d-flex justify-content-between align-items-center border-bottom border-secondary">
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Recent Activations</h5>
                    <a href="licenses.php" class="btn btn-sm btn-outline-light rounded-pill px-3">View All</a>
                </div>
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>License Key</th>
                            <th>Customer / Site</th>
                            <th>HWID Fingerprint</th>
                            <th>Last Heartbeat</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT a.*, l.license_key, l.status as l_status FROM activations a JOIN licenses l ON a.license_id = l.id ORDER BY a.last_check_in DESC LIMIT 8");
                        while($row = $stmt->fetch()): 
                        ?>
                        <tr>
                            <td class="font-monospace small text-primary"><?= $row['license_key'] ?></td>
                            <td><?= $row['hostname'] ?></td>
                            <td class="small opacity-50"><?= substr($row['hwid'], 0, 16) ?>...</td>
                            <td class="small"><?= date('M d, H:i', strtotime($row['last_check_in'])) ?></td>
                            <td><span class="badge bg-success bg-opacity-10 text-success"><?= $row['l_status'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('bypassSwitch').onchange = async (e) => {
        const checked = e.target.checked;
        const val = checked ? 'enabled' : 'disabled';
        
        const res = await fetch('api_admin.php?action=toggle_bypass', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `value=${val}`
        });
        const data = await res.json();
        if(data.success) {
            document.getElementById('statusLabel').innerText = val.toUpperCase();
            document.getElementById('statusLabel').className = checked ? 'text-success' : 'text-danger';
        }
    };
</script>
</body>
</html>
