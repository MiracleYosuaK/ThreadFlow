<?php
// Include the config file to load the credentials
require_once 'config/smpt.php';  // Make sure this is at the top of your file
require_once 'controller/connection1.php'; // Assuming you have a connection to your database

// Include PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include Composer's autoload if you're using Composer
require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate a unique reset token and expiration time
        $reset_token = bin2hex(random_bytes(32)); // Generate a random token
        $reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Set expiration time to 1 hour

        // Store the reset token and expiration in the database
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->bind_param("sss", $reset_token, $reset_token_expires, $email);
        $stmt->execute();

        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;            // Fetch the SMTP host from config.php
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;    // Fetch the SMTP username from config.php
            $mail->Password = SMTP_PASSWORD;    // Fetch the SMTP password from config.php
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;            // Fetch the SMTP port from config.php

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);  // From email and name
            $mail->addAddress($email);                       // Recipient's email

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $reset_link = "https://yourdomain.com/resetpassword.php?token=" . $reset_token;
            $mail->Body = "Hello,<br><br>Click the link below to reset your password:<br><br><a href='" . $reset_link . "'>Reset your password</a><br><br>This link will expire in 1 hour.";

            // Send the email
            $mail->send();
            echo "<p style='color: #4caf50;'>If your email address is in our system, you will receive a password reset link shortly.</p>";
        } catch (Exception $e) {
            echo "<p style='color: #f44336;'>Failed to send email. Mailer Error: {$mail->ErrorInfo}</p>";
        }
    } else {
        echo "<p style='color: #f44336;'>Email address not found.</p>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="shortcut icon" href="Images/panda.ico" />  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThreadFlow - Forgot Password</title>
    <link rel="stylesheet" href="CSS/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
</head>
<body>
    <div class="forgot-password-container">
        <div class="logo">
            <img src="/Images/logo.png" alt="Logo">
        </div>
        <h1>Forgot Password</h1>
        <p class="subtitle">Enter your email to receive a password reset link.</p>
        <div class="forgot-password-box">
            <form action="forgetpassword.php" method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Send Reset Link</button>
            </form>
        </div>
        <div class="extra-links">
            <a href="login.php">Remember your password? Login</a>
        </div>
    </div>
</body>
</html>
