<?php
require_once '../includes/config.php';

$msg = "";

if(isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

if(isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Admin';
        header('Location: dashboard.php');
        exit();
    } else {
        $msg = "<div class='alert error'>Invalid username or password.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PetCare Grooming</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#1f2937 0%,#4f46e5 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;color:#f8fafc}
        .login-card{background:rgba(255,255,255,0.08);backdrop-filter:blur(15px);padding:40px;border-radius:24px;box-shadow:0 25px 60px rgba(0,0,0,0.25);width:100%;max-width:420px}
        .login-card h2{text-align:center;font-size:32px;margin-bottom:12px}
        .login-card p.subtitle{text-align:center;color:#c7d2fe;margin-bottom:28px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;margin-bottom:8px;font-size:13px;font-weight:600;color:#e2e8f0}
        .form-group input{width:100%;padding:14px 16px;border-radius:14px;border:1px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.12);color:#f8fafc;font-size:15px}
        .form-group input:focus{outline:none;border-color:#818cf8;background:rgba(255,255,255,0.18)}
        button{width:100%;padding:16px;border:none;border-radius:14px;background:#6366f1;color:white;font-size:16px;font-weight:700;cursor:pointer;transition:transform .2s,box-shadow .2s}
        button:hover{transform:translateY(-2px);box-shadow:0 18px 35px rgba(99,102,241,0.3)}
        .alert{padding:14px 16px;border-radius:14px;margin-bottom:18px;font-size:14px;font-weight:600}
        .alert.error{background:#fee2e2;color:#b91c1c;border-left:4px solid #f87171}
        .back-link{display:block;text-align:center;color:#c7d2fe;margin-top:18px;text-decoration:none}
        .back-link:hover{text-decoration:underline}
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Admin Login</h2>
        <p class="subtitle">Sign in with your administrator account.</p>
        <?= $msg ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" name="login">Login</button>
        </form>
        <a class="back-link" href="../index.php">Back to site</a>
    </div>
</body>
</html>
