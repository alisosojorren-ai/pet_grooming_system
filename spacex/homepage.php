<?php
require 'config.php';

if(isset($_GET['date']) && isset($_GET['groomer_id'])) {
    $date = $_GET['date'];
    $groomer_id = intval($_GET['groomer_id']);

    // Get all booked slots for that day
    $booked = [];
    $result = $conn->query("SELECT start_time, end_time FROM appointments
                           WHERE groomer_id = $groomer_id
                           AND appointment_date = '$date'
                           AND status NOT IN ('cancelled','no-show')");

    while($row = $result->fetch_assoc()) {
        $booked[] = ['start' => $row['start_time'], 'end' => $row['end_time']];
    }

    // Generate available slots 9AM-5PM, 30min intervals
    $slots = [];
    $start = strtotime('09:00');
    $end = strtotime('17:00');

    while($start < $end) {
        $slot_start = date('H:i:s', $start);
        $slot_end = date('H:i:s', $start + 1800); // 30min slot

        $is_free = true;
        foreach($booked as $b) {
            if($slot_start < $b['end'] && $slot_end > $b['start']) {
                $is_free = false;
                break;
            }
        }

        if($is_free) $slots[] = date('h:i A', $start);
        $start += 1800;
    }

    header('Content-Type: application/json');
    echo json_encode($slots);
}
?>