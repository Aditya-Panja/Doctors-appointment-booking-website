<?php
// Include PHPMailer classes at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Use a robust absolute path to load the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

/**
 * Main function to send an email using Gmail SMTP.
 */
function send_email($toEmail, $subject, $body, $replyToEmail = null, $replyToName = '') {
    $mail = new PHPMailer(true);

    try {
        // --- Server Settings ---
        // DEBUGGING IS ENABLED: This will print the full server log.
        $mail->SMTPDebug = 0;

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // --- IMPORTANT: REPLACE WITH YOUR CREDENTIALS ---
        $mail->Username   = 'adityapanja3002@gmail.com';         // Your full Gmail address
        $mail->Password   = 'vnqv mees pxge mqee'; // Your 16-character Google App Password
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- Sender & Recipients ---
        // THIS IS NOW FIXED. It must match your Username.
        $mail->setFrom('adityapanja3002@gmail.com', 'Doctor Appointment System');
        $mail->addAddress($toEmail);

        // --- Reply-To (Crucial for contact forms) ---
        if ($replyToEmail) {
            $mail->addReplyTo($replyToEmail, $replyToName);
        }

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Create a plain-text version

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Sends an OTP email to a specified recipient.
 */
function send_otp_email($recipient_email, $otp) {
    $subject = 'Your OTP for Account Verification';
    $body    = 'Hello,<br><br>Thank you for registering. Your One-Time Password (OTP) is: <b>' . $otp . '</b><br><br>This code is valid for 10 minutes.<br><br>Regards,<br>The Team';
    
    // Call the main email function
    return send_email($recipient_email, $subject, $body);
}