<?php
require 'includes/config.php';

$msg = "";

if(isset($_POST['register'])) {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if($password !== $confirm_password) {
        $msg = "<div style='color:red;padding:10px;background:#fee;border-radius:5px;margin:10px 0'>Passwords do not match!</div>";
    } elseif(strlen($password) < 6) {
        $msg = "<div style='color:red;padding:10px;background:#fee;border-radius:5px;margin:10px 0'>Password must be at least 6 characters!</div>";
    } else {
        // Check if email exists
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        
        if($check->num_rows > 0) {
            $msg = "<div style='color:red;padding:10px;background:#fee;border-radius:5px;margin:10px 0'>Email already registered!</div>";
        } else {
            // Hash password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (fullname, email, phone, password) VALUES ('$fullname', '$email', '$phone', '$hashed_password')";
            
            if($conn->query($sql)) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['email'] = $email;

                header("Location: dashboard.php");
                exit();
            } else {
                $msg = "<div style='color:red;padding:10px;background:#fee;border-radius:5px;margin:10px 0'>Error: ".$conn->error."</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Pet Grooming</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family:'Segoe UI',sans-serif;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }
        .container{
            background:white;
            padding:40px;
            border-radius:15px;
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
            max-width:450px;
            width:100%;
        }
        h2{color:#333;margin-bottom:10px;text-align:center}
        .subtitle{color:#666;text-align:center;margin-bottom:30px;font-size:14px}
        input{
            width:100%;
            padding:12px;
            margin:8px 0;
            border:2px solid #e0e0e0;
            border-radius:8px;
            font-size:15px;
            transition:0.3s;
        }
        input:focus{
            outline:none;
            border-color:#8b5cf6;
        }
        label{color:#555;font-size:14px;font-weight:600;display:block;margin-top:10px}
        button{
            width:100%;
            padding:14px;
            background:#8b5cf6;
            color:white;
            border:none;
            border-radius:8px;
            font-size:16px;
            font-weight:bold;
            cursor:pointer;
            margin-top:20px;
            transition:0.3s;
        }
        button:hover{background:#7c3aed;transform:translateY(-2px)}
        .login-link{
            text-align:center;
            margin-top:20px;
            color:#666;
            font-size:14px;
        }
        .login-link a{color:#8b5cf6;text-decoration:none;font-weight:bold}
    </style>
</head>
<body>
    <div class="container">
        <h2>🐾 Create Account</h2>
        <p class="subtitle">Join PetCare Grooming today</p>
        
        <?= $msg ?>
        
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="fullname" placeholder="Enter Name" required>
            
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Email" required>
            
            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="mobile number" required>
            
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter Password" required>
            
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter password" required>
            
            <button type="submit" name="register">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>