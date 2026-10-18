<?php
require 'includes/config.php';
require 'includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

if(isset($_POST['add_pet'])) {
    $pet_name = sanitize($_POST['pet_name']);
    $breed = sanitize($_POST['breed']);
    $age = intval($_POST['age']);
    $weight = floatval($_POST['weight']);
    $allergies = sanitize($_POST['allergies']);
    $notes = sanitize($_POST['notes']);

    // Handle image upload
    $photo = 'default_pet.jpg';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(in_array($ext, $allowed)) {
            $new_name = 'pet_'.$user_id.'_'.time().'.'.$ext;
            $upload_path = 'assets/images/uploads/pets/'.$new_name;

            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo = $new_name;
            }
        }
    }

    $sql = "INSERT INTO pets (user_id, pet_name, breed, age, weight, allergies, notes, photo)
            VALUES ($user_id, '$pet_name', '$breed', $age, $weight, '$allergies', '$notes', '$photo')";

    if($conn->query($sql)) {
        header("Location: client/my_pet.php?added=1");
        exit();
    } else {
        $msg = "<div class='alert error'>Error: ".$conn->error."</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Pet - PetCare Grooming</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
       .card{background:white;padding:40px;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
       .card h2{font-size:28px;margin-bottom:10px;color:#2d3748}
       .subtitle{color:#718096;margin-bottom:30px;font-size:14px}
       .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
       .form-group{margin-bottom:20px}
       .form-group.full{grid-column:1/-1}
        label{color:#4a5568;font-size:13px;font-weight:600;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px}
        input,textarea,select{width:100%;padding:14px 16px;border:2px solid #e2e8f0;border-radius:10px;font-size:15px;font-family:'Poppins',sans-serif;transition:0.3s;background:#f7fafc}
        input:focus,textarea:focus,select:focus{outline:none;border-color:#8b5cf6;background:white;box-shadow:0 0 0 4px rgba(139,92,246,0.1)}
        textarea{resize:vertical;min-height:100px}
       .file-upload{border:2px dashed #cbd5e0;border-radius:10px;padding:30px;text-align:center;cursor:pointer;transition:0.3s;background:#f7fafc}
       .file-upload:hover{border-color:#8b5cf6;background:#f3e8ff}
       .file-upload input{display:none}
       .file-upload-icon{font-size:40px;margin-bottom:10px}
        button{width:100%;padding:16px;background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white;border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;transition:0.3s;box-shadow:0 4px 15px rgba(139,92,246,0.4)}
        button:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(139,92,246,0.6)}
       .alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500}
       .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
       .alert.error{background:#fed7d7;color:#c53030;border-left:4px solid #fc8181}
       .alert a{color:#7c3aed;font-weight:600;text-decoration:none}
       .back-link{display:inline-block;margin-bottom:20px;color:#8b5cf6;text-decoration:none;font-weight:600}
       .back-link:hover{text-decoration:underline}
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
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="card">
            <h2>Add New Pet 🐕</h2>
            <p class="subtitle">Register your pet to book grooming appointments</p>

            <?= $msg?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Pet Name *</label>
                        <input type="text" name="pet_name" placeholder="Brownie" required>
                    </div>

                    <div class="form-group">
                        <label>Breed *</label>
                        <input type="text" name="breed" placeholder="Shih Tzu" required>
                    </div>

                    <div class="form-group">
                        <label>Age (years)</label>
                        <input type="number" name="age" placeholder="3" min="0" max="30">
                    </div>

                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" name="weight" placeholder="5.5" step="0.1" min="0">
                    </div>

                    <div class="form-group full">
                        <label>Allergies</label>
                        <textarea name="allergies" placeholder="e.g., Allergic to chicken, lavender shampoo"></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Special Notes</label>
                        <textarea name="notes" placeholder="e.g., Aggressive with other dogs, needs muzzle"></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Pet Photo</label>
                        <label class="file-upload">
                            <input type="file" name="photo" accept="image/*" onchange="previewImage(event)">
                            <div class="file-upload-icon">📷</div>
                            <div>Click to upload photo</div>
                            <small style="color:#718096">JPG, PNG or GIF (Max 5MB)</small>
                        </label>
                    </div>
                </div>

                <button type="submit" name="add_pet">Add Pet</button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if(file) {
                const label = event.target.parentElement;
                label.querySelector('div').innerHTML = '✓ ' + file.name;
            }
        }
    </script>
</body>
</html>