<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

// Handle status update
if(isset($_POST['update_status'])) {
    ensureCompletedAtColumn();
    $id = intval($_POST['app_id']);
    $status = sanitize($_POST['status']);
    $sql = "UPDATE appointments SET status='$status'";
    if($status === 'completed') {
        $sql .= ", completed_at = CURRENT_TIMESTAMP()";
    } else {
        $sql .= ", completed_at = NULL";
    }
    $sql .= " WHERE id=$id";
    $conn->query($sql);
    $msg = "<div class='alert success'>Appointment status updated!</div>";
}

// Get all appointments with filters
$filter = isset($_GET['filter'])? $_GET['filter'] : 'all';
$date_filter = isset($_GET['date'])? $_GET['date'] : '';

$sql = "SELECT a.*, u.fullname, u.phone, p.pet_name, s.service_name, g.name as groomer_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN pets p ON a.pet_id = p.id
        JOIN services s ON a.service_id = s.id
        JOIN groomers g ON a.groomer_id = g.id WHERE 1=1";

if($filter == 'pending') $sql.= " AND a.status = 'pending'";
elseif($filter == 'confirmed') $sql.= " AND a.status = 'confirmed'";
elseif($filter == 'completed') $sql.= " AND a.status = 'completed'";
elseif($filter == 'today') $sql.= " AND a.appointment_date = CURDATE()";

if($date_filter) $sql.= " AND a.appointment_date = '$date_filter'";

$sql.= " ORDER BY a.appointment_date DESC, a.start_time DESC";
$appointments = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Admin</title>
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
       .filter-bar{background:white;padding:20px;border-radius:16px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);display:flex;gap:15px;flex-wrap:wrap;align-items:center}
       .filter-btn{padding:10px 20px;border:2px solid #e2e8f0;border-radius:10px;background:white;text-decoration:none;color:#4a5568;font-weight:600;font-size:14px;transition:0.3s}
       .filter-btn:hover,.filter-btn.active{border-color:#8b5cf6;background:#8b5cf6;color:white}
       .date-input{padding:10px 15px;border:2px solid #e2e8f0;border-radius:10px;font-family:'Poppins',sans-serif}
       .table-card{background:white;padding:30px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05);overflow-x:auto}
       table{width:100%;border-collapse:collapse}
       th{padding:15px;text-align:left;background:#f8fafc;font-weight:700;color:#475569;font-size:13px;text-transform:uppercase;letter-spacing:0.5px}
       td{padding:18px 15px;border-bottom:1px solid #f1f5f9}
       tr:hover{background:#f8fafc}
       .status-badge{padding:6px 14px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;display:inline-block}
       .status-pending{background:#fef3c7;color:#92400e}
       .status-confirmed{background:#d1fae5;color:#065f46}
       .status-completed{background:#dbeafe;color:#1e40af}
       .status-cancelled{background:#f1f5f9;color:#64748b}
       .status-no-show{background:#fee;color:#c53030}
       .action-form{display:inline}
       .status-select{padding:8px 12px;border:2px solid #e2e8f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer}
       .status-select:focus{outline:none;border-color:#8b5cf6}
       .amount{font-weight:700;color:#8b5cf6;font-size:16px}
       .client-info{font-weight:600;color:#1e293b}
       .client-phone{font-size:12px;color:#64748b}
        @media(max-width:968px){
           .sidebar{width:80px}
           .menu-item span{display:none}
           .main-content{margin-left:80px}
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
            <a href="dashboard.php" class="menu-item">
                <span class="menu-item-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="appointments.php" class="menu-item active">
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
            <h1>Manage Appointments</h1>
        </div>

        <?= $msg?>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <a href="?filter=all" class="filter-btn <?= $filter=='all'?'active':''?>">All</a>
            <a href="?filter=today" class="filter-btn <?= $filter=='today'?'active':''?>">Today</a>
            <a href="?filter=pending" class="filter-btn <?= $filter=='pending'?'active':''?>">Pending</a>
            <a href="?filter=confirmed" class="filter-btn <?= $filter=='confirmed'?'active':''?>">Confirmed</a>
            <a href="?filter=completed" class="filter-btn <?= $filter=='completed'?'active':''?>">Completed</a>
            <form method="GET" style="display:flex;gap:10px;margin-left:auto">
                <input type="date" name="date" class="date-input" value="<?= $date_filter?>">
                <button type="submit" class="filter-btn">Filter Date</button>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Pet</th>
                        <th>Service</th>
                        <th>Groomer</th>
                        <th>Date & Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($appointments->num_rows > 0):?>
                        <?php while($row = $appointments->fetch_assoc()):?>
                        <tr>
                            <td><strong>#<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT)?></strong></td>
                            <td>
                                <div class="client-info"><?= htmlspecialchars($row['fullname'])?></div>
                                <div class="client-phone"><?= $row['phone']?></div>
                            </td>
                            <td><?= htmlspecialchars($row['pet_name'])?></td>
                            <td><?= $row['service_name']?></td>
                            <td><?= $row['groomer_name']?></td>
                            <td>
                                <div style="font-weight:600"><?= date('M d, Y', strtotime($row['appointment_date']))?></div>
                                <div style="font-size:12px;color:#64748b"><?= date('h:i A', strtotime($row['start_time']))?></div>
                            </td>
                            <td class="amount">₱<?= number_format($row['total_amount'], 2)?></td>
                            <td><span class="status-badge status-<?= $row['status']?>"><?= ucfirst($row['status'])?></span></td>
                            <td>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="app_id" value="<?= $row['id']?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="">Change</option>
                                        <option value="confirmed">Confirm</option>
                                        <option value="completed">Complete</option>
                                        <option value="cancelled">Cancel</option>
                                        <option value="no-show">No Show</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                        </tr>
                        <?php endwhile;?>
                    <?php else:?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:40px;color:#94a3b8">No appointments found</td>
                        </tr>
                    <?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>