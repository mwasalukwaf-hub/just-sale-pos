<?php
// server_files/admin/licenses.php
require_once '../config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("Location: login");
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
        <a class="nav-link" href="index"><i class="fa-solid fa-gauge me-3"></i> Dashboard</a>
        <a class="nav-link active" href="licenses"><i class="fa-solid fa-key me-3"></i> Manage Licenses</a>
        <a class="nav-link" href="updates"><i class="fa-solid fa-cloud-arrow-up me-3"></i> Software Updates</a>
        <a class="nav-link" href="users"><i class="fa-solid fa-users me-3"></i> Portal Users</a>
        <a class="nav-link" href="payments"><i class="fa-solid fa-receipt me-3"></i> Payments</a>
        <hr class="opacity-10 my-4">
        <a class="nav-link text-danger" href="logout"><i class="fa-solid fa-power-off me-3"></i> Logout</a>
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
                            <div class="small opacity-50">User: <?= $l['owner_name'] ?: '<span class="text-danger">None</span>' ?></div>
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
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="editLicense(<?= $l['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-link text-danger" onclick="deleteLicense(<?= $l['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit License Modal -->
<div class="modal fade" id="editLicenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title fw-bold">Edit License</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editLicenseForm">
                    <input type="hidden" id="edit-license-id" name="id">
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Customer / Site Name</label>
                        <input type="text" class="form-control bg-secondary text-white border-0" id="edit-customer" name="customer" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Max Activations Limit</label>
                        <input type="number" class="form-control bg-secondary text-white border-0" id="edit-limit" name="limit" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Expiry Date (Blank = Forever)</label>
                        <input type="date" class="form-control bg-secondary text-white border-0" id="edit-expiry" name="expiry">
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Assigned Portal User (Relationship)</label>
                        <select class="form-select bg-secondary text-white border-0" id="edit-user-id" name="user_id">
                            <option value="">-- No User Assigned --</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-link text-white text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" onclick="saveLicenseEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editLicenseModal'));

    async function editLicense(id) {
        // 1. Fetch license data
        const res = await fetch(`api_admin.php?action=get_license&id=${id}`);
        const data = await res.json();
        
        if(data.success) {
            const l = data.data;
            document.getElementById('edit-license-id').value = l.id;
            document.getElementById('edit-customer').value = l.customer_name;
            document.getElementById('edit-limit').value = l.max_activations;
            document.getElementById('edit-expiry').value = l.expiry_date || '';
            
            // 2. Fetch users list for dropdown
            const uRes = await fetch('api_admin.php?action=get_users_list');
            const uData = await uRes.json();
            
            const userSelect = document.getElementById('edit-user-id');
            userSelect.innerHTML = '<option value="">-- No User Assigned --</option>';
            
            if(uData.success) {
                uData.data.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = `${u.fullname} (${u.email})`;
                    if(u.id == l.user_id) opt.selected = true;
                    userSelect.appendChild(opt);
                });
            }
            
            editModal.show();
        }
    }

    async function saveLicenseEdit() {
        const form = document.getElementById('editLicenseForm');
        const fd = new FormData(form);
        const body = new URLSearchParams(fd);

        try {
            const res = await fetch('api_admin.php?action=edit_license', {
                method: 'POST',
                body: body
            });
            const data = await res.json();
            if(data.success) {
                Swal.fire('Updated', 'License updated successfully.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch(e) {}
    }
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
