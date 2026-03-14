<?php
// server_files/admin/updates.php
require_once '../config.php';
session_start();

if (!isset($_SESSION['admin_auth'])) {
    header("Location: login");
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Updates | JUSTSALE Central</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; color: #f8fafc; }
        .sidebar { background: #1e293b; height: 100vh; padding: 40px 20px; position: fixed; width: 260px; }
        .main-content { margin-left: 260px; padding: 50px; }
        .nav-link { color: #94a3b8; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; font-weight: 500; }
        .nav-link:hover, .nav-link.active { background: rgba(67, 97, 238, 0.1); color: #4361ee; }
        .card-custom { background: #1e293b; border: none; border-radius: 20px; color: white; }
        .table-custom { background: #1e293b; color: white; border-radius: 20px; overflow: hidden; }
        .table-custom th { border: none; color: #94a3b8; text-transform: uppercase; font-size: 0.75rem; padding: 20px; }
        .table-custom td { border-top: 1px solid rgba(255,255,255,0.05); padding: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="fw-bold text-primary mb-5"><i class="fa-solid fa-crown me-2"></i>CENTRAL</h3>
    <nav class="nav flex-column">
        <a class="nav-link" href="index"><i class="fa-solid fa-gauge me-3"></i> Dashboard</a>
        <a class="nav-link" href="licenses"><i class="fa-solid fa-key me-3"></i> Manage Licenses</a>
        <a class="nav-link active" href="updates"><i class="fa-solid fa-cloud-arrow-up me-3"></i> Software Updates</a>
        <a class="nav-link" href="users"><i class="fa-solid fa-users me-3"></i> Portal Users</a>
        <a class="nav-link" href="payments"><i class="fa-solid fa-receipt me-3"></i> Payments</a>
        <hr class="opacity-10 my-4">
        <a class="nav-link text-danger" href="logout"><i class="fa-solid fa-power-off me-3"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-black m-0">Software Versions</h1>
            <p class="text-muted">Manage releases and download packages</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addVersionModal">
            <i class="fa-solid fa-plus me-2"></i> NEW RELEASE
        </button>
    </div>

    <div class="card table-custom border-0 shadow-lg">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Release Date</th>
                    <th>Status</th>
                    <th>PHP</th>
                    <th>Download Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM system_versions ORDER BY release_date DESC, v_entry DESC");
                while($row = $stmt->fetch()): 
                ?>
                <tr>
                    <td><span class="fw-bold">v<?= $row['version_number'] ?></span></td>
                    <td><?= date('M d, Y', strtotime($row['release_date'])) ?></td>
                    <td>
                        <?php if($row['is_critical']): ?>
                            <span class="badge bg-danger">CRITICAL</span>
                        <?php else: ?>
                            <span class="badge bg-success">STABLE</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="font-monospace small opacity-50"><?= $row['min_php_version'] ?>+</span></td>
                    <td><a href="<?= $row['download_url'] ?>" class="text-decoration-none small text-primary" target="_blank"><?= basename($row['download_url']) ?></a></td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteVersion(<?= $row['v_entry'] ?>)"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Version Modal -->
<div class="modal fade" id="addVersionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold">Push New Software Version</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addVersionForm">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">VERSION NUMBER</label>
                            <input type="text" name="version_number" class="form-control bg-secondary bg-opacity-10 border-secondary text-white" placeholder="e.g. 2.0.5" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">RELEASE DATE</label>
                            <input type="date" name="release_date" class="form-control bg-secondary bg-opacity-10 border-secondary text-white" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">UPLOAD ZIP PACKAGE (.ZIP)</label>
                            <input type="file" name="update_zip" class="form-control bg-secondary bg-opacity-10 border-secondary text-white" accept=".zip" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">CHANGELOG / DESCRIPTIONS</label>
                            <textarea name="changelog" rows="6" class="form-control bg-secondary bg-opacity-10 border-secondary text-white" placeholder="Bullet points of what's new..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_critical" value="1">
                                <label class="form-check-label fw-bold small">Mark as Critical Update</label>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label small fw-bold d-block">MIN PHP VERSION</label>
                            <input type="text" name="min_php_version" class="form-control bg-secondary bg-opacity-10 border-secondary text-white d-inline-block w-50" value="7.4">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" id="saveBtn">UPLOAD & PUSH</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('addVersionForm').onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> UPLOADING...';
        btn.disabled = true;

        const fd = new FormData(e.target);
        
        try {
            const res = await fetch('api_admin.php?action=add_version', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if(data.success) {
                Swal.fire('Success', data.message, 'success').then(() => window.location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Upload failed', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };

    async function deleteVersion(id) {
        if(!confirm('Are you sure you want to delete this version? This is destructive.')) return;
        
        const res = await fetch('api_admin.php?action=delete_version', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        });
        const data = await res.json();
        if(data.success) window.location.reload();
    }
</script>
</body>
</html>
