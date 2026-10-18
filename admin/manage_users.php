<?php
require '../includes/config.php';
require '../includes/functions.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$msg = '';

if(isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $conn->query("DELETE FROM users WHERE id = $user_id");
    $msg = "<div class='alert success'>User deleted successfully.</div>";
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
        .topbar{background:white;padding:20px 30px;border-radius:16px;margin-bottom:30px;box-shadow:0 2px 10px rgba(0,0,0,0.05);display:flex;justify-content:space-between;align-items:center}
        .topbar h1{font-size:28px;font-weight:800;color:#1e293b}
        .alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500}
        .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
        .table-card{background:white;padding:30px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05);overflow-x:auto}
        table{width:100%;border-collapse:collapse}
        th,td{padding:15px;text-align:left;border-bottom:1px solid #f1f5f9}
        th{background:#f8fafc;font-weight:700;color:#475569;font-size:13px;text-transform:uppercase;letter-spacing:0.5px}
        tr:hover{background:#f8fafc}
        .danger-btn{padding:8px 14px;background:#f87171;color:white;border:none;border-radius:10px;cursor:pointer;font-weight:600}
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
            <a href="manage_users.php" class="menu-item active">
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

    <div class="main-content">
        <div class="topbar">
            <h1>Manage Users</h1>
        </div>

        <?= $msg ?>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($users && $users->num_rows > 0): ?>
                        <?php while($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['fullname']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user" class="danger-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;color:#64748b;padding:30px">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
