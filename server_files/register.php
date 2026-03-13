<?php
// register.php for Central Portal
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';

    if (!empty($name) && !empty($email) && !empty($pass)) {
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO portal_users (fullname, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['fullname'] = $name;
            $_SESSION['email'] = $email;
            
            header("Location: dashboard.php");
            exit;
        } catch (Exception $e) { $error = "Email already registered or system error."; }
    } else { $error = "All fields are required."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | JUSTSALE Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #1e293b; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { background: white; width: 100%; max-width: 450px; padding: 40px; border-radius: 30px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary">Get Started</h2>
            <p class="text-muted small">Create an account to purchase licenses</p>
        </div>
        <?php if(isset($error)) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Register & Continue</button>
        </form>
        <div class="mt-4 text-center">
            <p class="small text-muted">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login</a></p>
        </div>
    </div>
</body>
</html>
