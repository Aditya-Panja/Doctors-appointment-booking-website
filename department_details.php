<?php
// This includes the db.php file, starts the session, and connects to the database
require_once 'includes/db.php';

// --- 1. GET DEPARTMENT NAME ---
// Get the department name from the URL (e.g., ...?dept=Orthopedics)
if (!isset($_GET['dept'])) {
    echo "No department specified.";
    exit; // Stop the script if no department is provided
}
$department_name = htmlspecialchars($_GET['dept']);

// --- 2. FETCH DEPARTMENT DETAILS ---
$dept_details = null;
$sql_dept = "SELECT description, hero_image FROM departments WHERE name = ?";
if ($stmt_dept = $conn->prepare($sql_dept)) {
    $stmt_dept->bind_param("s", $department_name);
    $stmt_dept->execute();
    $result_dept = $stmt_dept->get_result();
    if ($result_dept->num_rows > 0) {
        $dept_details = $result_dept->fetch_assoc();
    }
    $stmt_dept->close();
}

// --- 3. FETCH DOCTORS FOR THIS DEPARTMENT ---
$doctors = [];
$sql_doctors = "SELECT u.id, u.full_name, d.fees, d.image 
                FROM users u 
                JOIN doctors d ON u.id = d.user_id 
                WHERE d.specialization = ?";

if ($stmt_doc = $conn->prepare($sql_doctors)) {
    $stmt_doc->bind_param("s", $department_name);
    $stmt_doc->execute();
    $result_doctors = $stmt_doc->get_result();
    if ($result_doctors->num_rows > 0) {
        while ($row = $result_doctors->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
    $stmt_doc->close();
}

// This includes the top bar, main navigation, and opening HTML tags
include 'includes/header.php';
?>

<section class="hero-section">
    <div class="container">
        <div class="hero-text">
            <p class="breadcrumbs">Departments / <?php echo $department_name; ?></p>
            <h1><?php echo $department_name; ?></h1>
        </div>
    </div>
    <?php if ($dept_details && $dept_details['hero_image']): ?>
    <div class="hero-image-container">
        <img src="/doctor-appointment/images/<?php echo htmlspecialchars($dept_details['hero_image']); ?>" alt="<?php echo $department_name; ?>" class="hero-doctor-image">
    </div>
    <?php endif; ?>
</section>

<section class="department-description-section">
    <div class="container">
        <div class="department-content">
            <div class="description-text">
                <h2><?php echo $department_name; ?> Department</h2>
                <?php if ($dept_details && $dept_details['description']): ?>
                    <p><?php echo nl2br(htmlspecialchars($dept_details['description'])); ?></p>
                <?php else: ?>
                    <p>Details for this department are not yet available.</p>
                <?php endif; ?>
            </div>
            <div class="description-image">
                <img src="\doctor-appointment\images\doctor_image.png" alt="Department Image">
            </div>
        </div>
    </div>
</section>


<section class="doctors-grid-section">
    <div class="container">
        <h3 class="section-title">Our Consultants</h3>
        <div class="doctors-grid">
            <?php
            if (!empty($doctors)):
                foreach($doctors as $doctor):
                    $doctor_image_filename = $doctor['image'] ? htmlspecialchars($doctor['image']) : 'default_doctor.png';
            ?>
                    <div class="doctor-card">
                        <div class="doctor-card-img">
                            <img src="/doctor-appointment/images/<?php echo $doctor_image_filename; ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>">
                        </div>
                        <div class="doctor-card-content">
                            <h3><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                            <p class="doctor-dept"><?php echo $department_name; ?></p>
                            <p class="doctor-qual">Fees: $<?php echo htmlspecialchars($doctor['fees']); ?></p>
                            <div class="doctor-card-buttons">
                                <a href="doctor_details.php?id=<?php echo $doctor['id']; ?>" class="btn btn-secondary">View Details</a>
                                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Now</a>
                            </div>
                        </div>
                    </div>
            <?php
                endforeach; 
            else:
                echo "<p style='text-align:center;'>No doctors are currently listed for this department.</p>";
            endif;
            ?>
        </div> </div> </section>

<?php 
// This includes the footer and closing HTML tags
include 'includes/footer.php'; 
?>