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

    $payments = $pdo->query("SELECT p.*, u.fullname as customer_name, l.license_key FROM payments p LEFT JOIN portal_users u ON p.user_id = u.id LEFT JOIN licenses l ON p.license_id = l.id ORDER BY p.created_at DESC")->fetchAll();
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
        <a class="nav-link" href="index"><i class="fa-solid fa-gauge me-3"></i> Dashboard</a>
        <a class="nav-link" href="licenses"><i class="fa-solid fa-key me-3"></i> Manage Licenses</a>
        <a class="nav-link" href="users"><i class="fa-solid fa-users me-3"></i> Portal Users</a>
        <a class="nav-link active" href="payments"><i class="fa-solid fa-receipt me-3"></i> Payments</a>
        <hr class="opacity-10 my-4">
        <a class="nav-link text-danger" href="logout"><i class="fa-solid fa-power-off me-3"></i> Logout</a>
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
                        <th class="border-0 opacity-50 small uppercase">Customer & License</th>
                        <th class="border-0 opacity-50 small uppercase">Amount</th>
                        <th class="border-0 opacity-50 small uppercase">Status</th>
                        <th class="border-0 opacity-50 small uppercase">Date</th>
                        <th class="border-0 opacity-50 small uppercase text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="small font-monospace">
                            <div><?= $p['tx_ref'] ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;"><?= $p['transaction_id'] ?></div>
                        </td>
                        <td>
                            <div class="fw-bold"><?= $p['customer_name'] ?: '<span class="text-danger small">Unlinked User</span>' ?></div>
                            <div class="small opacity-50">Key: <?= $p['license_key'] ?: '<span class="text-warning">No License Assigned</span>' ?></div>
                        </td>
                        <td>
                            <div class="fw-black text-primary"><?= number_format($p['amount']) ?> <?= $p['currency'] ?></div>
                        </td>
                        <td>
                            <?php if($p['status'] == 'Successful'): ?>
                                <span class="badge bg-success rounded-pill px-3 py-2">Successful</span>
                            <?php elseif($p['status'] == 'Pending'): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill px-3 py-2"><?= $p['status'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small opacity-50"><?= date('M d, Y H:i', strtotime($p['created_at'])) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="editPayment(<?= $p['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-link text-danger" onclick="deletePayment(<?= $p['id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($payments)) echo '<tr><td colspan="6" class="text-center py-5 text-muted">No transactions recorded yet.</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title fw-bold">Edit Payment Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPaymentForm">
                    <input type="hidden" id="edit-payment-id" name="id">
                    
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Payment Status</label>
                        <select class="form-select bg-secondary text-white border-0" id="edit-status" name="status">
                            <option value="Successful">Successful</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted mb-1">Relate to Portal User</label>
                        <select class="form-select bg-secondary text-white border-0" id="edit-user-id" name="user_id">
                            <option value="">-- No User Assigned --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted mb-1">Relate to License</label>
                        <select class="form-select bg-secondary text-white border-0" id="edit-license-id" name="license_id">
                            <option value="">-- No License Assigned --</option>
                        </select>
                    </div>
                </form>
                <div class="alert alert-info py-2 small border-0 bg-opacity-10 text-info">
                    Linking a payment to a license and user helps in tracking which manual key was paid for by whom.
                </div>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-link text-white text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" onclick="savePaymentEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editPaymentModal'));

    async function editPayment(id) {
        // 1. Fetch payment data
        const res = await fetch(`api_admin.php?action=get_payment&id=${id}`);
        const data = await res.json();
        
        if(data.success) {
            const p = data.data;
            document.getElementById('edit-payment-id').value = p.id;
            document.getElementById('edit-status').value = p.status;
            
            // 2. Fetch users list
            const uRes = await fetch('api_admin.php?action=get_users_list');
            const uData = await uRes.json();
            const userSelect = document.getElementById('edit-user-id');
            userSelect.innerHTML = '<option value="">-- No User Assigned --</option>';
            if(uData.success) {
                uData.data.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = `${u.fullname} (${u.email})`;
                    if(u.id == p.user_id) opt.selected = true;
                    userSelect.appendChild(opt);
                });
            }

            // 3. Fetch licenses list
            const lRes = await fetch('api_admin.php?action=get_licenses_all');
            const lData = await lRes.json();
            const licenseSelect = document.getElementById('edit-license-id');
            licenseSelect.innerHTML = '<option value="">-- No License Assigned --</option>';
            if(lData.success) {
                lData.data.forEach(l => {
                    const opt = document.createElement('option');
                    opt.value = l.id;
                    opt.textContent = `${l.license_key} (${l.customer_name})`;
                    if(l.id == p.license_id) opt.selected = true;
                    licenseSelect.appendChild(opt);
                });
            }
            
            editModal.show();
        }
    }

    async function savePaymentEdit() {
        const form = document.getElementById('editPaymentForm');
        const fd = new FormData(form);
        const body = new URLSearchParams(fd);

        try {
            const res = await fetch('api_admin.php?action=edit_payment', {
                method: 'POST',
                body: body
            });
            const data = await res.json();
            if(data.success) {
                Swal.fire('Updated', 'Payment record updated.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch(e) {}
    }

    async function deletePayment(id) {
        if(confirm('Are you sure you want to delete this payment record? This cannot be undone.')) {
            const res = await fetch('api_admin.php?action=delete_payment', {
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
