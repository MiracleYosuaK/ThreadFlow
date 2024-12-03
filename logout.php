<?php
session_start();

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Regenerate session ID to prevent session fixation
session_start();
session_regenerate_id(true); // Regenerate the session ID to prevent session hijacking

$redirect_delay = 3; // Redirect delay in seconds
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - ThreadFlow</title>
    <link rel="stylesheet" href="/CSS/styles.css"> <!-- Include your CSS -->
</head>
<body>
    <div class="logout-container">
        <h1>You have successfully logged out</h1>
        <p>Thank you for using ThreadFlow. You will be redirected to the login page in a few seconds...</p>
        <p>If not, click <a href="login.php">here</a>.</p>
    </div>

    <!-- Server-side redirect -->
    <?php
        // Redirect after the delay using a PHP header to prevent JavaScript manipulation
        header("refresh: $redirect_delay; url=login.php");
    ?>
</body>
</html>
