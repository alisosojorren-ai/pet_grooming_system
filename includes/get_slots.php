<?php
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');

if(isset($_GET['date']) && isset($_GET['groomer_id'])) {
    $date = sanitize($_GET['date']);
    $groomer_id = intval($_GET['groomer_id']);
    $service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : null;

    // Get service duration if provided
    $duration = 30; // default 30mins
    if($service_id) {
        $service = $conn->query("SELECT duration_mins FROM services WHERE id = $service_id")->fetch_assoc();
        $duration = $service['duration_mins'] ?? 30;
    }

    $slots = getAvailableSlots($groomer_id, $date, $duration);

    // Format for display
    $formatted_slots = array_map(function($slot) {
        return date('h:i A', strtotime($slot));
    }, $slots);

    echo json_encode([
        'success' => true,
        'slots' => $formatted_slots,
        'raw_slots' => $slots
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameters'
    ]);
}
?>