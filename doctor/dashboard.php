<?php
// Go up one directory to include the db connection and header
require_once '../includes/db.php';

// Security Check: Ensure the user is logged in and is a doctor.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'doctor') {
    header("location: ../login.php");
    exit;
}

// Get the logged-in doctor's ID
$doctor_id = $_SESSION["id"];

// Include the shared header
include '../includes/header.php';
?>

<div class="welcome-banner">
    <h2>Doctor Dashboard</h2>
    <p>Welcome, Dr. <?php echo htmlspecialchars($_SESSION["full_name"]); ?>! You can view your schedule and manage your availability here.</p>
    <a href="../logout.php" class="logout-link">Logout</a>
</div>

<div class="admin-nav-links">
    <a href="dashboard.php" class="admin-nav-item active">My Appointments</a>
    <a href="manage_schedule.php" class="admin-nav-item">Manage Schedule</a>
</div>

<section class="my-appointments-section">
    <h3 class="section-title">My Upcoming Appointments</h3>
    <div class="appointments-table-container">
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch appointments for this doctor that are on or after the current date
                $sql = "SELECT a.appointment_date, a.appointment_time, a.status, 
                               p.full_name AS patient_name
                        FROM appointments a
                        JOIN users p ON a.patient_id = p.id
                        WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE()
                        ORDER BY a.appointment_date, a.appointment_time";
                
                if($stmt = $conn->prepare($sql)){
                    $stmt->bind_param("i", $doctor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                            echo "<td>" . htmlspecialchars(date("d M, Y", strtotime($row['appointment_date']))) . "</td>";
                            echo "<td>" . htmlspecialchars(date("g:i A", strtotime($row['appointment_time']))) . "</td>";
                            echo "<td><span class='status-badge status-" . htmlspecialchars($row['status']) . "'>" . ucfirst(htmlspecialchars($row['status'])) . "</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;'>You have no upcoming appointments.</td></tr>";
                    }
                    $stmt->close();
                }
                ?>
            </tbody>
        </table>
    </div>
</section>

<?php
// Include the shared footer
include '../includes/footer.php'; 
?>