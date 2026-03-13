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
    <title>Merchant Login | JUSTSALE Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --dark: #0f172a;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%);
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .auth-card { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            width: 100%; 
            max-width: 440px; 
            padding: 50px; 
            border-radius: 35px; 
            box-shadow: 0 40px 100px rgba(0,0,0,0.5); 
        }
        .form-control {
            padding: 12px 18px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            border-color: var(--primary);
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="text-center">
            <div class="brand-icon"><i class="fa-solid fa-cash-register"></i></div>
            <h2 class="fw-bold text-dark mb-1">Merchant Portal</h2>
            <p class="text-muted small mb-5">Access your license management dashboard</p>
        </div>
        
        <?php if(isset($error)) echo "<div class='alert alert-danger border-0 rounded-4 py-3 mb-4 small fw-bold'>$error</div>"; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="form-label small fw-bold text-uppercase opacity-75">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@company.com" required autofocus>
            </div>
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label small fw-bold text-uppercase opacity-75">Password</label>
                    <a href="#" class="small text-decoration-none text-primary opacity-75 fw-bold">Forgot?</a>
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-lg" style="background: var(--primary); border:none;">
                Log In to Dashboard <i class="fa-solid fa-arrow-right-long ms-2"></i>
            </button>
        </form>
        
        <div class="mt-5 text-center">
            <p class="small text-muted mb-0">Don't have an account? <br>
                <a href="register.php" class="text-primary fw-bold text-decoration-none fs-6">Create Merchant ID</a>
            </p>
        </div>
    </div>
</body>
</html>
