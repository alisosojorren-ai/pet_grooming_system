<?php
require 'includes/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// Get stats
$pets_count = $conn->query("SELECT COUNT(*) as cnt FROM pets WHERE user_id = $user_id")->fetch_assoc()['cnt'];

$total_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE user_id = $user_id")->fetch_assoc()['cnt'];

$completed_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE user_id = $user_id AND status='completed'")->fetch_assoc()['cnt'];

// Get user's pets
$pets = $conn->query("SELECT * FROM pets WHERE user_id = $user_id ORDER BY id DESC LIMIT 4");

// Get upcoming appointments
$upcoming = $conn->query("SELECT a.*, p.pet_name, p.photo, s.service_name, s.price, g.name as groomer_name
                          FROM appointments a
                          JOIN pets p ON a.pet_id = p.id
                          JOIN services s ON a.service_id = s.id
                          JOIN groomers g ON a.groomer_id = g.id
                          WHERE a.user_id = $user_id
                          AND a.appointment_date >= CURDATE()
                          AND a.status IN ('pending','confirmed')
                          ORDER BY a.appointment_date, a.start_time
                          LIMIT 5");

// Get recent completed
$recent = $conn->query("SELECT a.*, p.pet_name, s.service_name
                        FROM appointments a
                        JOIN pets p ON a.pet_id = p.id
                        JOIN services s ON a.service_id = s.id
                        WHERE a.user_id = $user_id
                        AND a.status = 'completed'
                        ORDER BY a.appointment_date DESC
                        LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PetCare Grooming</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        *{margin:0;padding:0;box-sizing:border-box}

        body{
            font-family:'Poppins',sans-serif;
            background:#f5f7fb;
            color:#2d3748;
        }

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

        /* Main Container */
       .container{
            max-width:1400px;
            margin:0 auto;
            padding:40px 20px;
        }

        /* Welcome Card */
       .welcome-card{
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            padding:40px;
            border-radius:20px;
            color:white;
            margin-bottom:30px;
            box-shadow:0 10px 40px rgba(102,126,234,0.3);
            position:relative;
            overflow:hidden;
            animation:fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn{
            from{opacity:0;transform:translateY(20px)}
            to{opacity:1;transform:translateY(0)}
        }

       .welcome-card::before{
            content:'🐾';
            position:absolute;
            font-size:200px;
            opacity:0.1;
            right:-50px;
            top:-50px;
            transform:rotate(-15deg);
        }

       .welcome-card h1{
            font-size:32px;
            margin-bottom:10px;
            position:relative;
        }

       .welcome-card p{
            font-size:16px;
            opacity:0.9;
            position:relative;
        }

        /* Stats Grid */
       .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
            gap:20px;
            margin-bottom:30px;
        }

       .stat-card{
            background:white;
            padding:25px;
            border-radius:16px;
            box-shadow:0 4px 15px rgba(0,0,0,0.08);
            transition:0.3s;
            position:relative;
            overflow:hidden;
        }

       .stat-card:hover{
            transform:translateY(-5px);
            box-shadow:0 8px 25px rgba(0,0,0,0.12);
        }

       .stat-card::before{
            content:'';
            position:absolute;
            top:0;
            left:0;
            width:4px;
            height:100%;
            background:linear-gradient(180deg,#8b5cf6,#7c3aed);
        }

       .stat-icon{
            font-size:40px;
            margin-bottom:12px;
        }

       .stat-number{
            font-size:32px;
            font-weight:700;
            color:#8b5cf6;
            margin-bottom:5px;
        }

       .stat-label{
            color:#718096;
            font-size:14px;
            font-weight:500;
        }

        /* Quick Actions */
       .quick-actions{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:15px;
            margin-bottom:30px;
        }

       .action-btn{
            background:white;
            padding:20px;
            border-radius:12px;
            text-decoration:none;
            color:#2d3748;
            display:flex;
            align-items:center;
            gap:15px;
            box-shadow:0 2px 10px rgba(0,0,0,0.06);
            transition:0.3s;
            border:2px solid transparent;
        }

       .action-btn:hover{
            border-color:#8b5cf6;
            transform:translateY(-3px);
            box-shadow:0 6px 20px rgba(139,92,246,0.2);
        }

       .action-icon{
            font-size:30px;
            width:50px;
            height:50px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg,#f3e8ff,#e9d5ff);
            border-radius:10px;
        }

       .action-text h4{
            font-size:15px;
            margin-bottom:3px;
        }

       .action-text p{
            font-size:12px;
            color:#718096;
        }

        /* Content Grid */
       .content-grid{
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:20px;
            margin-bottom:30px;
        }

       .card{
            background:white;
            padding:30px;
            border-radius:16px;
            box-shadow:0 4px 15px rgba(0,0,0,0.08);
        }

       .card-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
            padding-bottom:15px;
            border-bottom:2px solid #f7fafc;
        }

       .card-title{
            font-size:20px;
            font-weight:700;
            color:#2d3748;
        }

       .view-all{
            color:#8b5cf6;
            text-decoration:none;
            font-weight:600;
            font-size:14px;
        }

       .view-all:hover{text-decoration:underline}

        /* Appointment Item */
       .appointment-item{
            padding:18px;
            background:#f7fafc;
            border-radius:12px;
            margin-bottom:12px;
            display:flex;
            gap:15px;
            align-items:center;
            transition:0.3s;
        }

       .appointment-item:hover{
            background:#edf2f7;
            transform:translateX(5px);
        }

       .pet-avatar{
            width:55px;
            height:55px;
            border-radius:12px;
            background:linear-gradient(135deg,#8b5cf6,#7c3aed);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:24px;
            flex-shrink:0;
        }

       .appointment-details{
            flex:1;
        }

       .pet-name{
            font-weight:600;
            font-size:16px;
            margin-bottom:4px;
        }

       .appointment-meta{
            font-size:13px;
            color:#718096;
        }

       .status-badge{
            padding:6px 14px;
            border-radius:20px;
            font-size:12px;
            font-weight:600;
            text-transform:uppercase;
        }

       .status-pending{background:#fef3c7;color:#92400e}
       .status-confirmed{background:#d1fae5;color:#065f46}
       .status-completed{background:#dbeafe;color:#1e40af}

        /* Pet Card */
       .pet-card{
            padding:15px;
            background:#f7fafc;
            border-radius:12px;
            margin-bottom:12px;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:12px;
            text-align:center;
        }

       .pet-avatar-small{
            width:45px;
            height:45px;
            border-radius:10px;
            background:linear-gradient(135deg,#fbbf24,#f59e0b);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
        }

       .pet-info h4{
            font-size:15px;
            margin-bottom:2px;
        }

       .pet-info p{
            font-size:12px;
            color:#718096;
        }

       .empty-state{
            text-align:center;
            padding:40px 20px;
            color:#a0aec0;
        }

       .empty-state-icon{
            font-size:60px;
            margin-bottom:15px;
            opacity:0.5;
        }

        /* Responsive */
        @media(max-width:968px){
           .content-grid{grid-template-columns:1fr}
           .navbar{padding:15px 20px}
           .container{padding:20px 15px}
           .welcome-card{padding:30px 20px}
           .welcome-card h1{font-size:24px}
           .stats-grid{grid-template-columns:repeat(2,1fr)}
        }

        @media(max-width:600px){
           .stats-grid{grid-template-columns:1fr}
           .quick-actions{grid-template-columns:1fr}
           .nav-content{flex-direction:column;gap:15px}
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <span>🐾</span>
                PetCare Grooming
            </div>
            <div class="user-menu">
                <span class="user-name">Hi, <?= htmlspecialchars(explode(' ', $fullname)[0])?>!</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <?php if(isset($_GET['booked']) && $_GET['booked'] == '1'): ?>
            <div style="padding:18px 22px;margin-bottom:20px;border-radius:14px;background:#d1fae5;color:#064e3b;border-left:5px solid #22c55e;box-shadow:0 12px 30px rgba(34,197,94,0.1);font-weight:600">
                Booking successful! Your appointment is now pending confirmation.
            </div>
        <?php endif; ?>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <h1>Welcome back, <?= htmlspecialchars($fullname)?>! 👋</h1>
            <p>Manage your pets' grooming appointments with ease</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <a href="client/my_pet.php" class="stat-card" style="text-decoration:none;color:inherit;display:block">
                <div class="stat-icon">🐕</div>
                <div class="stat-number"><?= $pets_count?></div>
                <div class="stat-label">Registered Pets</div>
            </a>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number"><?= $total_appointments?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= $completed_appointments?></div>
                <div class="stat-label">Completed Services</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="client/add_pet.php" class="action-btn">
                <div class="action-icon">➕</div>
                <div class="action-text">
                    <h4>Add New Pet</h4>
                    <p>Register your pet</p>
                </div>
            </a>
            <a href="book_appointment.php" class="action-btn">
                <div class="action-icon">📅</div>
                <div class="action-text">
                    <h4>Book Appointment</h4>
                    <p>Schedule grooming</p>
                </div>
            </a>
            <a href="client/my_appointment.php" class="action-btn">
                <div class="action-icon">📋</div>
                <div class="action-text">
                    <h4>My Bookings</h4>
                    <p>View all appointments</p>
                </div>
            </a>
            <a href="services.php" class="action-btn">
                <div class="action-icon">💈</div>
                <div class="action-text">
                    <h4>Services</h4>
                    <p>View pricing</p>
                </div>
            </a>
        </div>

        <!-- Upcoming Appointments -->
        <div class="card" style="margin-bottom:30px">
            <div class="card-header">
                <h3 class="card-title">Upcoming Appointments</h3>
                <a href="client/my_appointment.php" class="view-all">View All →</a>
            </div>

            <?php if($upcoming->num_rows > 0):?>
                <?php while($row = $upcoming->fetch_assoc()):?>
                <div class="appointment-item">
                    <div class="pet-avatar">🐾</div>
                    <div class="appointment-details">
                        <div class="pet-name"><?= htmlspecialchars($row['pet_name'])?></div>
                        <div class="appointment-meta">
                            <?= $row['service_name']?> • <?= date('M d, Y', strtotime($row['appointment_date']))?> at <?= date('h:i A', strtotime($row['start_time']))?>
                        </div>
                        <div class="appointment-meta">Groomer: <?= $row['groomer_name']?></div>
                    </div>
                    <span class="status-badge status-<?= $row['status']?>"><?= ucfirst($row['status'])?></span>
                </div>
                <?php endwhile;?>
            <?php else:?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <p>No upcoming appointments</p>
                    <a href="book_appointment.php" style="color:#8b5cf6;text-decoration:none;font-weight:600">Book now →</a>
                </div>
            <?php endif;?>
        </div>

        <!-- My Pets -->
        <div class="card" style="margin-bottom:30px">
            <div class="card-header">
                <h3 class="card-title">My Pets</h3>
                <a href="client/my_pet.php" class="view-all">Manage Pets</a>
            </div>

            <?php if($pets && $pets->num_rows > 0):?>
                <?php while($pet = $pets->fetch_assoc()):?>
                <div class="pet-card">
                    <div class="pet-avatar-small">🐕</div>
                    <div class="pet-info">
                        <h4><?= htmlspecialchars($pet['pet_name'])?></h4>
                        <p><?= $pet['breed']?> • <?= $pet['age']?> years old</p>
                    </div>
                </div>
                <?php endwhile;?>
            <?php else:?>
                <div class="empty-state">
                    <div class="empty-state-icon">🐾</div>
                    <p>No pets added yet</p>
                    <a href="add_pet.php" style="color:#8b5cf6;text-decoration:none;font-weight:600">Add your first pet →</a>
                </div>
            <?php endif;?>
        </div>

        <!-- Recent History - Full Width Below -->
        <div class="card" style="margin-top:40px">
            <div class="card-header">
                <h3 class="card-title">Recent Grooming History</h3>
            </div>

            <?php if($recent->num_rows > 0):?>
                <?php while($row = $recent->fetch_assoc()):?>
                <div class="appointment-item">
                    <div class="pet-avatar">✨</div>
                    <div class="appointment-details">
                        <div class="pet-name"><?= htmlspecialchars($row['pet_name'])?></div>
                        <div class="appointment-meta">
                            <?= $row['service_name']?> • <?= date('M d, Y', strtotime($row['appointment_date']))?>
                        </div>
                    </div>
                    <span class="status-badge status-completed">Completed</span>
                </div>
                <?php endwhile;?>
            <?php else:?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>No completed appointments yet</p>
                </div>
            <?php endif;?>
        </div>
    </div>
</body>
</html>