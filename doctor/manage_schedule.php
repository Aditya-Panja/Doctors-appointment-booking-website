<?php
require_once '../includes/db.php';
// Security Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'doctor') {
    header("location: ../login.php");
    exit;
}

$doctor_id = $_SESSION['id'];
$message = '';
$error = '';

// Handle ADDING a new time slot
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_slot'])) {
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Basic validation
    if ($start_time >= $end_time) {
        $error = "End time must be after start time.";
    } else {
        $sql = "INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isss", $doctor_id, $day_of_week, $start_time, $end_time);
            if ($stmt->execute()) {
                $message = "Time slot added successfully.";
            } else {
                $error = "Error adding time slot.";
            }
            $stmt->close();
        }
    }
}

// Handle DELETING a time slot
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_slot'])) {
    $slot_id = $_POST['slot_id'];
    $sql = "DELETE FROM doctor_availability WHERE id = ? AND doctor_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $slot_id, $doctor_id);
        if ($stmt->execute()) {
            $message = "Time slot deleted successfully.";
        } else {
            $error = "Error deleting time slot.";
        }
        $stmt->close();
    }
}

// Fetch current schedule
$schedule = [];
$sql_fetch = "SELECT id, day_of_week, TIME_FORMAT(start_time, '%h:%i %p') as start_time_f, TIME_FORMAT(end_time, '%h:%i %p') as end_time_f FROM doctor_availability WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
if ($stmt_fetch = $conn->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $doctor_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    while($row = $result->fetch_assoc()) {
        $schedule[] = $row;
    }
    $stmt_fetch->close();
}

include '../includes/header.php';
?>

<div class="welcome-banner">
    <h2>Manage Your Schedule</h2>
    <p>Add or remove your available time slots. Patients will only be able to book appointments during these times.</p>
</div>

<?php if($message): ?><div class="form-message success"><?php echo $message; ?></div><?php endif; ?>
<?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>

<div class="form-container" style="max-width: 600px;">
    <h3 style="text-align:center;">Add New Time Slot</h3>
    <form action="manage_schedule.php" method="POST">
        <label for="day_of_week">Day of the Week:</label>
        <select name="day_of_week" required class="form-control" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
        </select>

        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" required>

        <label for="end_time">End Time:</label>
        <input type="time" name="end_time" required>

        <button type="submit" name="add_slot" class="btn btn-primary" style="width:100%;">Add Slot</button>
    </form>
</div>

<hr class="section-divider">

<section class="my-appointments-section">
    <h3 class="section-title">Your Current Schedule</h3>
    <div class="appointments-table-container">
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedule)): ?>
                    <tr><td colspan="4" style="text-align:center;">You have not set any available time slots.</td></tr>
                <?php else: ?>
                    <?php foreach ($schedule as $slot): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($slot['day_of_week']); ?></td>
                        <td><?php echo htmlspecialchars($slot['start_time_f']); ?></td>
                        <td><?php echo htmlspecialchars($slot['end_time_f']); ?></td>
                        <td>
                            <form action="manage_schedule.php" method="POST" onsubmit="return confirm('Delete this slot?');">
                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                <button type="submit" name="delete_slot" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include '../includes/footer.php'; ?>