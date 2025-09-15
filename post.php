<?php
include 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = $_GET['id'];

// Get the specific post with category information
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.color as category_color, c.slug as category_slug
                       FROM posts p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: index.php');
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_comment'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $comment = trim($_POST['comment']);

    if (!empty($name) && !empty($email) && !empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, name, email, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $name, $email, $comment]);
        $comment_success = "Your comment has been submitted and is awaiting moderation.";
    } else {
        $comment_error = "All fields are required.";
    }
}

// Get approved comments for this post
$stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($post['title']); ?> - Simple Blog</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">Simple Blog</a></h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="admin.php">Admin</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <article class="post-full">
            <?php if ($post['category_name']): ?>
                <div class="post-category">
                    <span class="category-badge" style="background-color: <?php echo htmlspecialchars($post['category_color']); ?>">
                        <?php echo htmlspecialchars($post['category_name']); ?>
                    </span>
                </div>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                Posted on <?php echo date('F j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                <?php if ($post['updated_at'] != $post['created_at']): ?>
                    | Updated on <?php echo date('F j, Y \a\t g:i A', strtotime($post['updated_at'])); ?>
                <?php endif; ?>
            </div>
            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
        </article>

        <div class="post-navigation">
            <a href="index.php" class="back-link">&larr; Back to Home</a>
        </div>

        <!-- Comments Section -->
        <section class="comments-section">
            <h3>Comments (<?php echo count($comments); ?>)</h3>

            <!-- Display Comments -->
            <?php if (!empty($comments)): ?>
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                <span class="comment-date"><?php echo date('F j, Y \a\t g:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-comments">No comments yet. Be the first to comment!</p>
            <?php endif; ?>

            <!-- Comment Form -->
            <div class="comment-form">
                <h4>Leave a Comment</h4>

                <?php if (isset($comment_success)): ?>
                    <div class="success"><?php echo $comment_success; ?></div>
                <?php endif; ?>

                <?php if (isset($comment_error)): ?>
                    <div class="error"><?php echo $comment_error; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Comment *</label>
                        <textarea name="comment" rows="5" required></textarea>
                    </div>
                    <button type="submit" name="submit_comment">Submit Comment</button>
                </form>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Simple Blog. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>