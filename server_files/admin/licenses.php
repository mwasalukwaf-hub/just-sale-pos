<?php
// server_files/admin/licenses.php
require_once '../config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $licenses = $pdo->query("SELECT l.*, u.fullname as owner_name FROM licenses l LEFT JOIN portal_users u ON l.user_id = u.id ORDER BY l.created_at DESC")->fetchAll();
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Licenses | JUSTSALE Central</title>
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
        <a class="nav-link active" href="licenses.php"><i class="fa-solid fa-key me-3"></i> Manage Licenses</a>
        <a class="nav-link" href="users.php"><i class="fa-solid fa-users me-3"></i> Portal Users</a>
        <a class="nav-link" href="payments.php"><i class="fa-solid fa-receipt me-3"></i> Payments</a>
        <hr class="opacity-10 my-4">
        <a class="nav-link text-danger" href="logout.php"><i class="fa-solid fa-power-off me-3"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-black m-0">Licenses</h1>
            <p class="text-muted">Generate, Block, or Disregard license keys</p>
        </div>
        <button class="btn btn-primary px-4 py-2 rounded-pill fw-bold" onclick="showCreateModal()">
            <i class="fa-solid fa-plus-circle me-2"></i> Manual Key
        </button>
    </div>

    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th class="border-0 opacity-50 small uppercase">License Key</th>
                        <th class="border-0 opacity-50 small uppercase">Customer</th>
                        <th class="border-0 opacity-50 small uppercase">Limit</th>
                        <th class="border-0 opacity-50 small uppercase">Expiry</th>
                        <th class="border-0 opacity-50 small uppercase">Status</th>
                        <th class="border-0 opacity-50 small uppercase text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($licenses as $l): ?>
                    <tr>
                        <td class="font-monospace text-primary"><?= $l['license_key'] ?></td>
                        <td>
                            <div class="fw-bold"><?= $l['customer_name'] ?></div>
                            <div class="small opacity-50">User: <?= $l['owner_name'] ?: 'N/A' ?></div>
                        </td>
                        <td class="fw-bold"><?= $l['max_activations'] ?></td>
                        <td class="small opacity-50"><?= $l['expiry_date'] ?: 'Forever' ?></td>
                        <td>
                            <select class="form-select form-select-sm bg-dark text-white border-0 w-auto" onchange="updateStatus(<?= $l['id'] ?>, this.value)">
                                <option value="Active" <?= $l['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Expired" <?= $l['status'] == 'Expired' ? 'selected' : '' ?>>Expired</option>
                                <option value="Blocked" <?= $l['status'] == 'Blocked' ? 'selected' : '' ?>>Blocked</option>
                                <option value="Pending" <?= $l['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-link text-danger" onclick="deleteLicense(<?= $l['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    async function showCreateModal() {
        const { value: formValues } = await Swal.fire({
            title: 'Manual Key Generation',
            background: '#1e293b',
            color: '#fff',
            html:
                '<input id="swal-key" class="swal2-input" placeholder="Custom Key (optional)">' +
                '<input id="swal-cust" class="swal2-input" placeholder="Customer/Site Name">' +
                '<input id="swal-limit" type="number" class="swal2-input" value="1" placeholder="Max Activations">',
            focusConfirm: false,
            preConfirm: () => {
                return [
                    document.getElementById('swal-key').value,
                    document.getElementById('swal-cust').value,
                    document.getElementById('swal-limit').value
                ]
            }
        });

        if (formValues) {
            const [key, cust, limit] = formValues;
            try {
                const res = await fetch('api_admin.php?action=create_license', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `key=${key}&customer=${cust}&limit=${limit}`
                });
                const data = await res.json();
                if(data.success) {
                    Swal.fire('Success', 'Key generated.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch(e) {}
        }
    }

    async function updateStatus(id, newStatus) {
        const res = await fetch('api_admin.php?action=update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&status=${newStatus}`
        });
        const data = await res.json();
        if(data.success) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Status updated', showConfirmButton: false, timer: 1500 });
        }
    }

    async function deleteLicense(id) {
        if(confirm('Are you sure you want to PERMANENTLY delete this license?')) {
            const res = await fetch('api_admin.php?action=delete_license', {
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
