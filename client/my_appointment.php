<?php
require '../includes/config.php';
require '../includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Ensure rating/review columns exist
$ratingColumn = $conn->query("SHOW COLUMNS FROM appointments LIKE 'rating'");
if($ratingColumn && $ratingColumn->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN rating TINYINT(1) DEFAULT NULL");
}
$reviewColumn = $conn->query("SHOW COLUMNS FROM appointments LIKE 'review'");
if($reviewColumn && $reviewColumn->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN review TEXT DEFAULT NULL");
}

// Handle rating submission
if(isset($_POST['rate_service'])) {
    $app_id = intval($_POST['appointment_id']);
    $rating = intval($_POST['rating']);
    if($rating < 1) $rating = 1;
    if($rating > 5) $rating = 5;
    $review = sanitize($_POST['review']);

    $check = $conn->query("SELECT id FROM appointments WHERE id = $app_id AND user_id = $user_id AND status = 'completed'");
    if($check->num_rows > 0) {
        $conn->query("UPDATE appointments SET rating = $rating, review = '$review' WHERE id = $app_id AND user_id = $user_id");
        header("Location: my_appointment.php?filter=".urlencode(isset($_GET['filter'])?$_GET['filter']:'all')."&rated=1");
        exit();
    }
    $msg = "<div class='alert error'>Unable to save rating. Please try again.</div>";
}

// Handle Cancel
if(isset($_GET['cancel'])) {
    $app_id = intval($_GET['cancel']);

    // Check if appointment belongs to user and can be cancelled
    $check = $conn->query("SELECT appointment_date, start_time FROM appointments
                           WHERE id = $app_id AND user_id = $user_id
                           AND status IN ('pending','confirmed')");

    if($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $app_datetime = strtotime($row['appointment_date'].' '.$row['start_time']);
        $now = time();
        $hours_diff = ($app_datetime - $now) / 3600;

        // Only allow cancel if 24hrs before appointment
        if($hours_diff >= 24) {
            $conn->query("UPDATE appointments SET status='cancelled' WHERE id = $app_id");
            $msg = "<div class='alert success'>Appointment cancelled successfully!</div>";
        } else {
            $msg = "<div class='alert error'>Cannot cancel! Must be 24 hours before appointment.</div>";
        }
    }
}

// Get all appointments with filters
$filter = isset($_GET['filter'])? $_GET['filter'] : 'all';

$sql = "SELECT a.*, p.pet_name, p.photo, s.service_name, s.price, s.duration_mins, g.name as groomer_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.id
        JOIN services s ON a.service_id = s.id
        JOIN groomers g ON a.groomer_id = g.id
        WHERE a.user_id = $user_id";

if($filter == 'upcoming') {
    $sql.= " AND a.appointment_date >= CURDATE() AND a.status IN ('pending','confirmed')";
} elseif($filter == 'past') {
    $sql.= " AND (a.appointment_date < CURDATE() OR a.status IN ('completed','cancelled','no-show'))";
} elseif($filter == 'pending') {
    $sql.= " AND a.status = 'pending'";
} elseif($filter == 'completed') {
    $sql.= " AND a.status = 'completed'";
}

$sql.= " ORDER BY a.appointment_date DESC, a.start_time DESC";
$appointments = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - PetCare Grooming</title>
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
      .page-header{margin-bottom:30px}
      .page-header h2{font-size:32px;color:#2d3748;margin-bottom:8px}
      .page-header p{color:#718096;font-size:14px}
      .alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500}
      .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
      .alert.error{background:#fed7d7;color:#c53030;border-left:4px solid #fc8181}
      .filter-tabs{display:flex;gap:10px;margin-bottom:25px;flex-wrap:wrap}
      .filter-tab{padding:12px 24px;background:white;border:2px solid #e2e8f0;border-radius:10px;text-decoration:none;color:#4a5568;font-weight:600;font-size:14px;transition:0.3s}
      .filter-tab:hover{border-color:#8b5cf6;color:#8b5cf6}
      .filter-tab.active{background:#8b5cf6;color:white;border-color:#8b5cf6}
      .appointments-list{display:grid;gap:20px}
      .appointment-card{background:white;border-radius:16px;padding:25px;box-shadow:0 4px 15px rgba(0,0,0,0.08);transition:0.3s;border-left:5px solid #8b5cf6}
      .appointment-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,0.12)}
      .appointment-card.cancelled{border-left-color:#cbd5e0;opacity:0.7}
      .appointment-card.completed{border-left-color:#68d391}
      .appointment-card.no-show{border-left-color:#fc8181}
      .card-top{display:flex;justify-content:space-between;align-items:start;margin-bottom:20px}
      .pet-info{display:flex;gap:15px;align-items:center}
      .pet-avatar{width:70px;height:70px;border-radius:12px;background:linear-gradient(135deg,#fbbf24,#f59e0b);display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0}
      .pet-avatar img{width:100%;height:100%;object-fit:cover;border-radius:12px}
      .pet-details h3{font-size:20px;margin-bottom:5px;color:#2d3748}
      .pet-details p{color:#718096;font-size:14px}
      .status-badge{padding:8px 16px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px}
      .status-pending{background:#fef3c7;color:#92400e}
      .status-confirmed{background:#d1fae5;color:#065f46}
      .status-completed{background:#dbeafe;color:#1e40af}
      .status-cancelled{background:#f1f5f9;color:#64748b}
      .status-no-show{background:#fee;color:#c53030}
      .card-details{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:20px;padding:20px;background:#f7fafc;border-radius:12px}
      .detail-item{display:flex;flex-direction:column;gap:5px}
      .detail-label{font-size:12px;color:#a0aec0;text-transform:uppercase;font-weight:600;letter-spacing:0.5px}
      .detail-value{font-size:15px;color:#2d3748;font-weight:600}
      .detail-value.price{color:#8b5cf6;font-size:18px}
      .card-actions{display:flex;gap:10px;flex-wrap:wrap}
      .btn{padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;transition:0.3s;border:none;cursor:pointer}
      .btn-primary{background:#8b5cf6;color:white}
      .btn-primary:hover{background:#7c3aed;transform:translateY(-2px)}
      .btn-danger{background:#fee;color:#c53030}
      .btn-danger:hover{background:#fc8181;color:white}
      .btn-secondary{background:#e2e8f0;color:#4a5568}
      .btn-secondary:hover{background:#cbd5e0}
      .btn:disabled{opacity:0.5;cursor:not-allowed}
      .empty-state{text-align:center;padding:80px 20px;background:white;border-radius:16px}
      .empty-icon{font-size:100px;margin-bottom:20px;opacity:0.3}
      .empty-state h3{font-size:24px;margin-bottom:10px;color:#4a5568}
      .empty-state p{color:#718096;margin-bottom:25px}
      .btn-empty{display:inline-block;background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white;padding:14px 32px;border-radius:12px;text-decoration:none;font-weight:600;transition:0.3s}
      .btn-empty:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(139,92,246,0.4)}
      .back-link{display:inline-block;margin-bottom:20px;color:#8b5cf6;text-decoration:none;font-weight:600}
      .back-link:hover{text-decoration:underline}
      .countdown{background:#fff5f5;padding:12px;border-radius:8px;border-left:3px solid #fc8181;margin-top:15px}
      .countdown-text{font-size:13px;color:#c53030;font-weight:600}
      .rating-stars{display:inline-flex;gap:4px;margin-top:6px}
      .rating-stars label{cursor:pointer;font-size:20px;color:#cbd5e0;transition:color 0.2s}
      .rating-stars input{display:none}
      .rating-stars label.filled{color:#f6ad55}
      .rating-form{border-top:1px solid #e2e8f0;padding-top:20px;margin-top:20px}
      .rating-textarea{width:100%;padding:14px 16px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;min-height:80px;resize:vertical;background:#f7fafc}
      .rating-stars{display:inline-flex;gap:6px;margin-top:6px}
      .rating-stars label{cursor:pointer;font-size:24px;color:#cbd5e0;transition:color 0.15s ease}
      .rating-stars label.filled{color:#f6ad55}
      .rating-stars label:hover,
      .rating-stars label:hover ~ label{color:#f6ad55}
        @media(max-width:768px){
          .card-top{flex-direction:column;gap:15px}
          .card-details{grid-template-columns:1fr}
          .filter-tabs{overflow-x:auto;padding-bottom:10px}
        }
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

        <div class="page-header">
            <h2>My Appointments 📅</h2>
            <p>Manage your pet grooming bookings</p>
        </div>

        <?= $msg?>
        <?php if(isset($_GET['rated']) && $_GET['rated'] == '1'):?>
            <div class="alert success">Thank you! Your rating and review have been saved.</div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?= $filter=='all'?'active':''?>">All Appointments</a>
            <a href="?filter=upcoming" class="filter-tab <?= $filter=='upcoming'?'active':''?>">Upcoming</a>
            <a href="?filter=pending" class="filter-tab <?= $filter=='pending'?'active':''?>">Pending</a>
            <a href="?filter=completed" class="filter-tab <?= $filter=='completed'?'active':''?>">Completed</a>
            <a href="?filter=past" class="filter-tab <?= $filter=='past'?'active':''?>">Past</a>
        </div>

        <!-- Appointments List -->
        <?php if($appointments->num_rows > 0):?>
            <div class="appointments-list">
                <?php while($row = $appointments->fetch_assoc()):
                    $app_datetime = strtotime($row['appointment_date'].' '.$row['start_time']);
                    $now = time();
                    $hours_diff = ($app_datetime - $now) / 3600;
                    $can_cancel = $hours_diff >= 24 && in_array($row['status'], ['pending','confirmed']);
               ?>
                <div class="appointment-card <?= $row['status']?>">
                    <div class="card-top">
                        <div class="pet-info">
                            <div class="pet-avatar">
                                <?php if($row['photo']!= 'default_pet.jpg' && file_exists('../assets/images/uploads/pets/'.$row['photo'])):?>
                                    <img src="../assets/images/uploads/pets/<?= $row['photo']?>" alt="<?= htmlspecialchars($row['pet_name'])?>">
                                <?php else:?>
                                    🐕
                                <?php endif;?>
                            </div>
                            <div class="pet-details">
                                <h3><?= htmlspecialchars($row['pet_name'])?></h3>
                                <p><?= $row['service_name']?> • <?= $row['groomer_name']?></p>
                            </div>
                        </div>
                        <span class="status-badge status-<?= $row['status']?>"><?= ucfirst($row['status'])?></span>
                    </div>

                    <div class="card-details">
                        <div class="detail-item">
                            <div class="detail-label">Date</div>
                            <div class="detail-value"><?= date('l, M d, Y', strtotime($row['appointment_date']))?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Time</div>
                            <div class="detail-value"><?= date('h:i A', strtotime($row['start_time']))?> - <?= date('h:i A', strtotime($row['end_time']))?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?= $row['duration_mins']?> minutes</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value price">₱<?= number_format($row['total_amount'], 2)?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value"><?= ucfirst($row['payment_status'])?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Booking ID</div>
                            <div class="detail-value">#<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT)?></div>
                        </div>
                        <?php if(!empty($row['rating'])):?>
                        <div class="detail-item" style="grid-column:1/-1">
                            <div class="detail-label">Service Rating</div>
                            <div class="detail-value">
                                <span class="rating-stars">
                                    <?php for($i=1;$i<=5;$i++): ?>
                                        <label class="<?= $row['rating'] >= $i ? 'filled' : '' ?>">★</label>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <?php if(!empty($row['review'])):?>
                            <div class="detail-value" style="font-size:14px;color:#4a5568;margin-top:8px;line-height:1.5;white-space:pre-wrap"><?= htmlspecialchars($row['review'])?></div>
                            <?php endif;?>
                        </div>
                        <?php endif;?>
                    </div>

                    <?php if($can_cancel && $hours_diff < 48):?>
                    <div class="countdown">
                        <div class="countdown-text">⏰ <?= round($hours_diff)?> hours left to cancel without penalty</div>
                    </div>
                    <?php endif;?>

                    <div class="card-actions">
                        <?php if($can_cancel):?>
                            <a href="?cancel=<?= $row['id']?>"
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                Cancel Appointment
                            </a>
                        <?php elseif($row['status'] == 'completed'):?>
                            <button class="btn btn-secondary" disabled>Completed ✓</button>
                        <?php elseif($row['status'] == 'cancelled'):?>
                            <button class="btn btn-secondary" disabled>Cancelled</button>
                        <?php else:?>
                            <button class="btn btn-secondary" disabled>Cannot Cancel</button>
                        <?php endif;?>

                        <?php if($row['status'] == 'completed'):?>
                            <button type="button" class="btn btn-primary" onclick="toggleRatingForm(<?= $row['id']?>)">
                                <?= !empty($row['rating']) ? 'Edit Rating' : 'Rate Service' ?>
                            </button>
                        <?php endif;?>
                    </div>

                    <?php if($row['status'] == 'completed'):?>
                        <div id="ratingForm<?= $row['id']?>" class="rating-form" style="display:none;">
                            <form method="post">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <div class="detail-item" style="grid-column:1/-1">
                                    <div class="detail-label">Service Rating</div>
                                    <div class="rating-stars" id="ratingStars<?= $row['id'] ?>">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <input type="radio" id="rating-<?= $row['id'] ?>-<?= $i?>" name="rating" value="<?= $i?>" <?= (!empty($row['rating']) && $row['rating'] == $i) ? 'checked' : '' ?> />
                                            <label for="rating-<?= $row['id'] ?>-<?= $i?>" data-value="<?= $i?>" class="<?= (!empty($row['rating']) && $row['rating'] >= $i) ? 'filled' : '' ?>" onclick="setStarRating(<?= $row['id']?>, <?= $i?>)">★</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="detail-item" style="grid-column:1/-1">
                                    <div class="detail-label">Comments</div>
                                    <textarea class="rating-textarea" name="review" placeholder="Leave a comment about the service"><?= htmlspecialchars($row['review'])?></textarea>
                                </div>
                                <button type="submit" name="rate_service" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    <?php endif;?>
                </div>
                <?php endwhile;?>
            </div>
        <?php else:?>
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <h3>No Appointments Found</h3>
                <p>You haven't booked any grooming appointments yet</p>
                <a href="../book_appointment.php" class="btn-empty">Book Your First Appointment</a>
            </div>
        <?php endif;?>
    </div>
    <script>
        function toggleRatingForm(id) {
            const form = document.getElementById('ratingForm' + id);
            if(!form) return;
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }

        function setStarRating(appointmentId, rating) {
            const form = document.getElementById('ratingForm' + appointmentId);
            if(!form) return;
            const inputs = form.querySelectorAll('input[name="rating"]');
            const labels = form.querySelectorAll('.rating-stars label');

            inputs.forEach(input => {
                input.checked = Number(input.value) === rating;
            });
            labels.forEach(label => {
                const value = Number(label.getAttribute('data-value'));
                if(value <= rating) {
                    label.classList.add('filled');
                } else {
                    label.classList.remove('filled');
                }
            });
        }
    </script>
</body>
</html