<?php
// login.php for Central Portal
require_once 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';

    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $stmt = $pdo->prepare("SELECT * FROM portal_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid log in credentials.";
        }
    } catch (Exception $e) { $error = "System Error: " . $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | JUSTSALE Licensing Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #1e293b; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { background: white; width: 100%; max-width: 400px; padding: 40px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary">Welcome Back</h2>
            <p class="text-muted small">Manage your JUSTSALE licenses</p>
        </div>
        <?php if(isset($error)) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Login to Portal</button>
        </form>
        <div class="mt-4 text-center">
            <p class="small text-muted">New user? <a href="register.php" class="text-primary fw-bold text-decoration-none">Create Account</a></p>
        </div>
    </div>
</body>
</html>
