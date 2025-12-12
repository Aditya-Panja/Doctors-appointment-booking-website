<?php
require_once 'includes/db.php';

$email = $_GET['email'] ?? '';
$code = $_GET['code'] ?? '';
$error = '';
$message = '';

if (!$email || !$code) {
    die("Invalid request. Missing information.");
}

$user_id = null;

// 1. Fetch user data without checking time in SQL
$sql = "SELECT id, reset_token_expires_at FROM users WHERE email = ? AND reset_token_hash = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->bind_result($id, $db_expiry);
    
    if ($stmt->fetch()) {
        // 2. Check Time in PHP
        $expiry_time = new DateTime($db_expiry);
        $current_time = new DateTime();

        if ($current_time < $expiry_time) {
            $user_id = $id; // Valid!
        }
    }
    $stmt->close();
}

if (!$user_id) {
    // If we are here, either the OTP was wrong or Time expired
    include 'includes/header.php';
    echo '<div class="form-container"><div class="form-message error">Session expired or invalid link. Please try the <a href="forgot_password.php">Forgot Password</a> process again.</div></div>';
    include 'includes/footer.php';
    exit;
}

// Handle Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear OTP
        $update_sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
        if ($update_stmt = $conn->prepare($update_sql)) {
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $message = "Password updated successfully! <a href='login.php'>Login now</a>.";
                $user_id = null; // Hide form
            } else {
                $error = "Error updating password.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Reset Password</h2>
    <?php if($message): ?><div class="form-message success"><?php echo $message; ?></div><?php endif; ?>
    <?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>

    <?php if($user_id): ?>
    <form action="reset_password.php?email=<?php echo urlencode($email); ?>&code=<?php echo htmlspecialchars($code); ?>" method="post">
        
        <label>New Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" required minlength="6">
            <button type="button" class="toggle-password-btn"><i class="fas fa-eye"></i></button>
        </div>

        <label>Confirm Password</label>
        <div class="password-wrapper">
            <input type="password" name="confirm_password" required minlength="6">
            <button type="button" class="toggle-password-btn"><i class="fas fa-eye"></i></button>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">Set New Password</button>
    </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>