<?php
require 'includes/config.php';

$msg = "";

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if(isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    
    if($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            header("Location: dashboard.php");
            exit();
        } else {
            $msg = "<div class='alert error'>Wrong password! Try again.</div>";
        }
    } else {
        $msg = "<div class='alert error'>Email not found! Please register first.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetCare Grooming</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        *{margin:0;padding:0;box-sizing:border-box}
        
        body{
            font-family:'Poppins',sans-serif;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
            position:relative;
            overflow-x:hidden;
        }
        
        body::before{
            content:'🐕 🐈 🐕‍🦺 🐩';
            position:absolute;
            font-size:100px;
            opacity:0.1;
            top:50px;
            left:50px;
            animation:float 10s ease-in-out infinite;
        }
        
        body::after{
            content:'🐾 🦴 🐾';
            position:absolute;
            font-size:80px;
            opacity:0.08;
            bottom:50px;
            right:50px;
            animation:float 12s ease-in-out infinite reverse;
        }
        
        @keyframes float{
            0%,100%{transform:translateY(0) rotate(0deg)}
            50%{transform:translateY(-30px) rotate(15deg)}
        }
        
        .container{
            background:rgba(255,255,255,0.98);
            backdrop-filter:blur(15px);
            padding:50px 40px;
            border-radius:20px;
            box-shadow:0 30px 80px rgba(0,0,0,0.35);
            max-width:450px;
            width:100%;
            position:relative;
            z-index:1;
            animation:slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp{
            from{opacity:0;transform:translateY(40px)}
            to{opacity:1;transform:translateY(0)}
        }
        
        .logo{
            text-align:center;
            font-size:70px;
            margin-bottom:15px;
            animation:pulse 2s infinite;
        }
        
        @keyframes pulse{
            0%,100%{transform:scale(1)}
            50%{transform:scale(1.1)}
        }
        
        h2{
            color:#1a202c;
            margin-bottom:8px;
            text-align:center;
            font-weight:700;
            font-size:30px;
        }
        
        .subtitle{
            color:#718096;
            text-align:center;
            margin-bottom:35px;
            font-size:15px;
        }
        
        .form-group{
            margin-bottom:22px;
        }
        
        label{
            color:#2d3748;
            font-size:13px;
            font-weight:600;
            display:block;
            margin-bottom:8px;
            text-transform:uppercase;
            letter-spacing:0.8px;
        }
        
        input{
            width:100%;
            padding:15px 18px;
            border:2px solid #e2e8f0;
            border-radius:12px;
            font-size:15px;
            font-family:'Poppins',sans-serif;
            transition:all 0.3s ease;
            background:#f7fafc;
        }
        
        input:focus{
            outline:none;
            border-color:#8b5cf6;
            background:white;
            box-shadow:0 0 0 4px rgba(139,92,246,0.12);
            transform:translateY(-2px);
        }
        
        button{
            width:100%;
            padding:16px;
            background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);
            color:white;
            border:none;
            border-radius:12px;
            font-size:16px;
            font-weight:600;
            cursor:pointer;
            margin-top:10px;
            transition:all 0.3s ease;
            box-shadow:0 5px 20px rgba(139,92,246,0.5);
            font-family:'Poppins',sans-serif;
            letter-spacing:0.5px;
        }
        
        button:hover{
            transform:translateY(-4px);
            box-shadow:0 8px 25px rgba(139,92,246,0.7);
        }
        
        button:active{
            transform:translateY(-2px);
        }
        
        .alert{
            padding:14px 18px;
            border-radius:12px;
            margin-bottom:22px;
            font-size:14px;
            font-weight:500;
            animation:shake 0.5s;
        }
        
        @keyframes shake{
            0%,100%{transform:translateX(0)}
            25%{transform:translateX(-8px)}
            75%{transform:translateX(8px)}
        }
        
        .alert.error{
            background:#fed7d7;
            color:#c53030;
            border-left:5px solid #fc8181;
        }
        
        .register-link{
            text-align:center;
            margin-top:28px;
            color:#718096;
            font-size:14px;
        }
        
        .register-link a{
            color:#8b5cf6;
            text-decoration:none;
            font-weight:700;
            transition:0.3s;
        }
        
        .register-link a:hover{
            color:#7c3aed;
            text-decoration:underline;
        }
        
        .divider{
            text-align:center;
            margin:25px 0;
            color:#cbd5e0;
            font-size:13px;
            position:relative;
        }
        
        .divider::before,.divider::after{
            content:'';
            position:absolute;
            top:50%;
            width:40%;
            height:1px;
            background:#e2e8f0;
        }
        
        .divider::before{left:0}
        .divider::after{right:0}
        
        @media(max-width:500px){
            .container{padding:40px 25px}
            h2{font-size:26px}
            .logo{font-size:60px}
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🐾</div>
        <h2>Welcome Back!</h2>
        <p class="subtitle">Login to manage your pet's grooming</p>
        
        <?= $msg ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="enter email" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" name="login">Login to Account</button>
        </form>
        
        <div class="divider">OR</div>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register Now</a>
        </div>
    </div>
</body>
</html>