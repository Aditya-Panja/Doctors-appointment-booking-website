<?php
require_once '../includes/db.php';

// Security Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Handle Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];
    
    $sql = "UPDATE appointments SET status = ? WHERE id = ?";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $new_status, $appointment_id);
        $stmt->execute();
        $stmt->close();
    }
}

include '../includes/header.php';
?>

<div class="welcome-banner" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2>All Appointments</h2>
        <p>Manage and update all patient appointments.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

<section class="my-appointments-section">
    <div class="appointments-table-container">
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Doctor Name</th>
                    <th>Date & Time</th>
                    <th>Current Status</th>
                    <th>Change Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all appointments
                $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
                               p.full_name AS patient_name, 
                               d.full_name AS doctor_name
                        FROM appointments a
                        JOIN users p ON a.patient_id = p.id
                        JOIN users d ON a.doctor_id = d.id
                        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                            <td>
                                <?php 
                                echo date("d M, Y", strtotime($row['appointment_date'])) . 
                                     " at " . 
                                     date("h:i A", strtotime($row['appointment_time'])); 
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($row['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="view_appointments.php" class="update-form">
                                    <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                    <select name="status">
                                        <option value="scheduled" <?php if($row['status'] == 'scheduled') echo 'selected'; ?>>Scheduled</option>
                                        <option value="confirmed" <?php if($row['status'] == 'confirmed') echo 'selected'; ?>>Confirmed</option>
                                        <option value="completed" <?php if($row['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                                        <option value="cancelled" <?php if($row['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-update">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>No appointments found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</section>

<?php include '../includes/footer.php'; ?>