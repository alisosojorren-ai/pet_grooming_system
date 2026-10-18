<?php
require '../includes/config.php';
require '../includes/functions.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$msg = '';

if(isset($_POST['add_groomer'])) {
    $name = sanitize($_POST['groomer_name']);
    $specialization = sanitize($_POST['specialization']);
    $photo = sanitize($_POST['photo']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if(!$name) {
        $msg = "<div class='alert error'>Please provide a groomer name.</div>";
    } else {
        $result = $conn->query("INSERT INTO groomers (name, specialization, photo, is_active) VALUES ('$name', '$specialization', '$photo', $is_active)");
        if($result) {
            $msg = "<div class='alert success'>Groomer added successfully.</div>";
        } else {
            $msg = "<div class='alert error'>Unable to add groomer: " . htmlspecialchars($conn->error) . "</div>";
        }
    }
}

if(isset($_POST['delete_groomer'])) {
    $groomer_id = intval($_POST['groomer_id']);
    $conn->query("DELETE FROM groomers WHERE id = $groomer_id");
    $msg = "<div class='alert success'>Groomer deleted.</div>";
}

if(isset($_POST['toggle_active'])) {
    $groomer_id = intval($_POST['groomer_id']);
    $current = intval($_POST['current_status']);
    $new_status = $current ? 0 : 1;
    $conn->query("UPDATE groomers SET is_active = $new_status WHERE id = $groomer_id");
    $msg = "<div class='alert success'>Groomer status updated.</div>";
}

$groomers = $conn->query("SELECT * FROM groomers ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Groomers - Admin</title>
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
        .toggle-btn{padding:12px 20px;background:#8b5cf6;color:white;border:none;border-radius:12px;cursor:pointer;font-weight:700;transition:0.2s}
        .toggle-btn:hover{background:#7c3aed}
        .toggle-form{display:none;margin-bottom:25px;}
        .card{background:white;padding:30px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
        .alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500}
        .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
        .alert.error{background:#fed7d7;color:#c53030;border-left:4px solid #fc8181}
        .table-card{background:white;padding:30px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05);overflow-x:auto}
        table{width:100%;border-collapse:collapse}
        th,td{padding:15px;text-align:left;border-bottom:1px solid #f1f5f9}
        th{background:#f8fafc;font-weight:700;color:#475569;font-size:13px;text-transform:uppercase;letter-spacing:0.5px}
        tr:hover{background:#f8fafc}
        .danger-btn,.secondary-btn{padding:8px 14px;border:none;border-radius:10px;cursor:pointer;font-weight:600}
        .danger-btn{background:#f87171;color:white}
        .secondary-btn{background:#93c5fd;color:#1e40af}
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
            <a href="reviews.php" class="menu-item">
                <span class="menu-item-icon">⭐</span>
                <span>Reviews</span>
            </a>
            <a href="manage_groomers.php" class="menu-item active">
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
            <h1>Manage Groomers</h1>
            <button type="button" id="groomerToggleBtn" class="toggle-btn" onclick="toggleGroomerForm()">Add Groomer</button>
        </div>

        <?= $msg ?>

        <div id="groomerForm" class="toggle-form card" style="margin-bottom:25px;">
            <h2 style="font-size:20px;margin-bottom:15px;color:#1e293b">Add New Groomer</h2>
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;align-items:end;">
                <div>
                    <label style="display:block;margin-bottom:8px;color:#475569;font-weight:700;font-size:13px">Name</label>
                    <input type="text" name="groomer_name" style="width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:12px;background:#f8fafc" required>
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;color:#475569;font-weight:700;font-size:13px">Specialty</label>
                    <input type="text" name="specialization" style="width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:12px;background:#f8fafc">
                </div>
                <div>
                    <label style="display:block;margin-bottom:8px;color:#475569;font-weight:700;font-size:13px">Photo URL</label>
                    <input type="text" name="photo" style="width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:12px;background:#f8fafc" placeholder="Optional image URL">
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <label style="font-weight:700;color:#475569;font-size:13px">Active</label>
                    <input type="checkbox" name="is_active" checked>
                </div>
                <div style="display:flex;justify-content:flex-end;grid-column:1/3;">
                    <button type="submit" name="add_groomer" style="padding:12px 24px;background:#8b5cf6;color:white;border:none;border-radius:12px;cursor:pointer;font-weight:700">Add Groomer</button>
                </div>
            </form>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Specialty</th>
                        <th>Photo</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($groomers && $groomers->num_rows > 0): ?>
                        <?php while($groomer = $groomers->fetch_assoc()): ?>
                        <tr>
                            <td><?= $groomer['id'] ?></td>
                            <td><?= htmlspecialchars($groomer['name']) ?></td>
                            <td><?= htmlspecialchars($groomer['specialization'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($groomer['photo'] ?? '-') ?></td>
                            <td><?= $groomer['is_active'] ? 'Active' : 'Inactive' ?></td>
                            <td style="display:flex;gap:10px;flex-wrap:wrap">
                                <form method="post" style="display:inline" onsubmit="return confirm('Change groomer status?');">
                                    <input type="hidden" name="groomer_id" value="<?= $groomer['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= intval($groomer['is_active']) ?>">
                                    <button type="submit" name="toggle_active" class="secondary-btn"><?= $groomer['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                </form>
                                <form method="post" style="display:inline" onsubmit="return confirm('Delete this groomer?');">
                                    <input type="hidden" name="groomer_id" value="<?= $groomer['id'] ?>">
                                    <button type="submit" name="delete_groomer" class="danger-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;color:#64748b;padding:30px">No groomers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function toggleGroomerForm() {
            const form = document.getElementById('groomerForm');
            const button = document.getElementById('groomerToggleBtn');
            const isVisible = form.style.display === 'block';
            form.style.display = isVisible ? 'none' : 'block';
            button.textContent = isVisible ? 'Add Groomer' : 'Hide Form';
        }
    </script>
</body>
</html>
