<?php
// Set the new password you want to use
$newPassword = 'newpassword123';

// Hash the new password securely
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Display the password and the hash
echo '<h3>Password Reset Tool</h3>';
echo 'Your new password is: <strong>' . $newPassword . '</strong><br><br>';
echo 'Copy this entire hash string below:<br>';
echo '<textarea rows="4" cols="70" readonly>' . $hashedPassword . '</textarea>';
?>