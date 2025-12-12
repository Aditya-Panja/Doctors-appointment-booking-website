<?php
// Go up one directory to include the db connection and header
require_once '../includes/db.php';

// Security Check: Ensure the user is logged in and is an admin.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$message = '';
$error = '';

// Check for success flag in URL (Redirect pattern)
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Doctor added successfully!";
}
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $message = "Doctor removed successfully.";
}

// --- HANDLE ADD DOCTOR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_doctor'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $specialization = trim($_POST['specialization']);
    $fees = trim($_POST['fees']);
    
    // Basic Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($specialization) || empty($fees)) {
        $error = "All fields are required.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'doctor';
        $is_verified = 1; // Auto-verify doctors added by admin

        // Handle Image Upload
        $doctor_image = 'default_doctor.png'; // Default
        if (isset($_FILES['doctor_image']) && $_FILES['doctor_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['doctor_image']['tmp_name'];
            $fileName = $_FILES['doctor_image']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            // Allowed extensions
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Create unique name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = '../images/' . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $doctor_image = $newFileName;
                }
            }
        }

        // Database Transaction
        $conn->begin_transaction();
        try {
            // 1. Insert into Users Table
            $sql_user = "INSERT INTO users (full_name, email, password, role, is_verified) VALUES (?, ?, ?, ?, ?)";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("ssssi", $full_name, $email, $hashed_password, $role, $is_verified);
            $stmt_user->execute();
            $user_id = $conn->insert_id;
            $stmt_user->close();
            
            // 2. Insert into Doctors Table
            $sql_doctor = "INSERT INTO doctors (user_id, specialization, fees, image) VALUES (?, ?, ?, ?)";
            $stmt_doctor = $conn->prepare($sql_doctor);
            $stmt_doctor->bind_param("isds", $user_id, $specialization, $fees, $doctor_image);
            $stmt_doctor->execute();
            $stmt_doctor->close();
            
            $conn->commit();
            
            // Redirect to self to prevent resubmission
            header("Location: manage_doctors.php?success=1");
            exit();

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            if ($exception->getCode() == 1062) { // Duplicate entry error code
                $error = "Error: A user with this email already exists.";
            } else {
                $error = "Database Error: " . $exception->getMessage();
            }
        }
    }
}

// --- HANDLE DELETE DOCTOR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_doctor'])) {
    $doctor_user_id = $_POST['doctor_user_id'];
    
    // We only need to delete from 'users'. The foreign key constraint ON DELETE CASCADE 
    // (if set up correctly) will automatically delete the 'doctors' row.
    // Even if not, we should delete the user account.
    $sql_delete = "DELETE FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql_delete)) {
        $stmt->bind_param("i", $doctor_user_id);
        if ($stmt->execute()) {
            header("Location: manage_doctors.php?deleted=1");
            exit();
        } else {
            $error = "Error deleting doctor.";
        }
        $stmt->close();
    }
}

include '../includes/header.php';
?>

<div class="welcome-banner">
    <h2>Manage Doctors</h2>
    <p>Add, view, and remove doctor profiles from the system.</p>
</div>

<div class="admin-nav-links">
    <a href="index.php" class="admin-nav-item">View Appointments</a>
    <a href="manage_doctors.php" class="admin-nav-item active">Manage Doctors</a>
    <a href="view_appointments.php" class="admin-nav-item">Update Appointments</a>
</div>

<?php if($message): ?><div class="form-message success"><?php echo $message; ?></div><?php endif; ?>
<?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>

<div class="form-container">
    <h3 style="text-align: center;">Add a New Doctor</h3>
    <form action="manage_doctors.php" method="POST" enctype="multipart/form-data">
        <label>Full Name</label>
        <input type="text" name="full_name" required>
        
        <label>Email</label>
        <input type="email" name="email" required>
        
        <label>Password</label>
        <input type="password" name="password" required>
        
        <label>Specialization</label>
        <select name="specialization" required style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">Select Specialization</option>
            <option value="Cardiology">Cardiology</option>
            <option value="Neurology">Neurology</option>
            <option value="Orthopedics">Orthopedics</option>
            <option value="Pediatrics">Pediatrics</option>
            <option value="Dermatology">Dermatology</option>
            <option value="General Surgery">General Surgery</option>
        </select>
        
        <label>Consultation Fees ($)</label>
        <input type="number" name="fees" step="0.01" required>
        
        <label>Doctor Image</label>
        <input type="file" name="doctor_image" accept="image/*" style="margin-bottom: 15px;">
        
        <button type="submit" name="add_doctor" class="btn btn-primary" style="width:100%;">Add Doctor</button>
    </form>
</div>

<hr class="section-divider">

<section class="doctors-grid-section">
    <h3 class="section-title">Existing Doctors</h3>
    <div class="doctors-grid">
        <?php
        $sql = "SELECT u.id, u.full_name, u.email, d.specialization, d.fees, d.image 
                FROM users u 
                JOIN doctors d ON u.id = d.user_id 
                WHERE u.role = 'doctor'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $img = $row['image'] ? $row['image'] : 'default_doctor.png';
                ?>
                <div class="doctor-card">
                    <div class="doctor-card-img">
                        <img src="../images/<?php echo htmlspecialchars($img); ?>" alt="Doctor">
                    </div>
                    <div class="doctor-card-content">
                        <h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
                        <p class="doctor-dept"><?php echo htmlspecialchars($row['specialization']); ?></p>
                        <p class="doctor-qual"><?php echo htmlspecialchars($row['email']); ?></p>
                        
                        <form action="manage_doctors.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this doctor?');" style="margin-top: 10px;">
                            <input type="hidden" name="doctor_user_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_doctor" class="btn btn-danger" style="width: 100%;">Remove Doctor</button>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<p>No doctors found.</p>";
        }
        ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>