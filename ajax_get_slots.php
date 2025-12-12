<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

// Set timezone to ensure "now" is correct (Adjust 'Asia/Kolkata' to your local zone if needed)
date_default_timezone_set('Asia/Kolkata'); 

$input = json_decode(file_get_contents('php://input'), true);
$all_slots = [];

if (isset($input['doctor_id']) && isset($input['date'])) {
    $doctor_id = $input['doctor_id'];
    $date = $input['date'];
    
    $day_of_week = date('l', strtotime($date));

    // 1. Get booked slots (ignoring cancelled ones)
    $booked_slots = [];
    $sql_booked = "SELECT TIME_FORMAT(appointment_time, '%H:%i') as time 
                   FROM appointments 
                   WHERE doctor_id = ? 
                   AND appointment_date = ? 
                   AND status IN ('scheduled', 'confirmed')";
                   
    if($stmt_booked = $conn->prepare($sql_booked)) {
        $stmt_booked->bind_param("is", $doctor_id, $date);
        $stmt_booked->execute();
        $result_booked = $stmt_booked->get_result();
        while($row = $result_booked->fetch_assoc()) {
            $booked_slots[] = $row['time'];
        }
        $stmt_booked->close();
    }

    // 2. Get available windows
    $sql_avail = "SELECT start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?";
    if ($stmt_avail = $conn->prepare($sql_avail)) {
        $stmt_avail->bind_param("is", $doctor_id, $day_of_week);
        $stmt_avail->execute();
        $result_avail = $stmt_avail->get_result();

        $current_timestamp = time(); // Current time on server

        while ($window = $result_avail->fetch_assoc()) {
            $start = new DateTime($window['start_time']);
            $end = new DateTime($window['end_time']);
            $interval = new DateInterval('PT30M'); 
            $period = new DatePeriod($start, $interval, $end);

            foreach ($period as $time) {
                $time_slot_str = $time->format('H:i');
                
                // --- NEW LOGIC: Check if this slot is in the past ---
                $slot_timestamp = strtotime("$date $time_slot_str");
                
                // If the slot is in the past, SKIP IT (don't add to list)
                if ($slot_timestamp <= $current_timestamp) {
                    continue; 
                }
                // ----------------------------------------------------

                if (in_array($time_slot_str, $booked_slots)) {
                    $all_slots[] = [
                        'time' => $time_slot_str,
                        'status' => 'booked'
                    ];
                } else {
                    $all_slots[] = [
                        'time' => $time_slot_str,
                        'status' => 'available'
                    ];
                }
            }
        }
        $stmt_avail->close();
    }
}

echo json_encode($all_slots);
?>