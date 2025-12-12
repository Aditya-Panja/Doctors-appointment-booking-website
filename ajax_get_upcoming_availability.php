<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$availability_summary = [];

if (isset($input['doctor_id'])) {
    $doctor_id = $input['doctor_id'];

    // Loop through the next 7 days (today + 6 more)
    for ($i = 0; $i < 7; $i++) {
        $date = new DateTime("+$i days");
        $date_str = $date->format('Y-m-d');
        $day_of_week = $date->format('l'); // 'Monday', 'Tuesday', etc.

        $total_slots = 0;
        $booked_slots = 0;

        // 1. Get all *already booked* slots for this day
        // 1. Get all *already booked* slots for this day
        $sql_booked = "SELECT COUNT(id) as count 
               FROM appointments 
               WHERE doctor_id = ? 
               AND appointment_date = ?
               AND status IN ('scheduled', 'confirmed')";
        if($stmt_booked = $conn->prepare($sql_booked)) {
            $stmt_booked->bind_param("is", $doctor_id, $date_str);
            $stmt_booked->execute();
            $result_booked = $stmt_booked->get_result();
            $booked_slots = $result_booked->fetch_assoc()['count'];
            $stmt_booked->close();
        }

        // 2. Get the doctor's total available slots for that day of the week
        $sql_avail = "SELECT start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?";
        if ($stmt_avail = $conn->prepare($sql_avail)) {
            $stmt_avail->bind_param("is", $doctor_id, $day_of_week);
            $stmt_avail->execute();
            $result_avail = $stmt_avail->get_result();

            while ($window = $result_avail->fetch_assoc()) {
                $start = new DateTime($window['start_time']);
                $end = new DateTime($window['end_time']);
                // Calculate how many 30-minute slots fit in this window
                $diff = $start->diff($end);
                $minutes = ($diff->h * 60) + $diff->i;
                $total_slots += floor($minutes / 30);
            }
            $stmt_avail->close();
        }
        
        $available_slots_count = $total_slots - $booked_slots;

        $availability_summary[] = [
            'date' => $date_str, // e.g., "2025-11-05"
            'day_name' => $date->format('D'), // e.g., "Wed"
            'day_num' => $date->format('j'), // e.g., "5"
            'available_slots' => $available_slots_count
        ];
    }
}

echo json_encode($availability_summary);
?>