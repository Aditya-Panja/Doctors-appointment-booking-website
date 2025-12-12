<?php
// This includes the db.php file, starts the session, and connects to the database
require_once 'includes/db.php';
// This includes the top bar, main navigation, and opening HTML tags
include 'includes/header.php';

// Get the doctor's ID from the URL (e.g., doctor_details.php?id=29)
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$doctor = null;

if ($doctor_id > 0) {
    // Prepare a query to fetch the specific doctor's details
    $sql = "SELECT u.full_name, d.specialization, d.fees, d.bio, d.image 
            FROM users u 
            JOIN doctors d ON u.id = d.user_id 
            WHERE u.id = ? AND u.role = 'doctor'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $doctor = $result->fetch_assoc();
        }
        $stmt->close();
    }
}
?>

<div class="page-content">
    <?php if ($doctor): 
        // Determine which image to show
        $doctor_image_filename = $doctor['image'] ? htmlspecialchars($doctor['image']) : 'default_doctor.png';
    ?>
        <div class="doctor-profile-card">
            <div class="doctor-profile-image">
                <img src="/doctor-appointment/images/<?php echo $doctor_image_filename; ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>">
            </div>
            
            <div class="doctor-profile-info">
                <h1><?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                <p class="specialization"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                <p class="fees">Consultation Fee: $<?php echo htmlspecialchars($doctor['fees']); ?></p>
                
                <hr class="section-divider" style="margin: 1.5rem 0;">
                
                <h3>About</h3>
                <p class="bio">
                    <?php 
                    echo $doctor['bio'] ? nl2br(htmlspecialchars($doctor['bio'])) : 'No biography available for this doctor.'; 
                    ?>
                </p>

                <div class="doctor-availability" style="margin-top: 1.5rem;">
                    <h4>Availability:</h4>
                    <?php
                    // Fetch Availability for this Doctor
                    $availability_html = '';
                    $sql_avail = "SELECT day_of_week, 
                                         TIME_FORMAT(start_time, '%l:%i %p') as start_f, 
                                         TIME_FORMAT(end_time, '%l:%i %p') as end_f 
                                  FROM doctor_availability 
                                  WHERE doctor_id = ?
                                  ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
                    
                    if ($stmt_avail = $conn->prepare($sql_avail)) {
                        $stmt_avail->bind_param("i", $doctor_id);
                        $stmt_avail->execute();
                        $result_avail = $stmt_avail->get_result();
                        
                        if ($result_avail->num_rows > 0) {
                            while($slot = $result_avail->fetch_assoc()) {
                                $availability_html .= '<p>' . htmlspecialchars(substr($slot['day_of_week'], 0, 3)) . ': ' . htmlspecialchars($slot['start_f']) . ' - ' . htmlspecialchars($slot['end_f']) . '</p>';
                            }
                        } else {
                            $availability_html = '<p class="not-available">Availability not set.</p>';
                        }
                        $stmt_avail->close();
                    }
                    echo $availability_html;
                    ?>
                </div>
                <a href="book_appointment.php?doctor_id=<?php echo $doctor_id; ?>" class="btn btn-primary" style="margin-top: 1.5rem;">Book an Appointment</a>
            </div>
        </div>
    <?php else: ?>
        <h2 style="text-align:center;">Doctor Not Found</h2>
        <p style="text-align:center;">The doctor you are looking for does not exist or is no longer available.</p>
    <?php endif; ?>
</div>

<?php 
// This includes the footer and closing HTML tags
include 'includes/footer.php'; 
?>