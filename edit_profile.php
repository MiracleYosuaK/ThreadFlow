<?php


// Secure session settings
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to session cookies
ini_set('session.use_only_cookies', 1); // Only use cookies for sessions
session_start();
session_regenerate_id(true);             // Regenerate session ID to prevent session fixation

// Include the database connection securely
include('controller/connection1.php');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$loggedInUser = $_SESSION['username'];

// CSRF Token validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    // Handle the file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $uploadedFile = $_FILES['profile_picture'];
        $fileName = basename($uploadedFile['name']);
        $filePath = 'uploads/' . $fileName;

        // Check file type (e.g., only allow jpg, jpeg, png)
        $allowedFileTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($uploadedFile['tmp_name']);
        
        if (in_array($fileType, $allowedFileTypes)) {
            // Check file size (limit to 2MB)
            if ($uploadedFile['size'] <= 2 * 1024 * 1024) {
                // Move the uploaded file to the 'uploads' directory
                if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                    // Update the database with the new profile picture
                    $query = "UPDATE users SET profile_picture = ? WHERE username = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('ss', $fileName, $loggedInUser);
                    $stmt->execute();

                    // Update the session with the new profile picture
                    $_SESSION['profile_picture'] = $fileName; // Update session with new profile picture

                    // Redirect to home.php after a successful upload
                    header('Location: home.php');
                    exit();
                } else {
                    echo "<p class='error-message'>Error uploading file. Please try again.</p>";
                }
            } else {
                echo "<p class='error-message'>File is too large. Maximum size is 2MB.</p>";
            }
        } else {
            echo "<p class='error-message'>Invalid file type. Only JPG, JPEG, and PNG are allowed.</p>";
        }
    }
}

// Fetch user data
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $loggedInUser);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - ThreadFlow</title>
    <link rel="stylesheet" href="CSS/edit_profile.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Your Profile</h1>
        </header>

        <div class="form-container">
            <h2>Change Your Profile Picture</h2>
            <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="profile_picture">Upload New Profile Picture:</label>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                </div>
                <button type="submit" class="submit-btn">Save Changes</button>
            </form>
        </div>

        <footer>
            <p>&copy; 2024 ThreadFlow</p>
        </footer>
    </div>
</body>
</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>
