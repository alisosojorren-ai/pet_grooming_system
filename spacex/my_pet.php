<?php
require '../includes/config.php';
require '../includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pets = $conn->query("SELECT * FROM pets WHERE user_id = $user_id ORDER BY id DESC");

// Delete pet
if(isset($_GET['delete'])) {
    $pet_id = intval($_GET['delete']);
    $conn->query("DELETE FROM pets WHERE id = $pet_id AND user_id = $user_id");
    header("Location: my_pet.php?deleted=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pets - PetCare Grooming</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
       .container{max-width:1200px;margin:40px auto;padding:0 20px}
       .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px}
       .header h2{font-size:32px;color:#2d3748}
       .btn-add{background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:600;transition:0.3s;box-shadow:0 4px 15px rgba(139,92,246,0.4)}
       .btn-add:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(139,92,246,0.6)}
       .pets-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:25px}
       .pet-card{background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);transition:0.3s}
       .pet-card:hover{transform:translateY(-8px);box-shadow:0 12px 30px rgba(0,0,0,0.15)}
       .pet-image{width:100%;height:220px;background:linear-gradient(135deg,#fbbf24,#f59e0b);display:flex;align-items:center;justify-content:center;font-size:80px;position:relative}
       .pet-image img{width:100%;height:100%;object-fit:cover}
       .pet-details{padding:25px}
       .pet-name{font-size:22px;font-weight:700;margin-bottom:8px;color:#2d3748}
       .pet-breed{color:#718096;font-size:14px;margin-bottom:15px}
       .pet-info{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
       .info-item{background:#f7fafc;padding:10px;border-radius:8px}
       .info-label{font-size:11px;color:#a0aec0;text-transform:uppercase;font-weight:600;margin-bottom:3px}
       .info-value{font-size:14px;color:#2d3748;font-weight:600}
       .pet-notes{background:#fff5f5;padding:12px;border-radius:8px;border-left:3px solid #fc8181;margin-bottom:15px}
       .pet-notes-title{font-size:12px;color:#c53030;font-weight:600;margin-bottom:5px}
       .pet-notes-text{font-size:13px;color:#742a2a}
       .card-actions{display:flex;gap:10px}
       .btn{flex:1;padding:12px;border-radius:8px;text-align:center;text-decoration:none;font-weight:600;font-size:14px;transition:0.3s}
       .btn-book{background:#8b5cf6;color:white}
       .btn-book:hover{background:#7c3aed}
       .btn-delete{background:#fee;color:#c53030}
       .btn-delete:hover{background:#fc8181;color:white}
       .empty-state{text-align:center;padding:60px 20px;background:white;border-radius:16px}
       .empty-icon{font-size:80px;margin-bottom:20px;opacity:0.5}
       .empty-state h3{font-size:24px;margin-bottom:10px;color:#4a5568}
       .empty-state p{color:#718096;margin-bottom:25px}
       .back-link{display:inline-block;margin-bottom:20px;color:#8b5cf6;text-decoration:none;font-weight:600}
       .back-link:hover{text-decoration:underline}
        @media(max-width:768px){.pets-grid{grid-template-columns:1fr}.header{flex-direction:column;align-items:flex-start;gap:15px}}
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
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h2>My Pets 🐕</h2>
            <a href="add_pet.php" class="btn-add">+ Add New Pet</a>
        </div>

        <?php if(isset($_GET['deleted'])):?>
            <div class="alert success" style="padding:14px 18px;border-radius:12px;margin-bottom:20px;background:#c6f6d5;color:#22543d;border-left:4px solid #68d391">Pet deleted successfully!</div>
        <?php endif;?>

        <?php if($pets->num_rows > 0):?>
            <div class="pets-grid">
                <?php while($pet = $pets->fetch_assoc()):?>
                <div class="pet-card">
                    <div class="pet-image">
                        <?php if($pet['photo']!= 'default_pet.jpg' && file_exists('../assets/images/uploads/pets/'.$pet['photo'])):?>
                            <img src="../assets/images/uploads/pets/<?= $pet['photo']?>" alt="<?= htmlspecialchars($pet['pet_name'])?>">
                        <?php else:?>
                            🐕
                        <?php endif;?>
                    </div>
                    <div class="pet-details">
                        <h3 class="pet-name"><?= htmlspecialchars($pet['pet_name'])?></h3>
                        <p class="pet-breed"><?= htmlspecialchars($pet['breed'])?></p>

                        <div class="pet-info">
                            <div class="info-item">
                                <div class="info-label">Age</div>
                                <div class="info-value"><?= $pet['age']?> years</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Weight</div>
                                <div class="info-value"><?= $pet['weight']?> kg</div>
                            </div>
                        </div>

                        <?php if(!empty($pet['allergies'])):?>
                        <div class="pet-notes">
                            <div class="pet-notes-title">⚠️ ALLERGIES</div>
                            <div class="pet-notes-text"><?= htmlspecialchars($pet['allergies'])?></div>
                        </div>
                        <?php endif;?>

                        <?php if(!empty($pet['notes'])):?>
                        <div class="pet-notes" style="background:#f0fff4;border-left-color:#68d391">
                            <div class="pet-notes-title" style="color:#22543d">📝 NOTES</div>
                            <div class="pet-notes-text" style="color:#1a202c"><?= htmlspecialchars($pet['notes'])?></div>
                        </div>
                        <?php endif;?>

                        <div class="card-actions">
                            <a href="book_appointment.php?pet_id=<?= $pet['id']?>" class="btn btn-book">Book Grooming</a>
                            <a href="?delete=<?= $pet['id']?>" class="btn btn-delete" onclick="return confirm('Delete <?= htmlspecialchars($pet['pet_name'])?>?')">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endwhile;?>
            </div>
        <?php else:?>
            <div class="empty-state">
                <div class="empty-icon">🐾</div>
                <h3>No Pets Yet</h3>
                <p>Add your first pet to start booking grooming appointments</p>
                <a href="add_pet.php" class="btn-add">+ Add Your First Pet</a>
            </div>
        <?php endif;?>
    </div>
</body>
</html>
