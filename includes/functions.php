<?php
// Sanitize input
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Ensure completed_at column exists for completion-based revenue reporting
function ensureCompletedAtColumn() {
    global $conn;
    $result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'completed_at'");
    if($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER created_at");
    }
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format time
function formatTime($time, $format = 'h:i A') {
    return date($format, strtotime($time));
}

// Format currency
function formatPrice($amount) {
    return '₱' . number_format($amount, 2);
}

// Get user pets
function getUserPets($user_id) {
    global $conn;
    $result = $conn->query("SELECT * FROM pets WHERE user_id = $user_id ORDER BY id DESC");
    return $result;
}

// Check time conflict
function checkTimeConflict($groomer_id, $date, $start, $end, $exclude_id = 0) {
    global $conn;
    $sql = "SELECT id FROM appointments
            WHERE groomer_id = $groomer_id
            AND appointment_date = '$date'
            AND status NOT IN ('cancelled','no-show')
            AND (start_time < '$end' AND end_time > '$start')";

    if($exclude_id > 0) {
        $sql .= " AND id != $exclude_id";
    }

    return $conn->query($sql)->num_rows > 0;
}

// Get available time slots
function getAvailableSlots($groomer_id, $date, $duration_mins = 30) {
    global $conn;

    // Get booked slots
    $booked = [];
    $result = $conn->query("SELECT start_time, end_time FROM appointments
                           WHERE groomer_id = $groomer_id
                           AND appointment_date = '$date'
                           AND status NOT IN ('cancelled','no-show')");

    while($row = $result->fetch_assoc()) {
        $booked[] = ['start' => $row['start_time'], 'end' => $row['end_time']];
    }

    // Generate slots 9AM-5PM
    $slots = [];
    $start = strtotime('09:00');
    $end = strtotime('17:00');

    while($start < $end) {
        $slot_start = date('H:i:s', $start);
        $slot_end = date('H:i:s', $start + ($duration_mins * 60));

        $is_free = true;
        foreach($booked as $b) {
            if($slot_start < $b['end'] && $slot_end > $b['start']) {
                $is_free = false;
                break;
            }
        }

        if($is_free && strtotime($date.' '.$slot_start) > time()) {
            $slots[] = date('H:i:s', $start);
        }
        $start += 1800; // 30min intervals
    }

    return $slots;
}

// Get booking count by status
function getBookingCount($user_id = null, $status = null) {
    global $conn;
    $sql = "SELECT COUNT(*) as cnt FROM appointments WHERE 1=1";

    if($user_id) $sql .= " AND user_id = $user_id";
    if($status) $sql .= " AND status = '$status'";

    $result = $conn->query($sql);
    return $result->fetch_assoc()['cnt'];
}

// Get total revenue
function getTotalRevenue($start_date = null, $end_date = null) {
    global $conn;
    ensureCompletedAtColumn();
    $dateCondition = "COALESCE(DATE(completed_at), appointment_date)";
    $sql = "SELECT SUM(total_amount) as total FROM appointments WHERE status='completed'";

    if($start_date) $sql .= " AND $dateCondition >= '$start_date'";
    if($end_date) $sql .= " AND $dateCondition <= '$end_date'";

    $result = $conn->query($sql);
    return $result->fetch_assoc()['total'] ?? 0;
}

// Check if user owns pet
function userOwnsPet($user_id, $pet_id) {
    global $conn;
    $result = $conn->query("SELECT id FROM pets WHERE id = $pet_id AND user_id = $user_id");
    return $result->num_rows > 0;
}

// Get pet photo URL
function getPetPhoto($photo) {
    if($photo != 'default_pet.jpg' && file_exists(_DIR_.'/../assets/images/uploads/pets/'.$photo)) {
        return BASE_URL.'assets/images/uploads/pets/'.$photo;
    }
    return BASE_URL.'assets/images/default_pet.jpg';
}

// Alert message
function setAlert($type, $message) {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
}

function showAlert() {
    if(isset($_SESSION['alert'])) {
        $type = $_SESSION['alert']['type'];
        $message = $_SESSION['alert']['message'];
        echo "<div class='alert $type'>$message</div>";
        unset($_SESSION['alert']);
    }
}

// Redirect with message
function redirect($url, $type = null, $message = null) {
    if($type && $message) {
        setAlert($type, $message);
    }
    header("Location: $url");
    exit();
}
?>