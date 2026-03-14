<?php
// server_files/versions.php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) { die("System offline"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Roadmap & Releases | JUSTSALE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4361ee; --dark: #0f172a; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: var(--dark); padding-top: 100px; }
        .navbar { background: white; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .release-card { background: white; border-radius: 24px; padding: 40px; margin-bottom: 40px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .version-badge { padding: 8px 20px; border-radius: 50px; background: rgba(67, 97, 238, 0.1); color: var(--primary); font-weight: 800; }
        .changelog-content { margin-top: 25px; line-height: 1.8; color: #64748b; }
        .changelog-content ul { padding-left: 20px; }
        .critical-alert { border-left: 4px solid #ef4444; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top py-3">
    <div class="container">
        <a class="navbar-brand fw-black text-primary fs-3" href="/"><i class="fa-solid fa-cash-register me-2"></i>JUSTSALE</a>
        <div class="ms-auto">
            <a href="login" class="btn btn-outline-primary rounded-pill px-4 fw-bold">Login to Portal</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="text-center mb-5 pb-4">
        <h1 class="display-4 fw-black">Product <span class="text-primary">Roadmap</span></h1>
        <p class="lead text-muted">Stay up to date with the latest features, security patches, and improvements.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <?php
            $stmt = $pdo->query("SELECT * FROM system_versions ORDER BY release_date DESC, v_entry DESC");
            while($row = $stmt->fetch()): 
            ?>
            <div class="release-card <?= $row['is_critical'] ? 'critical-alert' : '' ?>">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="version-badge">Version <?= $row['version_number'] ?></span>
                        <span class="text-muted small fw-bold"><i class="fa-regular fa-calendar me-1"></i> <?= date('M d, Y', strtotime($row['release_date'])) ?></span>
                    </div>
                    <?php if($row['is_critical']): ?>
                        <span class="badge bg-danger rounded-pill px-3">CRITICAL UPDATE</span>
                    <?php endif; ?>
                </div>

                <div class="changelog-content">
                    <?= nl2br($row['changelog']) ?>
                </div>

                <div class="mt-4 pt-4 border-top d-flex justify-content-between align-items-center">
                    <div class="small text-muted">Minimum PHP: <strong><?= $row['min_php_version'] ?>+</strong></div>
                    <a href="login" class="btn btn-primary btn-sm rounded-pill px-4">Download via Portal</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<footer class="py-5 mt-5 bg-white border-top">
    <div class="container text-center">
        <p class="text-muted small mb-0">&copy; 2026 JUSTSALE POS System. Developed by Franklin.</p>
    </div>
</footer>

</body>
</html>
