<?php
require_once 'includes/db.php';
require_once 'includes/send_mail.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists
    $sql = "SELECT id FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Generate a 6-digit OTP
            $otp = rand(100000, 999999);
            
            // We use the same 'reset_token_hash' column to store the OTP for simplicity
            // In a real production app, you might want a separate column, but this works fine.
            $expiry = date("Y-m-d H:i:s", time() + 60 * 10); // Valid for 10 minutes

            // Save OTP to DB
            $update_sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("sss", $otp, $expiry, $email);
                $update_stmt->execute();

                // Send OTP Email
                $subject = "Password Reset OTP";
                $body = "Your One-Time Password (OTP) for password reset is: <b>" . $otp . "</b><br><br>";
                $body .= "This code expires in 10 minutes.";

                if (send_email($email, $subject, $body)) {
                    // Redirect to the OTP entry page
                    header("location: verify_reset_otp.php?email=" . urlencode($email));
                    exit();
                } else {
                    $error = "Could not send OTP. Please try again.";
                }
            }
        } else {
            // Security: Don't reveal if email exists, but for UX we can show a generic message
            $message = "If that email exists, we have sent an OTP.";
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Forgot Password</h2>
    <p style="text-align:center; color:#666;">Enter your email to receive an OTP.</p>
    
    <?php if($message): ?><div class="form-message success"><?php echo $message; ?></div><?php endif; ?>
    <?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>

    <form action="forgot_password.php" method="post">
        <label>Email Address</label>
        <input type="email" name="email" required>
        <button type="submit" class="btn btn-primary" style="width:100%;">Send OTP</button>
    </form>
    <p>Remembered it? <a href="login.php">Login here</a></p>
</div>

<?php include 'includes/footer.php'; ?>