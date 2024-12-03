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

// Get logged-in user's username
$loggedInUser = $_SESSION['username'];

// Fetch user data from the database using a prepared statement
$query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $loggedInUser); // 's' means the parameter is a string
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found!";
    exit();
}

// Set default profile picture if not set or if file does not exist
$profilePicture = $user['profile_picture'] && file_exists('uploads/' . $user['profile_picture']) ? $user['profile_picture'] : 'default-picture.jfif';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ThreadFlow</title>
    <link rel="stylesheet" href="/CSS/index-styles.css">
    <style>
        .profile-pic {
            width: 100px; /* Lebih kecil */
            height: 100px; /* Sesuaikan dengan width */
            border-radius: 50%; /* Membuatnya bulat */
            object-fit: cover; /* Menjaga proporsi gambar */
            border: 2px solid #fff; /* Opsional: Border putih */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Opsional: bayangan */
        }
    </style>
</head>
<body>
    <header>
        <h1>ThreadFlow</h1>
        <div class="header-right">
            <span class="user-name">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <main>
        <h2>Your Profile</h2>
        <div class="profile-section">
            <!-- Menampilkan gambar profil yang diperbarui -->
            <img src="uploads/<?php echo htmlspecialchars($profilePicture); ?>?<?php echo time(); ?>" alt="Profile Picture" class="profile-pic">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Joined:</strong> <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
        </div>

        <button onclick="window.location.href='edit_profile.php'">Edit Profile</button>
    </main>

    <footer>
        <div class="footer-content">
            <h3>About Us</h3>
            <p>
                ThreadFlow is a modern discussion forum platform designed to connect people through meaningful conversations. 
                We provide a space for users to share ideas, news, and experiences across various fields such as technology, local events, and general discussions.
            </p>
            <p>&copy; 2024 ThreadFlow - Copyright</p>
        </div>
    </footer>

</body>
</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>
