<?php
require_once 'includes/db.php';
$message = '';
$error = '';
$email = isset($_GET['email']) ? urldecode($_GET['email']) : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_entered = $_POST['otp'];
    $email_posted = $_POST['email'];

    $sql = "SELECT otp, otp_expiry FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email_posted);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($db_otp, $db_otp_expiry);
            $stmt->fetch();
            
            if ($db_otp == $otp_entered) {
                if (new DateTime() < new DateTime($db_otp_expiry)) {
                    // OTP is correct and not expired, verify the user
                    $update_sql = "UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE email = ?";
                    if ($update_stmt = $conn->prepare($update_sql)) {
                        $update_stmt->bind_param("s", $email_posted);
                        $update_stmt->execute();
                        
                        // Redirect to login with success message
                        header("location: login.php?verified=1");
                        exit();
                    }
                } else {
                    $error = "OTP has expired. Please request a new one.";
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
    <h2>Verify Your Account</h2>
    <p>An OTP has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>. Please enter it below.</p>
    <?php if($error): ?><p class="form-message error"><?php echo $error; ?></p><?php endif; ?>
    <form action="verify_otp.php" method="post">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <label for="otp">Enter OTP</label>
        <input type="text" name="otp" required maxlength="6">
        <button type="submit" class="btn btn-primary">Verify</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>