<?php
require '../includes/config.php';
require '../includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Get user details
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Handle profile update
if(isset($_POST['update_profile'])) {
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);

    // Check if email changed and already exists
    if($email!= $user['email']) {
        $check = $conn->query("SELECT id FROM users WHERE email = '$email' AND id!= $user_id");
        if($check->num_rows > 0) {
            $msg = "<div class='alert error'>Email already taken by another account!</div>";
        } else {
            $sql = "UPDATE users SET fullname='$fullname', email='$email', phone='$phone' WHERE id=$user_id";
            if($conn->query($sql)) {
                $_SESSION['fullname'] = $fullname;
                $_SESSION['email'] = $email;
                $msg = "<div class='alert success'>Profile updated successfully!</div>";
                $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
            }
        }
    } else {
        $sql = "UPDATE users SET fullname='$fullname', phone='$phone' WHERE id=$user_id";
        if($conn->query($sql)) {
            $_SESSION['fullname'] = $fullname;
            $msg = "<div class='alert success'>Profile updated successfully!</div>";
        }
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if(!password_verify($current_password, $user['password'])) {
        $msg = "<div class='alert error'>Current password is incorrect!</div>";
    } elseif($new_password!== $confirm_password) {
        $msg = "<div class='alert error'>New passwords do not match!</div>";
    } elseif(strlen($new_password) < 6) {
        $msg = "<div class='alert error'>Password must be at least 6 characters!</div>";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE id=$user_id");
        $msg = "<div class='alert success'>Password changed successfully!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PetCare Grooming</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:#f5f7fb;color:#2d3748}
     .navbar{background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);padding:20px 40px;box-shadow:0 4px 20px rgba(139,92,246,0.3)}
     .nav-content{max-width:1400px;margin:0 auto;display:flex;justify-content:space-between;align-items:center}
     .logo{display:flex;align-items:center;gap:12px;color:white;font-size:24px;font-weight:700}
     .logo span{font-size:32px}
     .user-menu{display:flex;align-items:center;gap:20px}
     .user-name{color:white;font-weight:500}
     .logout-btn{background:rgba(255,255,255,0.2);color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:500;transition:0.3s}
     .logout-btn:hover{background:rgba(255,255,255,0.3)}
     .container{max-width:800px;margin:40px auto;padding:0 20px}
     .card{background:white;padding:40px;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.08);margin-bottom:20px}
     .card h2{font-size:28px;margin-bottom:10px;color:#2d3748}
     .subtitle{color:#718096;margin-bottom:30px;font-size:14px}
     .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
     .form-group{margin-bottom:20px}
     .form-group.full{grid-column:1/-1}
        label{color:#4a5568;font-size:13px;font-weight:600;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px}
        input{width:100%;padding:14px 16px;border:2px solid #e2e8f0;border-radius:10px;font-size:15px;font-family:'Poppins',sans-serif;transition:0.3s;background:#f7fafc}
        input:focus{outline:none;border-color:#8b5cf6;background:white;box-shadow:0 0 0 4px rgba(139,92,246,0.1)}
        button{width:100%;padding:16px;background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white;border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;transition:0.3s;box-shadow:0 4px 15px rgba(139,92,246,0.4)}
        button:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(139,92,246,0.6)}
     .alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500}
     .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
     .alert.error{background:#fed7d7;color:#c53030;border-left:4px solid #fc8181}
     .back-link{display:inline-block;margin-bottom:20px;color:#8b5cf6;text-decoration:none;font-weight:600}
     .back-link:hover{text-decoration:underline}
     .divider{height:1px;background:#e2e8f0;margin:30px 0}
        @media(max-width:768px){.form-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <span>🐾</span>
                PetCare Grooming
            </div>
            <div class="user-menu">
                <span class="user-name">Hi, <?= htmlspecialchars(explode(' ', $_SESSION['fullname'])[0])?>!</span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <!-- Profile Info -->
        <div class="card">
            <h2>My Profile 👤</h2>
            <p class="subtitle">Update your personal information</p>

            <?= $msg?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname'])?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'])?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'])?>" required>
                    </div>
                </div>

                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card">
            <h2>Change Password 🔒</h2>
            <p class="subtitle">Keep your account secure</p>

            <form method="POST">
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>

                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
                </div>

                <button type="submit" name="change_password">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>