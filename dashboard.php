<?php
// Start by including the database connection
require_once 'includes/db.php';

// Security Check: Ensure the user is logged in and has the 'patient' role.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient') {
    header("location: login.php");
    exit;
}

$patient_id = $_SESSION["id"];
$message = '';
$error = '';

// --- HANDLE CANCELLATION REQUEST ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_appointment'])) {
    $appointment_id_to_cancel = $_POST['appointment_id'];

    // Security check: Make sure this patient owns this appointment
    $sql_check = "SELECT id FROM appointments WHERE id = ? AND patient_id = ? AND (status = 'scheduled' OR status = 'confirmed')";
    
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("ii", $appointment_id_to_cancel, $patient_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows == 1) {
            // If it's valid, update the status to 'cancelled'
            $sql_update = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("i", $appointment_id_to_cancel);
                if ($stmt_update->execute()) {
                    $message = "Your appointment has been successfully cancelled.";
                } else {
                    $error = "Could not cancel the appointment. Please try again.";
                }
                $stmt_update->close();
            }
        } else {
            $error = "This appointment cannot be cancelled.";
        }
        $stmt_check->close();
    }
}
// --- END: CANCELLATION LOGIC ---

// Include the shared header file
include 'includes/header.php';
?>

<div class="welcome-banner">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h2>
    <p>This is your personal dashboard. You can book new appointments and view your existing ones here.</p>
    <a href="logout.php" class="logout-link">Logout</a>
</div>

<?php if($message): ?><div class="form-message success"><?php echo $message; ?></div><?php endif; ?>
<?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>

<section class="doctors-grid-section">
    <h3 class="section-title">Book a New Appointment</h3>
    
    <div class="search-bar-container">
        <form class="dependent-dropdown-form">
            <div class="form-group">
                <select id="filter-dept-select" class="live-search-input">
                    <option value="">Filter by Department</option>
                    <?php
                    // Fetch distinct departments
                    $dept_query = "SELECT DISTINCT specialization FROM doctors ORDER BY specialization ASC";
                    $dept_result = $conn->query($dept_query);
                    if ($dept_result->num_rows > 0) {
                        while($row = $dept_result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['specialization']) . '">' . htmlspecialchars($row['specialization']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <select id="filter-doc-select" class="live-search-input" disabled>
                    <option value="">All Doctors</option>
                </select>
            </div>
        </form>
    </div>
    <div class="doctors-grid" id="doctors-grid-list">
        <?php
        // Fetch all available doctors
        $sql_doctors = "SELECT u.id, u.full_name, d.specialization, d.fees, d.image 
                        FROM users u 
                        JOIN doctors d ON u.id = d.user_id 
                        WHERE u.role = 'doctor'";
        
        $result_doctors = $conn->query($sql_doctors);

        if ($result_doctors && $result_doctors->num_rows > 0) {
            while($doctor = $result_doctors->fetch_assoc()) {
                $doctor_image_filename = $doctor['image'] ? htmlspecialchars($doctor['image']) : 'default_doctor.png';
                
                // --- NEW: Fetch Availability for this Doctor ---
                $current_doctor_id = $doctor['id'];
                $availability_html = '';
                
                $sql_avail = "SELECT day_of_week, 
                                     TIME_FORMAT(start_time, '%l:%i %p') as start_f, 
                                     TIME_FORMAT(end_time, '%l:%i %p') as end_f 
                              FROM doctor_availability 
                              WHERE doctor_id = ?
                              ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
                
                if ($stmt_avail = $conn->prepare($sql_avail)) {
                    $stmt_avail->bind_param("i", $current_doctor_id);
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
                // --- END: New Logic ---
        ?>
                <div class="doctor-card">
                    <div class="doctor-card-img">
                        <img src="/doctor-appointment/images/<?php echo $doctor_image_filename; ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>">
                    </div>
                    <div class="doctor-card-content">
                        <h3><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                        <p class="doctor-dept"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        <p class="doctor-qual">Fees: $<?php echo htmlspecialchars($doctor['fees']); ?></p>
                        
                        <div class="doctor-availability">
                            <h4>Availability:</h4>
                            <?php echo $availability_html; ?>
                        </div>
                        
                        <div class="doctor-card-buttons">
                            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                </div>
        <?php
            } 
        } else {
            echo "<p style='text-align:center;'>No doctors are available at the moment.</p>";
        }
        ?>
    </div>
</section>

<hr class="section-divider">

<section class="my-appointments-section">
    <h3 class="section-title">My Appointments</h3>
    <div class="appointments-table-container">
        <table>
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Specialization</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="appointments-table-body"> <?php
                $sql_app = "SELECT 
                                a.id, 
                                a.appointment_date, 
                                a.appointment_time, 
                                a.status, 
                                u_doctor.full_name AS doctor_name, 
                                d.specialization
                            FROM appointments a
                            JOIN users u_doctor ON a.doctor_id = u_doctor.id
                            JOIN doctors d ON u_doctor.id = d.user_id
                            WHERE a.patient_id = ?
                            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                
                if($stmt = $conn->prepare($sql_app)) {
                    $stmt->bind_param("i", $patient_id);
                    $stmt->execute();
                    $result_app = $stmt->get_result();

                    if ($result_app->num_rows > 0) {
                        while($row = $result_app->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
                            echo "<td>" . htmlspecialchars(date("d M, Y", strtotime($row['appointment_date']))) . "</td>";
                            echo "<td>" . htmlspecialchars(date("g:i A", strtotime($row['appointment_time']))) . "</td>";
                            echo "<td><span class='status-badge status-" . htmlspecialchars($row['status']) . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                            
                            // --- Action Button Logic ---
                            echo "<td>";
                            if ($row['status'] == 'scheduled' || $row['status'] == 'confirmed') {
                                $appointment_datetime = new DateTime($row['appointment_date'] . ' ' . $row['appointment_time']);
                                $now = new DateTime();
                                
                                if ($appointment_datetime > $now) {
                                    echo '<form action="dashboard.php" method="POST" onsubmit="return confirm(\'Are you sure you want to cancel this appointment?\');">';
                                    echo '<input type="hidden" name="appointment_id" value="' . $row['id'] . '">';
                                    echo '<button type="submit" name="cancel_appointment" class="btn btn-danger">Cancel</button>';
                                    echo '</form>';
                                } else {
                                    echo '—'; // In the past
                                }
                            } else {
                                echo '—'; // Already completed or cancelled
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>You have no appointments scheduled.</td></tr>";
                    }
                    $stmt->close();
                }
                ?>
            </tbody>
        </table>
    </div>
</section>

<?php
// Include the shared footer file
include 'includes/footer.php'; 
?>