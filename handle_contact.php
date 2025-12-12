<?php
// We only need the mail function here. db.php is not required.
require_once 'includes/send_mail.php';

// --- CONFIGURATION ---
// Set the admin email where you want to receive messages
$admin_email = 'adityapanja3002@gmail.com';
// ---------------------

$message_status = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize and retrieve form data
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $mobile = filter_var(trim($_POST['mobile']), FILTER_SANITIZE_STRING);
    $subject = filter_var(trim($_POST['subject']), FILTER_SANITIZE_STRING);
    $message = filter_var(trim($_POST['message']), FILTER_SANITIZE_STRING);

    // 2. Validate data
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        // Basic validation for required fields
        $message_status = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check for valid email format
        $message_status = 'error_email';
    } else {
        // 3. Prepare the email
        $email_subject = "New Contact Form Submission: " . $subject;
        
        $email_body = "<html><body>";
        $email_body .= "<h2>New Message from Your Website Contact Form</h2>";
        $email_body .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
        $email_body .= "<p><strong>Email (Reply-To):</strong> " . htmlspecialchars($email) . "</p>";
        $email_body .= "<p><strong>Mobile:</strong> " . htmlspecialchars($mobile) . "</p>";
        $email_body .= "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
        $email_body .= "<h3>Message:</h3>";
        $email_body .= "<p>" . nl2br(htmlspecialchars($message)) . "</p>"; // nl2br converts newlines to <br>
        $email_body .= "</body></html>";
        
        // 4. Send the email using our new function
        if (send_email($admin_email, $email_subject, $email_body, $email, $name)) {
            // Success
            $message_status = 'success';
        } else {
           // Failure
            // echo "<h1>Email Send Failed!</h1>"; // <-- REMOVE THIS
            // echo "<p>The redirect was stopped...</p>"; // <-- REMOVE THIS
            // die(); // <-- REMOVE THIS
            $message_status = 'error';
        }
    }
    
    // 5. Redirect back to the index page with a status message
    header("Location: index.php?contact=" . $message_status . "#contact-us");
    exit();
    
} else {
    // If someone tries to access this file directly, redirect them to the homepage
    header("Location: index.php");
    exit();
}
?>