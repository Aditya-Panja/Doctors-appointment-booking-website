<?php
// Ensure this path to your database connection file is correct
require_once 'includes/db.php';

$doctors = [];

// Check if a department was sent via POST
if (isset($_POST['department']) && !empty($_POST['department'])) {
    $department = $_POST['department'];

    // Prepare a statement to safely query the database
    $sql = "SELECT u.id, u.full_name 
            FROM users u 
            JOIN doctors d ON u.id = d.user_id 
            WHERE d.specialization = ? 
            ORDER BY u.full_name ASC";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $department);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            // Fetch all matching doctors into an array
            while ($row = $result->fetch_assoc()) {
                $doctors[] = $row;
            }
        }
        $stmt->close();
    }
}

// Set the correct header to tell the browser it's receiving JSON
header('Content-Type: application/json');

// Encode the PHP array into a JSON string and send it back
echo json_encode($doctors);
?>