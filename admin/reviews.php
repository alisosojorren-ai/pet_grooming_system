<?php
require '../includes/config.php';
require '../includes/functions.php';

if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];

// Ensure review columns exist
$ratingCol = $conn->query("SHOW COLUMNS FROM appointments LIKE 'rating'");
if($ratingCol && $ratingCol->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN rating TINYINT(1) DEFAULT NULL");
}
$reviewCol = $conn->query("SHOW COLUMNS FROM appointments LIKE 'review'");
if($reviewCol && $reviewCol->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN review TEXT DEFAULT NULL");
}

$review_count = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE rating IS NOT NULL OR (review IS NOT NULL AND review != '')")->fetch_assoc()['cnt'];

$reviews = $conn->query("SELECT a.*, u.fullname, p.pet_name, s.service_name, g.name as groomer_name
                        FROM appointments a
                        JOIN users u ON a.user_id = u.id
                        JOIN pets p ON a.pet_id = p.id
                        JOIN services s ON a.service_id = s.id
                        JOIN groomers g ON a.groomer_id = g.id
                        WHERE a.rating IS NOT NULL OR (a.review IS NOT NULL AND a.review != '')
                        ORDER BY a.appointment_date DESC, a.start_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Admin</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:#f1f5f9;color:#1e293b}
       .sidebar{position:fixed;left:0;top:0;width:260px;height:100vh;background:linear-gradient(180deg,#1e293b 0%,#0f172a 100%);padding:30px 0;overflow-y:auto}
       .sidebar-logo{text-align:center;padding:0 20px 30px;border-bottom:1px solid #334155}
       .sidebar-logo h2{color:white;font-size:24px;font-weight:800}
       .sidebar-logo span{font-size:40px;display:block;margin-bottom:10px}
       .sidebar-menu{padding:20px 0}
       .menu-item{display:flex;align-items:center;gap:15px;padding:15px 30px;color:#cbd5e1;text-decoration:none;transition:0.3s;font-weight:500}
       .menu-item:hover,.menu-item.active{background:#334155;color:white;border-left:4px solid #8b5cf6}
       .menu-item-icon{font-size:22px}
       .main-content{margin-left:260px;padding:30px}
       .topbar{background:white;padding:20px 30px;border-radius:16px;margin-bottom:30px;box-shadow:0 2px 10px rgba(0,0,0,0.05)}
       .topbar h1{font-size:28px;font-weight:800;color:#1e293b}
       .alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500}
       .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
       .card{background:white;padding:30px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05);margin-bottom:20px}
       .card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
       .card-title{font-size:20px;font-weight:700;color:#1e293b}
       .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:25px}
       .stat-card{background:#ffffff;padding:25px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05)}
       .stat-icon{font-size:30px;margin-bottom:10px}
       .stat-name{font-size:13px;color:#64748b;text-transform:uppercase;font-weight:700;letter-spacing:0.5px;margin-bottom:8px}
       .stat-value{font-size:32px;font-weight:800;color:#8b5cf6}
       .review-list{display:grid;gap:20px}
       .review-card{background:#ffffff;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05);padding:25px}
       .review-header{display:flex;justify-content:space-between;align-items:start;gap:15px;margin-bottom:15px}
       .review-title{font-size:18px;font-weight:700;color:#1e293b}
       .review-meta{font-size:13px;color:#64748b;line-height:1.6}
       .review-stars{color:#f6ad55;font-size:18px;margin-bottom:12px}
       .review-text{font-size:14px;color:#334155;line-height:1.7;white-space:pre-wrap}
       .no-reviews{padding:40px;text-align:center;color:#64748b}
        @media(max-width:968px){.sidebar{width:80px}.menu-item span{display:none}.main-content{margin-left:80px}}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <span>🐾</span>
            <h2>PetCare Admin</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <span class="menu-item-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="appointments.php" class="menu-item">
                <span class="menu-item-icon">📅</span>
                <span>Appointments</span>
            </a>
            <a href="manage_pets.php" class="menu-item">
                <span class="menu-item-icon">🐕</span>
                <span>Manage Pets</span>
            </a>
            <a href="manage_users.php" class="menu-item">
                <span class="menu-item-icon">👥</span>
                <span>Manage Users</span>
            </a>
            <a href="manage_services.php" class="menu-item">
                <span class="menu-item-icon">💈</span>
                <span>Services</span>
            </a>
            <a href="manage_groomers.php" class="menu-item">
                <span class="menu-item-icon">✂️</span>
                <span>Groomers</span>
            </a>
            <a href="reviews.php" class="menu-item active">
                <span class="menu-item-icon">⭐</span>
                <span>Reviews</span>
            </a>
            <a href="../logout.php?redirect=admin/login.php" class="menu-item">
                <span class="menu-item-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <h1>Client Reviews</h1>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-name">Total Reviews</div>
                <div class="stat-value"><?= $review_count ?></div>
            </div>
        </div>

        <div class="review-list">
            <?php if($reviews && $reviews->num_rows > 0): ?>
                <?php while($row = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <div class="review-title"><?= htmlspecialchars($row['service_name']) ?> — <?= htmlspecialchars($row['pet_name']) ?></div>
                                <div class="review-meta">Client: <?= htmlspecialchars($row['fullname']) ?> | Groomer: <?= htmlspecialchars($row['groomer_name']) ?> | <?= date('M d, Y', strtotime($row['appointment_date'])) ?></div>
                            </div>
                            <div class="review-stars"><?= str_repeat('★', intval($row['rating'])) ?><?= str_repeat('☆', 5 - intval($row['rating'])) ?></div>
                        </div>
                        <div class="review-text"><?= nl2br(htmlspecialchars($row['review'])) ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card no-reviews">No reviews or ratings have been submitted yet.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
