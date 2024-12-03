<?php
// Include the connection file securely
include 'controller/connection1.php';

// Start the session with secure session handling

ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookies
ini_set('session.use_only_cookies', 1); // Use cookies for session handling only
session_start();
session_regenerate_id(true); // Regenerate session ID to prevent session fixation

// Get the post ID from the URL and validate it
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) {
    echo "Invalid post ID.";
    exit;
}

// Query to get the post details
$sql_post = "SELECT * FROM posts WHERE post_id = ?";
$stmt = $conn->prepare($sql_post);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post_result = $stmt->get_result();
$post = $post_result->fetch_assoc();

if (!$post) {
    echo "Post not found.";
    exit;
}

// Update view count
$sql_update_views = "UPDATE posts SET views = views + 1 WHERE post_id = ?";
$stmt_update_views = $conn->prepare($sql_update_views);
$stmt_update_views->bind_param("i", $post_id);
$stmt_update_views->execute();

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new CSRF token
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['username'])) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Validate and sanitize the comment input
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        // Get the current time for the comment
        $comment_time = date('Y-m-d H:i:s');
        $user_id = $_SESSION['user_id']; // Assuming user_id is stored in session

        // Insert the new comment into the database
        $sql_insert_comment = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, ?)";
        $stmt_insert_comment = $conn->prepare($sql_insert_comment);
        $stmt_insert_comment->bind_param("iiss", $post_id, $user_id, $comment, $comment_time);
        $stmt_insert_comment->execute();

        // Redirect to avoid form resubmission
        header("Location: post.php?id=$post_id");
        exit;
    }
}

// Separate post content and comments
list($post_content, $comments) = explode("<!-- COMMENTS -->", $post['content'] . "\n<!-- COMMENTS -->");

// Query to get all comments for the current post
$sql_comments = "SELECT c.comment_id, c.content, c.created_at, u.username FROM comments c INNER JOIN users u ON c.user_id = u.user_id WHERE c.post_id = ? ORDER BY c.created_at DESC";
$stmt_comments = $conn->prepare($sql_comments);
$stmt_comments->bind_param("i", $post_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - ThreadFlow</title>
    <link rel="stylesheet" href="/CSS/post-styles.css">
</head>
<body>

    <!-- Header -->
    <header>
    <a href="home.php" class="header-logo">
        <h1>ThreadFlow</h1>
    </a>
    <div class="header-right">
        <?php
        if (isset($_SESSION['username'])) {
            $loggedInUser = htmlspecialchars($_SESSION['username']);
            echo "<span class='user-name'>Welcome, $loggedInUser</span>";
        } else {
            echo "<span class='user-name'><a href='login.php'>Login</a> / <a href='register.php'>Register</a></span>";
        }
        ?>
    </div>
</header>

    <!-- Main Content -->
    <main>
        <article>
            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
            <div class="post-meta">
                <span><strong>By:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="post-date"><?php echo htmlspecialchars($post['created_at']); ?></span>
            </div>
            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post_content)); ?>
            </div>
            <p><strong>Views:</strong> <?php echo $post['views']; ?> | <strong>Replies:</strong> <?php echo $post['replies']; ?></p>
        </article>

        <!-- Comment Section -->
        <section id="comments">
            <h3>Comments</h3>
            <?php if (isset($_SESSION['username'])): ?>
                <!-- Form for adding a comment -->
                <form method="POST">
                    <textarea name="comment" rows="4" placeholder="Write your comment here..." required></textarea>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit">Post Comment</button>
                </form>
            <?php else: ?>
                <p><strong>You must <a href="login.php">login</a> to comment.</strong></p>
            <?php endif; ?>

            <!-- Display Comments -->
            <ul class="comment-list">
                <?php
                if ($comments_result->num_rows > 0) {
                    while ($comment = $comments_result->fetch_assoc()) {
                        echo "<li><div class='comment-text'>" . nl2br(htmlspecialchars($comment['content'])) . "</div>";
                        echo "<div class='comment-meta'><strong>" . htmlspecialchars($comment['username']) . "</strong> on " . htmlspecialchars($comment['created_at']) . "</div></li>";
                    }
                } else {
                    echo "<p>No comments yet.</p>";
                }
                ?>
            </ul>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <t>&copy; 2024 ThreadFlow</t>
    </footer>

</body>
</html>
