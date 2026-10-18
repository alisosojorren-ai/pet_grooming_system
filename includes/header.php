<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_check.php';

// Auto-check auth if not admin page
$current_page = basename($_SERVER['PHP_SELF']);
if(!str_contains($_SERVER['PHP_SELF'], '/admin/') && !in_array($current_page, ['index.php','login.php','register.php','services.php'])) {
    checkUserAuth();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME?> - Professional Pet Grooming</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:#f5f7fb;color:#2d3748}

        /* Navbar */
       .navbar{
            background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);
            padding:20px 40px;
            box-shadow:0 4px 20px rgba(139,92,246,0.3);
            position:sticky;
            top:0;
            z-index:100;
        }
       .nav-content{
            max-width:1400px;
            margin:0 auto;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
       .logo{
            display:flex;
            align-items:center;
            gap:12px;
            color:white;
            font-size:24px;
            font-weight:700;
            text-decoration:none;
        }
       .logo span{font-size:32px}
       .user-menu{
            display:flex;
            align-items:center;
            gap:20px;
        }
       .user-name{
            color:white;
            font-weight:500;
        }
       .logout-btn{
            background:rgba(255,255,255,0.2);
            color:white;
            padding:10px 20px;
            border-radius:8px;
            text-decoration:none;
            font-weight:500;
            transition:0.3s;
            backdrop-filter:blur(10px);
        }
       .logout-btn:hover{
            background:rgba(255,255,255,0.3);
            transform:translateY(-2px);
        }

        /* Container */
       .container{
            max-width:1400px;
            margin:0 auto;
            padding:40px 20px;
        }

        /* Alert Messages */
       .alert{
            padding:14px 18px;
            border-radius:12px;
            margin-bottom:20px;
            font-size:14px;
            font-weight:500;
            animation:slideDown 0.5s ease-out;
        }
        @keyframes slideDown{
            from{opacity:0;transform:translateY(-20px)}
            to{opacity:1;transform:translateY(0)}
        }
       .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
       .alert.error{background:#fed7d7;color:#c53030;border-left:4px solid #fc8181}
       .alert.warning{background:#fef3c7;color:#92400e;border-left:4px solid #fbbf24}
       .alert.info{background:#dbeafe;color:#1e40af;border-left:4px solid #60a5fa}

        /* Back Link */
       .back-link{
            display:inline-block;
            margin-bottom:20px;
            color:#8b5cf6;
            text-decoration:none;
            font-weight:600;
            transition:0.3s;
        }
       .back-link:hover{
            text-decoration:underline;
            transform:translateX(-5px);
        }

        /* Responsive */
        @media(max-width:768px){
           .navbar{padding:15px 20px}
           .nav-content{flex-direction:column;gap:15px}
           .container{padding:20px 15px}
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="<?= BASE_URL?>index.php" class="logo">
                <span>🐾</span>
                <?= SITE_NAME?>
            </a>
            <div class="user-menu">
                <?php if(isLoggedIn()):?>
                    <span class="user-name">Hi, <?= htmlspecialchars(explode(' ', $_SESSION['fullname'])[0])?>!</span>
                    <a href="<?= BASE_URL?>logout.php" class="logout-btn">Logout</a>
                <?php else:?>
                    <a href="<?= BASE_URL?>login.php" class="logout-btn">Login</a>
                    <a href="<?= BASE_URL?>register.php" class="logout-btn">Register</a>
                <?php endif;?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php showAlert();?>