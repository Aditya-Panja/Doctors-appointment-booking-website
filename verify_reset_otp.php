<?php
require_once 'includes/db.php';

$email = $_REQUEST['email'] ?? '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $otp_entered = trim($_POST['otp']); 

    // 1. Fetch the OTP and Expiry from DB (No NOW() check here)
    $sql = "SELECT id, reset_token_hash, reset_token_expires_at FROM users WHERE email = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $db_otp, $db_expiry);
            $stmt->fetch();

            // 2. Check if OTP matches
            if ($db_otp === $otp_entered) {
                // 3. Check Time in PHP (Fixes Timezone Issue)
                $expiry_time = new DateTime($db_expiry);
                $current_time = new DateTime(); // Current PHP time

                if ($current_time < $expiry_time) {
                    // Success! Go to reset page
                    header("location: reset_password.php?email=" . urlencode($email) . "&code=" . urlencode($otp_entered));
                    exit();
                } else {
                    $error = "This OTP has expired. Please request a new one.";
                }
            } else {
                $error = "Invalid OTP. Please check the code and try again.";
            }
        } else {
            $error = "Invalid request. Email not found.";
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Verify OTP</h2>
    <p style="text-align:center; color:#666;">Enter the code sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
    
    <?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>

    <form action="verify_reset_otp.php" method="post">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        
        <label>Enter OTP</label>
        <input type="text" name="otp" required maxlength="6" autocomplete="off">
        
        <button type="submit" class="btn btn-primary" style="width:100%;">Verify Code</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>