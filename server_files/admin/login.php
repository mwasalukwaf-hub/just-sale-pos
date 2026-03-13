<?php
// server_files/admin/login.php
require_once '../config.php';
session_start();

if (isset($_SESSION['admin_auth'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $stmt = $pdo->prepare("SELECT * FROM portal_admins WHERE username = ?");
        $stmt->execute([$user]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($pass, $admin['password_hash'])) {
            $_SESSION['admin_auth'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid administrator credentials.";
        }
    } catch (Exception $e) { $error = $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | JUSTSALE Central</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .admin-card { background: white; width: 100%; max-width: 400px; padding: 40px; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    <div class="admin-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-dark">Central Admin</h2>
            <p class="text-muted small">Licensing & Operations Terminal</p>
        </div>
        <?php if(isset($error)) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Admin Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Master Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">Authorize Access</button>
        </form>
    </div>
</body>
</html>
