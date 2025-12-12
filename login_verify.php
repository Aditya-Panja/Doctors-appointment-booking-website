<?php
require_once 'includes/db.php';
$message = '';
$error = '';
$email = isset($_GET['email']) ? urldecode($_GET['email']) : '';

if (empty($email)) {
    header("location: login.php"); // No email, send them back
    exit;
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_entered = $_POST['otp'];
    $email_posted = $_POST['email'];

    $sql = "SELECT id, full_name, role, otp, otp_expiry FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email_posted);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $full_name, $role, $db_otp, $db_otp_expiry);
            $stmt->fetch();
            
            if ($db_otp == $otp_entered) {
                if (new DateTime() < new DateTime($db_otp_expiry)) {
                    // --- SUCCESS! ---
                    // OTP is correct and not expired. Log the user in.
                    
                    // Clear the OTP
                    $conn->query("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE email = '$email_posted'");
                    
                    // Set all the session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["full_name"] = $full_name;
                    $_SESSION["role"] = $role;
                    
                    // Redirect to the correct dashboard
                    if ($role == 'admin') header("location: admin/index.php");
                    elseif ($role == 'doctor') header("location: doctor/dashboard.php");
                    else header("location: dashboard.php");
                    exit();
                    
                } else {
                    $error = "OTP has expired. Please <a href='login.php'>try logging in again</a> to get a new code.";
                }
            } else {
                $error = "Invalid OTP entered. Please try again.";
            }
        }
        $stmt->close();
    }
}
$conn->close();

include 'includes/header.php';
?>
<div class="form-container">
    <h2>Check Your Email</h2>
    <p>A new verification code has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
    <?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>
    <form action="login_verify.php?email=<?php echo urlencode($email); ?>" method="post">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <label for="otp">Enter OTP</label>
        <input type="text" name="otp" required maxlength="6">
        <button type="submit" class="btn btn-primary" style="width:100%;">Log In</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>