<?php
require_once 'includes/db.php';
require_once 'includes/send_mail.php'; // Include our mail function

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'patient';
    
    // Generate OTP
    $otp = rand(100000, 999999); // This is an integer
    $otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes")); // This is a string

    // SQL to insert new user
    // We also set is_verified to 0
    $sql = "INSERT INTO users (full_name, email, password, role, otp, otp_expiry, is_verified) 
            VALUES (?, ?, ?, ?, ?, ?, 0)";
    
    if ($stmt = $conn->prepare($sql)) {
        
        // --- THIS IS THE FIX ---
        // The bind_param types must match the variables: s, s, s, s, i (for integer), s
        $stmt->bind_param("ssssis", $full_name, $email, $hashed_password, $role, $otp, $otp_expiry);
        
        if ($stmt->execute()) {
            // Database part is done. Now try to send the email.
            if (send_otp_email($email, $otp)) {
                // Email sent! Redirect to verification page.
                header("location: verify_otp.php?email=" . urlencode($email));
                exit();
            } else {
                // Email failed to send.
                $error = "Registration successful, but could not send OTP. Please check your email configuration and Spam folder.";
            }
        } else {
            // Database insert failed, probably a duplicate email
            $error = "This email address is already registered.";
        }
        $stmt->close();
    }
}
$conn->close();

// Include the shared header for consistent styling
include 'includes/header.php';
?>

<div class="form-container">
    <h2>Register as a Patient</h2>
    <?php if($error): ?>
        <div class="form-message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="register.php" method="post">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" required>
        
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        
        <button type="submit" class="btn btn-primary" style="width:100%;">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php 
// Include the shared footer
include 'includes/footer.php'; 
?>