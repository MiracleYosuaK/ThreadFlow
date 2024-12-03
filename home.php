<?php

// Secure session settings
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to session cookies
ini_set('session.use_only_cookies', 1); // Only use cookies for sessions
session_start();
session_regenerate_id(true);             // Regenerate session ID to prevent session fixation

// Include the database connection securely
include('controller/connection1.php');

// Set the default page number and items per page for all posts
$items_per_page = 10;
$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
$page = $page ? $page : 1; // Default to page 1 if validation fails
$offset = ($page - 1) * $items_per_page;

// Capture and sanitize the search query if provided
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); // Prevent XSS

// Get the sort option (default to latest)
$sort_option = isset($_GET['order']) ? $_GET['order'] : 'latest';

// Determine the SQL ORDER BY clause based on the sort option
switch ($sort_option) {
    case 'popular':
        $order_by = 'ORDER BY posts.views DESC'; // Sort by most views (popularity)
        break;
    case 'trending':
        $order_by = 'ORDER BY posts.replies DESC'; // Sort by most replies (trending)
        break;
    case 'latest':
    default:
        $order_by = 'ORDER BY posts.created_at DESC'; // Sort by latest posts
        break;
}

// Modify the query to filter based on search query if provided
$search_condition = $search_query ? "WHERE posts.title LIKE ? OR posts.content LIKE ?" : '';

// Prepare the query for fetching the latest posts (3 posts for the "Latest Posts" section)
$latest_result = null; // Initialize as null to avoid undefined variable errors
if (!$search_query) {  // Only query the latest posts if no search is active
    $sql_latest = "
        SELECT 
            posts.post_id, 
            posts.title, 
            posts.content, 
            posts.created_at AS post_created_at,
            categories.category_name, 
            users.username AS author_name,
            users.profile_picture
        FROM 
            posts
        JOIN 
            categories ON posts.category_id = categories.category_id
        JOIN 
            users ON posts.user_id = users.user_id
        $search_condition
        $order_by
        LIMIT 3
    ";

    $stmt = $conn->prepare($sql_latest);
    if ($search_query) {
        $search_like = "%$search_query%";
        $stmt->bind_param('ss', $search_like, $search_like); // Bind search parameters
    }
    $stmt->execute();
    $latest_result = $stmt->get_result();
}

// Query to get all posts from all users, sorted by creation date, with pagination
$sql_all_posts = "
    SELECT 
        posts.post_id, 
        posts.title, 
        posts.content, 
        posts.created_at AS post_created_at,
        categories.category_name, 
        users.username AS author_name,
        users.profile_picture
    FROM 
        posts
    JOIN 
        categories ON posts.category_id = categories.category_id
    JOIN 
        users ON posts.user_id = users.user_id
    $search_condition
    $order_by
    LIMIT ? OFFSET ?
";

$stmt_all_posts = $conn->prepare($sql_all_posts);
if ($search_query) {
    $search_like = "%$search_query%";
    // Correct parameter binding for search query (both title and content fields)
    $stmt_all_posts->bind_param('ssii', $search_like, $search_like, $items_per_page, $offset);
} else {
    // If there's no search query, bind only the pagination parameters
    $stmt_all_posts->bind_param('ii', $items_per_page, $offset);
}
$stmt_all_posts->execute();
$result_all_posts = $stmt_all_posts->get_result();

// Get total number of posts to calculate pagination for the all posts section
$sql_count = "SELECT COUNT(*) AS total_posts FROM posts $search_condition";
$stmt_count = $conn->prepare($sql_count);
if ($search_query) {
    $stmt_count->bind_param('ss', $search_like, $search_like); // Bind search parameters for count query
}
$stmt_count->execute();
$total_result = $stmt_count->get_result();
$total_row = $total_result->fetch_assoc();
$total_posts = $total_row['total_posts'];
$total_pages = ceil($total_posts / $items_per_page);

// Get all categories from the database for filtering
$category_sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$category_result = $conn->query($category_sql);

// Check if user is logged in
$is_logged_in = isset($_SESSION['username']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="shortcut icon" href="Images/panda.ico" /> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThreadFlow - Forum Diskusi Modern</title>
    <link rel="stylesheet" href="/CSS/index-styles.css">
    <link rel="stylesheet" href="/CSS/modal-styles.css"> <!-- Link to the new CSS file -->
</head>
<body>

    <!-- Header -->
    <header>
        <a href="home.php">
            <h1>ThreadFlow</h1>
        </a>
        <div class="header-right">
            <form method="GET" action="home.php">
                <input type="text" class="search-box" name="search" placeholder="Search here..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-button">Search</button>
            </form>
            <nav>
                <?php if ($is_logged_in): ?>
                    <div class="user-info">
                        <a href="profile.php">
                            <img src="uploads/<?php echo htmlspecialchars($_SESSION['profile_picture'] ?? 'default-picture.jfif'); ?>?<?php echo time(); ?>" alt="Profile Picture" class="profile-picture">
                        </a>
                        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> | <a href="/logout.php">Logout</a></p>
                    </div>
                <?php else: ?>
                    <a href="/login.php">Login</a> | <a href="/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Subheader -->
    <div class="subheader">
        <button onclick="window.location.href='createpost.php'">Create Post</button>
        <button onclick="refreshPage()">Refresh</button>
        <label for="order">Order:</label>
        <select id="order" onchange="window.location.href='home.php?order=' + this.value">
            <option value="latest" <?php echo ($sort_option == 'latest') ? 'selected' : ''; ?>>Latest</option>
            <option value="popular" <?php echo ($sort_option == 'popular') ? 'selected' : ''; ?>>Popular</option>
            <option value="trending" <?php echo ($sort_option == 'trending') ? 'selected' : ''; ?>>Trending</option>
        </select>
        <label for="categories">Categories:</label>
        <select id="categories" onchange="window.location.href='home.php?category=' + this.value">
            <option value="all">All Categories</option>
            <?php while ($category = $category_result->fetch_assoc()): ?>
                <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Main Content Section -->
    <main>
        <!-- Remove Latest Posts section when search is active -->
        <?php if ($page == 1 && !$search_query): ?>  
        <div class="section" id="latest-posts">
            <h2>Latest Posts</h2>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Author</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Display the latest 3 posts
                        if ($latest_result && $latest_result->num_rows > 0) {
                            while ($post = $latest_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><a href='post.php?id=" . htmlspecialchars($post['post_id']) . "'>" . htmlspecialchars($post['title']) . "</a></td>";
                                echo "<td>" . htmlspecialchars($post['author_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($post['post_created_at']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No posts available yet. Be the first to create a post!</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- All Posts Section with Pagination -->
        <div class="section" id="all-posts">
            <h2>All Posts</h2>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Display all posts with pagination
                        if ($result_all_posts->num_rows > 0) {
                            while ($post = $result_all_posts->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><a href='post.php?id=" . htmlspecialchars($post['post_id']) . "'>" . htmlspecialchars($post['title']) . "</a></td>";
                                echo "<td>" . htmlspecialchars($post['author_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($post['post_created_at']) . "</td>";
                                echo "<td>" . htmlspecialchars($post['category_name']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No posts available.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="home.php?page=<?php echo $page - 1; ?>&order=<?php echo $sort_option; ?>">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="home.php?page=<?php echo $i; ?>&order=<?php echo $sort_option; ?>" <?php echo ($i == $page) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="home.php?page=<?php echo $page + 1; ?>&order=<?php echo $sort_option; ?>">Next</a>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 ThreadFlow. All rights reserved.</p>
    </footer>

    <script src="JS/index.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
