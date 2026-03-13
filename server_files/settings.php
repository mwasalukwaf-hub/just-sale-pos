<?php
// server_files/settings.php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM portal_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $new_pass = $_POST['password'] ?? '';

        if (!empty($fullname) && !empty($email)) {
            $sql = "UPDATE portal_users SET fullname = ?, email = ? WHERE id = ?";
            $params = [$fullname, $email, $_SESSION['user_id']];
            
            if (!empty($new_pass)) {
                $sql = "UPDATE portal_users SET fullname = ?, email = ?, password_hash = ? WHERE id = ?";
                $params = [$fullname, $email, password_hash($new_pass, PASSWORD_DEFAULT), $_SESSION['user_id']];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $success = "Profile updated successfully.";
        }
    }
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | JUSTSALE Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        .sidebar { height: 100vh; background: #1e293b; color: white; padding: 30px 20px; position: fixed; width: 260px; }
        .main-content { margin-left: 260px; padding: 50px; }
        .nav-link { color: rgba(255,255,255,0.7); margin-bottom: 10px; border-radius: 10px; padding: 12px 15px; text-decoration: none; display: block; }
        .nav-link.active { background: #4361ee; color: white; }
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="fw-bold text-primary mb-5"><i class="fa-solid fa-cash-register me-2"></i>JUSTSALE</h4>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard"><i class="fa-solid fa-key me-3"></i> My Licenses</a>
        <a class="nav-link" href="buy"><i class="fa-solid fa-cart-shopping me-3"></i> Buy Key</a>
        <a class="nav-link" href="help" target="_blank"><i class="fa-solid fa-circle-question me-3"></i> User Manual</a>
        <a class="nav-link active" href="settings"><i class="fa-solid fa-user me-3"></i> Profile</a>
        <hr class="opacity-20">
        <a class="nav-link text-danger" href="logout"><i class="fa-solid fa-sign-out-alt me-3"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    <h2 class="fw-black mb-1">Account Settings</h2>
    <p class="text-muted mb-5">Update your personal information and password</p>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-custom p-4 p-md-5">
                <?php if(isset($success)) echo "<div class='alert alert-success py-2'>$success</div>"; ?>
                <?php if(isset($error)) echo "<div class='alert alert-danger py-2'>$error</div>"; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Full Name</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">Save Changes</button>
                </form>
            </div>
        </div>
        <div class="col-md-5 offset-md-1">
            <div class="mt-5 mt-md-0 p-4 border rounded-4 bg-white shadow-sm">
                <h5 class="fw-bold mb-3">Security Note</h5>
                <p class="text-muted small">Your account is secured with 256-bit encryption. Always use a strong password with at least 8 characters, including numbers and symbols.</p>
                <hr>
                <p class="text-muted small mb-0">For business ownership transfers, please contact <a href="http://franklin.co.tz">Franklin Support</a>.</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
