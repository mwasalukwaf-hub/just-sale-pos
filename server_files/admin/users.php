<?php
// server_files/admin/users.php
require_once '../config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM licenses WHERE user_id = u.id) as license_count FROM portal_users u ORDER BY u.created_at DESC")->fetchAll();
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | JUSTSALE Central</title>
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
        <a class="nav-link active" href="users.php"><i class="fa-solid fa-users me-3"></i> Portal Users</a>
        <a class="nav-link" href="payments.php"><i class="fa-solid fa-receipt me-3"></i> Payments</a>
        <hr class="opacity-10 my-4">
        <a class="nav-link text-danger" href="logout.php"><i class="fa-solid fa-power-off me-3"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    <h1 class="fw-black mb-1">Portal Users</h1>
    <p class="text-muted mb-5">Customers registered on the licensing portal</p>

    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th class="border-0 opacity-50 small uppercase">Full Name</th>
                        <th class="border-0 opacity-50 small uppercase">Email Address</th>
                        <th class="border-0 opacity-50 small uppercase">Owned Licenses</th>
                        <th class="border-0 opacity-50 small uppercase">Join Date</th>
                        <th class="border-0 opacity-50 small uppercase text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold text-primary"><?= $u['fullname'] ?></td>
                        <td><?= $u['email'] ?></td>
                        <td>
                            <span class="badge bg-primary rounded-pill px-3"><?= $u['license_count'] ?> Licenses</span>
                        </td>
                        <td class="small opacity-50"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-link text-danger" onclick="deleteUser(<?= $u['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($users)) echo '<tr><td colspan="5" class="text-center py-5 text-muted">No users registered yet.</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    async function deleteUser(id) {
        if(confirm('Delete this user? This will NOT delete their licenses but they will lose portal access.')) {
            const res = await fetch('api_admin.php?action=delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            });
            const data = await res.json();
            if(data.success) location.reload();
        }
    }
</script>
</body>
</html>
