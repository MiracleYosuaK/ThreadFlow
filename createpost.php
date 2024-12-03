<?php

// Secure session handling
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript from accessing session cookies
ini_set('session.use_only_cookies', 1);  // Only allow session cookies, not URL parameters
session_start();
session_regenerate_id(true);  // Regenerate session ID to prevent session fixation


// Include database connection
require_once 'controller/connection1.php'; // Include the database configuration file

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit(); // Redirect to login page if the user is not logged in
}

// Inisialisasi variabel untuk error handling
$title = '';
$content = '';
$category_id = '';
$error = '';

// Ambil daftar kategori untuk dropdown
$query = "SELECT category_id, category_name FROM categories";
$result = $conn->query($query);

$categories = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row; // Store categories in an array
    }
} else {
    $_SESSION['error'] = "Failed to fetch categories from the database.";
    header('Location: ../createpost.php');
    exit(); // Redirect if categories can't be fetched
}

// Simpan kategori ke session untuk digunakan di halaman create.php
$_SESSION['categories'] = $categories;

// CSRF Token Generation and Validation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new token if not already set
}

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Trim and sanitize user inputs
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $category_id = $_POST['category'];

    // Validasi input
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "Title and Content are required."; // Error message
    } elseif (!ctype_digit($category_id)) {
        $_SESSION['error'] = "Invalid category selected."; // Invalid category ID
    } else {
        // Siapkan statement untuk menambahkan post
        $stmt = $conn->prepare("INSERT INTO posts (user_id, category_id, title, content) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iiss", $_SESSION['user_id'], $category_id, $title, $content);

            // Eksekusi statement
            if ($stmt->execute()) {
                // Clear previous session data
                unset($_SESSION['error'], $_SESSION['title'], $_SESSION['content'], $_SESSION['category_id'], $_SESSION['categories']);
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);
                header('Location: ../home.php'); // Redirect to homepage after successful post
                exit();
            } else {
                $_SESSION['error'] = "Failed to submit your post. Please try again.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Failed to prepare the database query."; // Error preparing statement
        }
    }

    // Simpan input form sebelumnya ke session untuk repopulasi jika terjadi error
    $_SESSION['title'] = $title;
    $_SESSION['content'] = $content;
    $_SESSION['category_id'] = $category_id;

    // Redirect kembali ke form jika ada error
    header('Location: ../createpost.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>  
    <link rel="shortcut icon" href="Images/panda.ico" /> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Post - ThreadFlow</title>
    <link rel="stylesheet" href="/CSS/createpost.css"> <!-- Asumsi ada file CSS terpisah -->
</head>
<body>

<header>
    <h1>ThreadFlow</h1>
    <div class="header-right">
        <input type="text" class="search-box" placeholder="Cari di ThreadFlow...">
        <nav>
            <a href="home.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </nav>
    </div>
</header>

<main>
    <div class="section">
    <form action="createpost.php" method="POST">
        <h2>Create New Post</h2>

        <!-- Menampilkan error jika ada -->
        <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
            <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
        <?php endif; ?>

        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?php echo isset($_SESSION['title']) ? htmlspecialchars($_SESSION['title']) : ''; ?>" required>
        
        <label for="category">Category:</label>
        <select name="category" id="category" required>
            <option value="">Select Category</option>
            <!-- Assuming categories are preloaded via PHP -->
            <?php foreach ($_SESSION['categories'] as $category): ?>
                <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($_SESSION['category_id']) && $_SESSION['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="content">Content:</label>
        <textarea name="content" id="content" rows="8" required><?php echo isset($_SESSION['content']) ? htmlspecialchars($_SESSION['content']) : ''; ?></textarea>

        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <button type="submit">Submit Post</button>
    </form>
    </div>
</main>

<footer>
    <p><a href="#about-us">About Us</a> | <a href="#contact-us">Contact Us</a></p>
</footer>

</body>
</html>
