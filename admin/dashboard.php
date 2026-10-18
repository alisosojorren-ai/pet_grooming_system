<?php
require '../includes/config.php';
require '../includes/functions.php';

// Auth check
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];

// Get stats
ensureCompletedAtColumn();
$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$total_pets = $conn->query("SELECT COUNT(*) as cnt FROM pets")->fetch_assoc()['cnt'];
$total_bookings = $conn->query("SELECT COUNT(*) as cnt FROM appointments")->fetch_assoc()['cnt'];
$pending_bookings = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status='pending'")->fetch_assoc()['cnt'];
$completed_today = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status='completed' AND COALESCE(DATE(completed_at), appointment_date)=CURDATE()")->fetch_assoc()['cnt'];

// Revenue calculations
$daily_revenue = $conn->query("SELECT SUM(total_amount) as total FROM appointments
                         WHERE status='completed'
                         AND COALESCE(DATE(completed_at), appointment_date) = CURDATE()
                         AND total_amount IS NOT NULL")->fetch_assoc()['total'] ?? 0;
$weekly_revenue = $conn->query("SELECT SUM(total_amount) as total FROM appointments
                         WHERE status='completed'
                         AND COALESCE(DATE(completed_at), appointment_date) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
                         AND total_amount IS NOT NULL")->fetch_assoc()['total'] ?? 0;
$monthly_revenue = $conn->query("SELECT SUM(total_amount) as total FROM appointments
                         WHERE status='completed'
                         AND MONTH(COALESCE(DATE(completed_at), appointment_date))=MONTH(CURDATE())
                         AND YEAR(COALESCE(DATE(completed_at), appointment_date))=YEAR(CURDATE())
                         AND total_amount IS NOT NULL")->fetch_assoc()['total'] ?? 0;

// Daily income entries
$daily_income = $conn->query("SELECT a.*, u.fullname, p.pet_name, s.service_name, g.name as groomer_name
                        FROM appointments a
                        JOIN users u ON a.user_id = u.id
                        JOIN pets p ON a.pet_id = p.id
                        JOIN services s ON a.service_id = s.id
                        JOIN groomers g ON a.groomer_id = g.id
                        WHERE a.status='completed'
                          AND COALESCE(DATE(a.completed_at), a.appointment_date) = CURDATE()
                        ORDER BY COALESCE(a.completed_at, a.appointment_date) DESC");

// Recent appointments
$recent = $conn->query("SELECT a.*, u.fullname, p.pet_name, s.service_name, g.name as groomer_name
                        FROM appointments a
                        JOIN users u ON a.user_id = u.id
                        JOIN pets p ON a.pet_id = p.id
                        JOIN services s ON a.service_id = s.id
                        JOIN groomers g ON a.groomer_id = g.id
                        ORDER BY a.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PetCare Grooming</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:#f1f5f9;color:#1e293b}
       .sidebar{
            position:fixed;
            left:0;
            top:0;
            width:260px;
            height:100vh;
            background:linear-gradient(180deg,#1e293b 0%,#0f172a 100%);
            padding:30px 0;
            overflow-y:auto;
        }
       .sidebar-logo{text-align:center;padding:0 20px 30px;border-bottom:1px solid #334155}
       .sidebar-logo h2{color:white;font-size:24px;font-weight:800}
       .sidebar-logo span{font-size:40px;display:block;margin-bottom:10px}
       .sidebar-menu{padding:20px 0}
       .menu-item{
            display:flex;
            align-items:center;
            gap:15px;
            padding:15px 30px;
            color:#cbd5e1;
            text-decoration:none;
            transition:0.3s;
            font-weight:500;
        }
       .menu-item:hover,.menu-item.active{background:#334155;color:white;border-left:4px solid #8b5cf6}
       .menu-item-icon{font-size:22px}
       .main-content{margin-left:260px;padding:30px}
       .topbar{background:white;padding:20px 30px;border-radius:16px;margin-bottom:30px;box-shadow:0 2px 10px rgba(0,0,0,0.05);display:flex;justify-content:space-between;align-items:center}
       .topbar h1{font-size:28px;font-weight:800;color:#1e293b}
       .admin-info{display:flex;align-items:center;gap:15px}
       .admin-avatar{width:45px;height:45px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px}
       .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
       .stat-card{background:white;padding:30px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05);position:relative;overflow:hidden;transition:0.3s}
       .stat-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,0.1)}
       .stat-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:linear-gradient(180deg,#8b5cf6,#7c3aed)}
       .stat-icon{font-size:40px;margin-bottom:15px}
       .stat-number{font-size:36px;font-weight:800;color:#8b5cf6;margin-bottom:5px}
       .stat-label{color:#64748b;font-size:14px;font-weight:600}
       .content-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px}
       .card{background:white;padding:30px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05)}
       .card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;padding-bottom:15px;border-bottom:2px solid #f1f5f9}
       .card-title{font-size:20px;font-weight:700;color:#1e293b}
       .view-all{color:#8b5cf6;text-decoration:none;font-weight:600;font-size:14px}
       .appointment-item{padding:18px;background:#f8fafc;border-radius:12px;margin-bottom:12px;display:flex;gap:15px;align-items:center}
       .app-status{padding:6px 14px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase}
       .status-pending{background:#fef3c7;color:#92400e}
       .status-confirmed{background:#d1fae5;color:#065f46}
       .status-completed{background:#dbeafe;color:#1e40af}
       .quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:15px}
       .action-btn{padding:20px;background:linear-gradient(135deg,#f3e8ff,#e9d5ff);border-radius:12px;text-decoration:none;color:#7c3aed;text-align:center;transition:0.3s}
       .action-btn:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(139,92,246,0.3)}
       .action-btn-icon{font-size:30px;margin-bottom:8px;display:block}
       .action-btn-text{font-weight:600;font-size:14px}
        @media(max-width:968px){
           .sidebar{width:80px}
           .menu-item span{display:none}
           .main-content{margin-left:80px}
           .content-grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <span>🐾</span>
            <h2>PetCare Admin</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
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
            <a href="reviews.php" class="menu-item">
                <span class="menu-item-icon">⭐</span>
                <span>Reviews</span>
            </a>
            <a href="manage_groomers.php" class="menu-item">
                <span class="menu-item-icon">✂️</span>
                <span>Groomers</span>
            </a>
            <a href="../logout.php?redirect=admin/login.php" class="menu-item">
                <span class="menu-item-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h1>Dashboard Overview</h1>
            <div class="admin-info">
                <div>
                    <div style="font-weight:600;color:#1e293b"><?= htmlspecialchars($admin_name)?></div>
                    <div style="font-size:12px;color:#64748b">Administrator</div>
                </div>
                <div class="admin-avatar"><?= strtoupper(substr($admin_name,0,1))?></div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?= $total_users?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🐕</div>
                <div class="stat-number"><?= $total_pets?></div>
                <div class="stat-label">Registered Pets</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number"><?= $total_bookings?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-number"><?= $pending_bookings?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= $completed_today?></div>
                <div class="stat-label">Completed Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💵</div>
                <div class="stat-number">₱<?= number_format($daily_revenue,0)?></div>
                <div class="stat-label">Daily Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🗓️</div>
                <div class="stat-number">₱<?= number_format($weekly_revenue,0)?></div>
                <div class="stat-label">Weekly Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number">₱<?= number_format($monthly_revenue,0)?></div>
                <div class="stat-label">Revenue This Month</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Appointments -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Appointments</h3>
                    <a href="appointments.php" class="view-all">View All →</a>
                </div>
                <?php if($recent->num_rows > 0):?>
                    <?php while($row = $recent->fetch_assoc()):?>
                    <div class="appointment-item">
                        <div style="flex:1">
                            <div style="font-weight:600;margin-bottom:5px"><?= htmlspecialchars($row['pet_name'])?> - <?= $row['service_name']?></div>
                            <div style="font-size:13px;color:#64748b">
                                <?= $row['fullname']?> • <?= date('M d, Y', strtotime($row['appointment_date']))?> at <?= date('h:i A', strtotime($row['start_time']))?>
                            </div>
                            <div style="font-size:12px;color:#94a3b8;margin-top:3px">Groomer: <?= $row['groomer_name']?></div>
                        </div>
                        <span class="app-status status-<?= $row['status']?>"><?= ucfirst($row['status'])?></span>
                    </div>
                    <?php endwhile;?>
                <?php else:?>
                    <p style="text-align:center;color:#94a3b8;padding:40px">No appointments yet</p>
                <?php endif;?>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <a href="appointments.php" class="action-btn">
                        <span class="action-btn-icon">📅</span>
                        <span class="action-btn-text">View Bookings</span>
                    </a>
                    <a href="manage_services.php" class="action-btn">
                        <span class="action-btn-icon">💈</span>
                        <span class="action-btn-text">Add Service</span>
                    </a>
                    <a href="manage_groomers.php" class="action-btn">
                        <span class="action-btn-icon">✂️</span>
                        <span class="action-btn-text">Add Groomer</span>
                    </a>
                    <a href="manage_users.php" class="action-btn">
                        <span class="action-btn-icon">👥</span>
                        <span class="action-btn-text">View Clients</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card" style="margin-top:20px;">
            <div class="card-header">
                <h3 class="card-title">Today's Income Sources</h3>
            </div>
            <?php if($daily_income && $daily_income->num_rows > 0):?>
                <?php while($row = $daily_income->fetch_assoc()):?>
                <div class="appointment-item">
                    <div style="flex:1">
                        <div style="font-weight:600;margin-bottom:4px"><?= htmlspecialchars($row['service_name']) ?> - ₱<?= number_format($row['total_amount'],2) ?></div>
                        <div style="font-size:13px;color:#64748b">
                            <?= htmlspecialchars($row['fullname'])?> - <?= htmlspecialchars($row['pet_name'])?> - <?= htmlspecialchars($row['groomer_name'])?>
                        </div>
                        <div style="font-size:12px;color:#94a3b8;margin-top:4px">Payment: <?= htmlspecialchars($row['payment_method'] ?? 'N/A')?> - <?= htmlspecialchars($row['payment_status'] ?? 'N/A')?></div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:700;color:#1e293b"><?= date('h:i A', strtotime($row['created_at']))?></div>
                        <div style="font-size:12px;color:#64748b">Date: <?= date('M d, Y', strtotime($row['appointment_date']))?></div>
                    </div>
                </div>
                <?php endwhile;?>
            <?php else:?>
                <p style="text-align:center;color:#94a3b8;padding:40px">No completed income entries for today.</p>
            <?php endif;?>
        </div>
</body>
</html>