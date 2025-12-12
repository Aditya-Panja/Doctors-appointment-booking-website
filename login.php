<?php
require_once 'includes/db.php';
require_once 'includes/send_mail.php'; // Required for sending 2FA OTP

// If user is already logged in, redirect them
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if ($_SESSION["role"] == 'admin') header("location: admin/index.php");
    elseif ($_SESSION["role"] == 'doctor') header("location: doctor/dashboard.php");
    else header("location: dashboard.php");
    exit;
}

$error = '';
$message = '';

// Check if redirected from verification or password reset
if(isset($_GET['verified']) && $_GET['verified'] == 1){
    $message = "Email verified successfully! You can now log in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Find the user and check verification status
    $sql = "SELECT id, password, is_verified FROM users WHERE email = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $hashed_password, $is_verified);
                if ($stmt->fetch()) {
                    // 1. Verify Password
                    if (password_verify($password, $hashed_password)) {
                        
                        // 2. Check if account was verified at registration
                        if ($is_verified != 1) {
                            $error = "Your account is not verified. Please <a href='verify_otp.php?email=" . urlencode($email) . "'>verify your account</a> first.";
                        } else {
                            // 3. 2-FACTOR AUTHENTICATION: Generate & Send OTP
                            $otp = rand(100000, 999999);
                            $otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

                            // Update user record with new Login OTP
                            $update_sql = "UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?";
                            if ($update_stmt = $conn->prepare($update_sql)) {
                                $update_stmt->bind_param("iss", $otp, $otp_expiry, $email);
                                $update_stmt->execute();
                                
                                // Send Email
                                $subject = "Your Login Verification Code";
                                $body = "Your login verification code is: <b>$otp</b><br>Valid for 10 minutes.";
                                
                                if (send_email($email, $subject, $body)) {
                                    // Redirect to the login verification page
                                    header("location: login_verify.php?email=" . urlencode($email));
                                    exit();
                                } else {
                                    $error = "Could not send verification email. Please try again.";
                                }
                            }
                        }
                    } else { 
                        $error = "The email or password you entered was not valid.";
                    }
                }
            } else { 
                $error = "The email or password you entered was not valid.";
            }
        } else {
            $error = "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
}
$conn->close();

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Login</h2>
    <?php if($message): ?><div class="form-message success"><?php echo $message; ?></div><?php endif; ?>
    <?php if($error): ?><div class="form-message error"><?php echo $error; ?></div><?php endif; ?>
    
    <form action="login.php" method="post">
        <label for="email">Email</label>
        <input type="email" name="email" required>
        
        <label for="password">Password</label>
        <div class="password-wrapper">
            <input type="password" name="password" required>
            <button type="button" class="toggle-password-btn"><i class="fas fa-eye"></i></button>
        </div>
        
        <div style="text-align: right; margin-bottom: 15px;">
            <a href="forgot_password.php" style="font-size: 0.9rem; color: #007bff; text-decoration: none;">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">Get OTP</button>
    </form>
    
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php include 'includes/footer.php'; ?>