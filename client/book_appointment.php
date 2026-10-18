<?php
require '../includes/config.php';
require '../includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    header("Location:../login.php");
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

// Pre-select pet if from URL
$selected_pet = isset($_GET['pet_id'])? intval($_GET['pet_id']) : '';

// Handle booking
if(isset($_POST['book'])) {
    $pet_id = intval($_POST['pet_id']);
    $service_id = intval($_POST['service_id']);
    $groomer_id = intval($_POST['groomer_id']);
    $appointment_date = sanitize($_POST['appointment_date']);
    $start_time = sanitize($_POST['start_time']);
    $notes = sanitize($_POST['notes']);

    // Verify pet belongs to user
    $pet_check = $conn->query("SELECT id FROM pets WHERE id = $pet_id AND user_id = $user_id");
    if($pet_check->num_rows == 0) {
        $msg = "<div class='alert error'>Invalid pet selection!</div>";
    } else {
        // Get service duration
        $service = $conn->query("SELECT duration_mins, price FROM services WHERE id = $service_id")->fetch_assoc();
        $duration = $service['duration_mins'];
        $total_amount = $service['price'];

        // Calculate end_time
        $end_time = date('H:i:s', strtotime($start_time) + ($duration * 60));

        // Check conflict
        if(checkTimeConflict($groomer_id, $appointment_date, $start_time, $end_time)) {
            $msg = "<div class='alert error'>Sorry! Time slot already taken. Please choose another.</div>";
        } else {
            // Insert appointment
            $sql = "INSERT INTO appointments (user_id, pet_id, groomer_id, service_id, appointment_date, start_time, end_time, total_amount, notes, status)
                    VALUES ($user_id, $pet_id, $groomer_id, $service_id, '$appointment_date', '$start_time', '$end_time', $total_amount, '$notes', 'pending')";

            if($conn->query($sql)) {
                header('Location: ../dashboard.php?booked=1');
                exit();
            } else {
                $msg = "<div class='alert error'>Error: ".$conn->error."</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - PetCare Grooming</title>
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
     .container{max-width:900px;margin:40px auto;padding:0 20px}
     .card{background:white;padding:40px;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
     .card h2{font-size:28px;margin-bottom:10px;color:#2d3748}
     .subtitle{color:#718096;margin-bottom:30px;font-size:14px}
     .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
     .form-group{margin-bottom:20px}
     .form-group.full{grid-column:1/-1}
        label{color:#4a5568;font-size:13px;font-weight:600;display:block;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px}
        input,select,textarea{width:100%;padding:14px 16px;border:2px solid #e2e8f0;border-radius:10px;font-size:15px;font-family:'Poppins',sans-serif;transition:0.3s;background:#f7fafc}
        input:focus,select:focus,textarea:focus{outline:none;border-color:#8b5cf6;background:white;box-shadow:0 0 0 4px rgba(139,92,246,0.1)}
        textarea{resize:vertical;min-height:100px}
     .time-slots{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:10px;margin-top:10px}
     .time-slot{padding:12px;background:#f7fafc;border:2px solid #e2e8f0;border-radius:8px;text-align:center;cursor:pointer;transition:0.3s;font-weight:600;font-size:14px}
     .time-slot:hover{border-color:#8b5cf6;background:#f3e8ff}
     .time-slot.selected{background:#8b5cf6;color:white;border-color:#8b5cf6}
     .time-slot.disabled{background:#f1f5f9;color:#cbd5e0;cursor:not-allowed;border-color:#e2e8f0}
        button{width:100%;padding:16px;background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white;border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;transition:0.3s;box-shadow:0 4px 15px rgba(139,92,246,0.4)}
        button:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(139,92,246,0.6)}
     .alert{padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:500}
     .alert.success{background:#c6f6d5;color:#22543d;border-left:4px solid #68d391}
     .alert.error{background:#fed7d7;color:#c53030;border-left:4px solid #fc8181}
     .alert a{color:#7c3aed;font-weight:600;text-decoration:none}
     .back-link{display:inline-block;margin-bottom:20px;color:#8b5cf6;text-decoration:none;font-weight:600}
     .back-link:hover{text-decoration:underline}
     .service-card{padding:15px;background:#f7fafc;border:2px solid #e2e8f0;border-radius:10px;margin-bottom:10px;cursor:pointer;transition:0.3s}
     .service-card:hover{border-color:#8b5cf6}
     .service-card.selected{border-color:#8b5cf6;background:#f3e8ff}
     .service-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px}
     .service-name{font-weight:700;font-size:16px}
     .service-price{color:#8b5cf6;font-weight:700;font-size:18px}
     .service-desc{font-size:13px;color:#718096}
     .service-duration{font-size:12px;color:#a0aec0;margin-top:5px}
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
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="card">
            <h2>Book Grooming Appointment 📅</h2>
            <p class="subtitle">Select your pet, service, and preferred time</p>

            <?= $msg?>

            <form method="POST" id="bookingForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Select Pet *</label>
                        <select name="pet_id" required>
                            <option value="">-- Choose Pet --</option>
                            <?php while($pet = $pets->fetch_assoc()):?>
                            <option value="<?= $pet['id']?>" <?= $selected_pet==$pet['id']?'selected':''?>>
                                <?= htmlspecialchars($pet['pet_name'])?> - <?= $pet['breed']?>
                            </option>
                            <?php endwhile;?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select Groomer *</label>
                        <select name="groomer_id" id="groomerSelect" required onchange="loadAvailableSlots()">
                            <option value="">-- Choose Groomer --</option>
                            <?php while($g = $groomers->fetch_assoc()):?>
                            <option value="<?= $g['id']?>"><?= $g['name']?> - <?= $g['specialization']?></option>
                            <?php endwhile;?>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label>Select Service *</label>
                        <div id="serviceList">
                            <?php while($srv = $services->fetch_assoc()):?>
                            <label class="service-card">
                                <input type="radio" name="service_id" value="<?= $srv['id']?>" required style="display:none" onchange="selectService(this)">
                                <div class="service-header">
                                    <span class="service-name"><?= $srv['service_name']?></span>
                                    <span class="service-price">₱<?= number_format($srv['price'],2)?></span>
                                </div>
                                <div class="service-desc"><?= $srv['description']?></div>
                                <div class="service-duration">⏱️ <?= $srv['duration_mins']?> minutes</div>
                            </label>
                            <?php endwhile;?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Appointment Date *</label>
                        <input type="date" name="appointment_date" id="appointmentDate" min="<?= date('Y-m-d')?>" required onchange="loadAvailableSlots()">
                    </div>

                    <div class="form-group">
                        <label>Available Time Slots *</label>
                        <input type="hidden" name="start_time" id="selectedTime" required>
                        <div class="time-slots" id="timeSlots">
                            <div style="grid-column:1/-1;text-align:center;color:#a0aec0;padding:20px">
                                Select date and groomer first
                            </div>
                        </div>
                    </div>

                    <div class="form-group full">
                        <label>Special Notes (Optional)</label>
                        <textarea name="notes" placeholder="e.g., Pet is nervous, please be gentle"></textarea>
                    </div>
                </div>

                <button type="submit" name="book">Confirm Booking</button>
            </form>
        </div>
    </div>

    <script>
        function selectService(radio) {
            document.querySelectorAll('.service-card').forEach(card => {
                card.classList.remove('selected');
            });
            radio.closest('.service-card').classList.add('selected');
            loadAvailableSlots();
        }

        function loadAvailableSlots() {
            const date = document.getElementById('appointmentDate').value;
            const groomerId = document.getElementById('groomerSelect').value;
            const serviceInput = document.querySelector('input[name="service_id"]:checked');
            const slotsDiv = document.getElementById('timeSlots');

            if(!date || !groomerId) {
                slotsDiv.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#a0aec0;padding:20px">Select date and groomer first</div>';
                return;
            }

            if(!serviceInput) {
                slotsDiv.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#a0aec0;padding:20px">Select a service first</div>';
                return;
            }

            const serviceId = serviceInput.value;
            slotsDiv.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#8b5cf6;padding:20px">Loading slots...</div>';

            fetch(`../includes/get_slots.php?date=${date}&groomer_id=${groomerId}&service_id=${serviceId}`)
               .then(res => res.json())
               .then(data => {
                    if(!data.success) {
                        throw new Error(data.message || 'Unable to load slots');
                    }

                    const slots = data.slots || [];
                    if(slots.length === 0) {
                        slotsDiv.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#fc8181;padding:20px">No available slots for this date</div>';
                    } else {
                        slotsDiv.innerHTML = slots.map(time => {
                            return '<div class="time-slot" onclick="selectTime(\'' + time + '\', this)">' + time + '</div>';
                        }).join('');
                    }
                })
               .catch(err => {
                    console.error('Slot load error:', err);
                    slotsDiv.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#fc8181;padding:20px">Error loading slots</div>';
                });
        }

        function selectTime(time, element) {
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('selectedTime').value = time;
        }
    </script>
</body>
</html>