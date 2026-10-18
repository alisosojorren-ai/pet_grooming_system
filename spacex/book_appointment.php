<?php
require 'includes/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Get user's pets
$pets = $conn->query("SELECT * FROM pets WHERE user_id = $user_id");
// Get services
$services = $conn->query("SELECT * FROM services ORDER BY price");
// Get groomers
$groomers = $conn->query("SELECT * FROM groomers WHERE is_active = 1");

// Handle booking
if(isset($_POST['book'])) {
    $pet_id = intval($_POST['pet_id']);
    $service_id = intval($_POST['service_id']);
    $groomer_id = intval($_POST['groomer_id']);
    $appointment_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];
    
    // Get service duration
    $service = $conn->query("SELECT duration_mins, price FROM services WHERE id = $service_id")->fetch_assoc();
    $duration = $service['duration_mins'];
    $total_amount = $service['price'];
    
    // Calculate end_time
    $end_time = date('H:i:s', strtotime($start_time) + ($duration * 60));
    
    // CRITICAL: Check if slot is available - NO DOUBLE BOOKING
    $check_sql = "SELECT id FROM appointments 
                  WHERE groomer_id = $groomer_id 
                  AND appointment_date = '$appointment_date'
                  AND status NOT IN ('cancelled','no-show')
                  AND (
                    (start_time < '$end_time' AND end_time > '$start_time')
                  )";
    $conflict = $conn->query($check_sql);
    
    if($conflict->num_rows > 0) {
        $msg = "<div style='color:red'>Sorry! Time slot taken na. Pili iba.</div>";
    } else {
        // Insert appointment
        $sql = "INSERT INTO appointments (user_id, pet_id, groomer_id, service_id, appointment_date, start_time, end_time, total_amount) 
                VALUES ($user_id, $pet_id, $groomer_id, $service_id, '$appointment_date', '$start_time', '$end_time', $total_amount)";
        
        if($conn->query($sql)) {
            header('Location: dashboard.php?booked=1');
            exit();
        } else {
            $msg = "<div style='color:red'>Error: ".$conn->error."</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Pet Grooming</title>
    <style>
        body{font-family:Arial;max-width:600px;margin:20px auto;padding:20px;background:#f5f5f5}
        form{background:white;padding:20px;border-radius:10px}
        select,input{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:5px}
        button{background:#8b5cf6;color:white;padding:12px;border:none;border-radius:5px;width:100%;cursor:pointer}
        button:hover{background:#7c3aed}
    </style>
</head>
<body>
    <a href="dashboard.php" style="display:inline-block;margin-bottom:20px;color:#8b5cf6;text-decoration:none;font-weight:600">← Back to Dashboard</a>
    <h2>Book Pet Grooming Appointment</h2>
    <?= $msg ?>
    <form method="POST">
        <label>Select Pet:</label>
        <select name="pet_id" required>
            <option value="">-- Choose Pet --</option>
            <?php while($pet = $pets->fetch_assoc()): ?>
            <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['pet_name']) ?> - <?= $pet['breed'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Service:</label>
        <select name="service_id" required>
            <option value="">-- Choose Service --</option>
            <?php while($srv = $services->fetch_assoc()): ?>
            <option value="<?= $srv['id'] ?>"><?= $srv['service_name'] ?> - ₱<?= number_format($srv['price'],2) ?> (<?= $srv['duration_mins'] ?>mins)</option>
            <?php endwhile; ?>
        </select>

        <label>Groomer:</label>
        <select name="groomer_id" required>
            <option value="">-- Choose Groomer --</option>
            <?php while($g = $groomers->fetch_assoc()): ?>
            <option value="<?= $g['id'] ?>"><?= $g['name'] ?> - <?= $g['specialization'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Date:</label>
        <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required>

        <label>Start Time:</label>
        <input type="time" name="start_time" min="09:00" max="17:00" required>

        <button type="submit" name="book">Book Appointment</button>
    </form>
</body>
</html>