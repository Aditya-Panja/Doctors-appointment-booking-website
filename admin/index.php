<?php
// Go up one directory to include the db connection and header
require_once '../includes/db.php';

// Security Check: Ensure the user is logged in and is an admin.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Include the shared header
include '../includes/header.php';
?>

<div class="welcome-banner">
    <h2>Admin Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>! From here you can manage the entire system.</p>
    <a href="../logout.php" class="logout-link">Logout</a>
</div>

<div class="admin-nav-links">
    <a href="index.php" class="admin-nav-item active">View Appointments</a>
    <a href="manage_doctors.php" class="admin-nav-item">Manage Doctors</a>
    <a href="view_appointments.php" class="admin-nav-item">Update Appointments</a>
</div>

<section class="my-appointments-section">
    <h3 class="section-title">All System Appointments</h3>
    
    <div class="search-bar-container">
    <form class="dependent-dropdown-form">
        <div class="form-group">
            <select id="admin-filter-dept" class="live-search-input">
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
            <select id="admin-filter-doc" class="live-search-input" disabled>
                <option value="">All Doctors</option>
            </select>
        </div>
    </form>
</div>

    <div class="appointments-table-container">
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="appointments-table-body"> <?php
                // Fetch all appointments with patient and doctor names
                $sql = "SELECT a.appointment_date, a.appointment_time, a.status, 
                               p.full_name AS patient_name, 
                               d.full_name AS doctor_name
                        FROM appointments a
                        JOIN users p ON a.patient_id = p.id
                        JOIN users d ON a.doctor_id = d.id
                        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                        echo "<td>" . htmlspecialchars(date("d M, Y", strtotime($row['appointment_date']))) . "</td>";
                        echo "<td>" . htmlspecialchars(date("g:i A", strtotime($row['appointment_time']))) . "</td>";
                        echo "<td><span class='status-badge status-" . htmlspecialchars($row['status']) . "'>" . ucfirst(htmlspecialchars($row['status'])) . "</span></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>No appointments found in the system.</td></tr>";
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