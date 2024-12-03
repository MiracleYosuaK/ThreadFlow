<?php
session_start();

// Include database connection
require_once './connection.php';

// Function to sanitize input to prevent XSS
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header('Location: ../register.php');
        exit();
    }

    // Sanitize form inputs
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm-password']);

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters long, contain at least one uppercase letter, one number, and one special character.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } else {
        // Check if username already exists in the database
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "This username is already taken.";
        } else {
            // Check if email already exists in the database
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $email_result = $stmt->get_result();

            if ($email_result->num_rows > 0) {
                $_SESSION['error'] = "This email is already registered.";
            } else {
                // Hash the password using bcrypt
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Prepare SQL query to insert new user data
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $hashed_password);

                if ($stmt->execute()) {
                    // Clear session error and redirect to login page after successful registration
                    unset($_SESSION['error']);
                    session_regenerate_id(true); // Regenerate session ID to prevent session hijacking
                    header('Location: ../login.php');
                    exit();
                } else {
                    $_SESSION['error'] = "There was an error registering your account.";
                }
            }
        }

        $stmt->close();
    }

    // Redirect back to the registration page with error message
    header('Location: ../register.php');
    exit();
}

$conn->close();
?>
