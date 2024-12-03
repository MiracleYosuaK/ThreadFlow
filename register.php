<?php
session_start();

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a CSRF token
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="shortcut icon" href="Images/panda.ico" /> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThreadFlow - Register</title>
    <link rel="stylesheet" href="CSS/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
</head>
<body>
    <div class="register-container">
        <div class="logo1">
            <img src="/Images/logo.png" alt="Logo">
        </div>
        <h1>Create Account</h1>
        <p class="subtitle">Join ThreadFlow today!</p>

        <!-- Display error if available -->
        <?php if (isset($_SESSION['error'])): ?>
            <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php unset($_SESSION['error']); // Clear error after displaying ?>
        <?php endif; ?>

        <div class="login-box">
            <form action="/controller/DoRegister.php" method="POST">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm-password" placeholder="Confirm Password" required>
                
                <!-- Password Requirements Message -->
                <p class="password-requirements">
                    Password must be at least 8 characters long, contain at least one uppercase letter, one number, and one special character.
                </p>

                <button type="submit">Register</button>
            </form>
        </div>
        <div class="extra-links">
            <a href="/login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>
