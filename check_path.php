<?php

echo '<h1>PHP Path Test</h1>';
echo '<p>This test will check if PHP can find the autoload.php file from the root directory.</p>';
echo '<hr>';

// --- Test Starts Here ---

// Define the exact path to the file we need
$filePath = __DIR__ . '/vendor/autoload.php';

echo '<strong>Checking for file at this exact path:</strong><br>';
echo $filePath;
echo '<br><br>';

// Check if the file exists and is readable
if (file_exists($filePath)) {
    echo '<h2 style="color: green;">SUCCESS!</h2>';
    echo '<p>The file was found and is accessible. This means your folder structure is correct.</p>';
    echo '<p>The problem MUST be a typo or an unsaved change in either <strong>register.php</strong> or <strong>includes/send_mail.php</strong>.</p>';
    echo '<p>Please try replacing the code in those two files one more time from my previous message.</p>';
} else {
    echo '<h2 style="color: red;">ERROR!</h2>';
    echo '<p>The file was NOT found at that path.</p>';
    echo '<p>This confirms that the <strong>vendor</strong> folder or the <strong>autoload.php</strong> file is missing from your main project directory.</p>';
    echo '<p><strong>Solution:</strong> Please delete your current `vendor` folder and the `composer.lock` file, then run `composer install` again in your terminal.</p>';
}

?>