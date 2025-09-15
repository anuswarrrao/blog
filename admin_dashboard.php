<?php
// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_post'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

        $stmt = $pdo->prepare("INSERT INTO posts (title, content, category_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $category_id]);
        $success = "Post created successfully!";
    }

    if (isset($_POST['update_post'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, category_id = ? WHERE id = ?");
        $stmt->execute([$title, $content, $category_id, $id]);
        $success = "Post updated successfully!";
    }

    if (isset($_POST['delete_post'])) {
        $id = $_POST['id'];

        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Post deleted successfully!";
    }

    // Handle comment moderation
    if (isset($_POST['approve_comment'])) {
        $id = $_POST['comment_id'];
        $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Comment approved successfully!";
    }

    if (isset($_POST['reject_comment'])) {
        $id = $_POST['comment_id'];
        $stmt = $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Comment rejected successfully!";
    }

    if (isset($_POST['delete_comment'])) {
        $id = $_POST['comment_id'];
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Comment deleted successfully!";
    }

    // Handle contact message actions
    if (isset($_POST['mark_read'])) {
        $id = $_POST['message_id'];
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Message marked as read!";
    }

    if (isset($_POST['mark_unread'])) {
        $id = $_POST['message_id'];
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'unread' WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Message marked as unread!";
    }

    if (isset($_POST['delete_message'])) {
        $id = $_POST['message_id'];
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Message deleted successfully!";
    }

    // Handle banner management
    if (isset($_POST['update_banner'])) {
        $title = $_POST['banner_title'];
        $subtitle = $_POST['banner_subtitle'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $image_path = null;

        // Handle file upload
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB

            $file_info = $_FILES['banner_image'];
            $file_type = $file_info['type'];
            $file_size = $file_info['size'];

            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
                $new_filename = 'banner_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
                    $image_path = $upload_path;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type or size. Please upload an image under 5MB.";
            }
        }

        if (!isset($error)) {
            // Get current banner to check if we need to update image
            $stmt = $pdo->query("SELECT * FROM banner_settings ORDER BY id DESC LIMIT 1");
            $current_banner = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($current_banner) {
                // Update existing banner
                if ($image_path) {
                    // Delete old image if exists
                    if ($current_banner['image_path'] && file_exists($current_banner['image_path'])) {
                        unlink($current_banner['image_path']);
                    }
                    $stmt = $pdo->prepare("UPDATE banner_settings SET title = ?, subtitle = ?, image_path = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$title, $subtitle, $image_path, $is_active, $current_banner['id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE banner_settings SET title = ?, subtitle = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$title, $subtitle, $is_active, $current_banner['id']]);
                }
            } else {
                // Create new banner
                $stmt = $pdo->prepare("INSERT INTO banner_settings (title, subtitle, image_path, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $subtitle, $image_path, $is_active]);
            }
            $success = "Banner updated successfully!";
        }
    }

    // Handle category management
    if (isset($_POST['create_category'])) {
        $name = trim($_POST['category_name']);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['category_slug'])));
        $description = trim($_POST['category_description']);
        $color = $_POST['category_color'];

        if (!empty($name) && !empty($slug)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)");
            try {
                $stmt->execute([$name, $slug, $description, $color]);
                $success = "Category created successfully!";
            } catch (PDOException $e) {
                $error = "Category name or slug already exists.";
            }
        } else {
            $error = "Category name and slug are required.";
        }
    }

    if (isset($_POST['update_category'])) {
        $id = $_POST['category_id'];
        $name = trim($_POST['category_name']);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['category_slug'])));
        $description = trim($_POST['category_description']);
        $color = $_POST['category_color'];

        if (!empty($name) && !empty($slug)) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, color = ? WHERE id = ?");
            try {
                $stmt->execute([$name, $slug, $description, $color, $id]);
                $success = "Category updated successfully!";
            } catch (PDOException $e) {
                $error = "Category name or slug already exists.";
            }
        } else {
            $error = "Category name and slug are required.";
        }
    }

    if (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];

        // Check if category has posts
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ?");
        $stmt->execute([$id]);
        $post_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($post_count > 0) {
            $error = "Cannot delete category that has posts assigned to it.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Category deleted successfully!";
        }
    }
}

// Get all posts for listing with category information
$stmt = $pdo->query("SELECT p.*, c.name as category_name, c.color as category_color
                     FROM posts p
                     LEFT JOIN categories c ON p.category_id = c.id
                     ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific post for editing
$edit_post = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_post = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all comments with post titles
$stmt = $pdo->query("SELECT c.*, p.title as post_title
                     FROM comments c
                     JOIN posts p ON c.post_id = p.id
                     ORDER BY c.created_at DESC");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending comments count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'");
$pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get all contact messages
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$contact_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread messages count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");
$unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get current banner settings
$stmt = $pdo->query("SELECT * FROM banner_settings ORDER BY id DESC LIMIT 1");
$banner = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific category for editing
$edit_category = null;
if (isset($_GET['edit_category']) && is_numeric($_GET['edit_category'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit_category']]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Simple Blog</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">Simple Blog</a></h1>
            <nav>
                <span>Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                <a href="admin.php?logout=1">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Admin Dashboard</h2>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Dashboard Navigation -->
        <div class="admin-nav">
            <a href="#posts" class="nav-link active" onclick="showSection('posts')">Posts</a>
            <a href="#categories" class="nav-link" onclick="showSection('categories')">Categories</a>
            <a href="#comments" class="nav-link" onclick="showSection('comments')">Comments
                <?php if ($pending_count > 0): ?>
                    <span class="badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="#messages" class="nav-link" onclick="showSection('messages')">Messages
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="#banner" class="nav-link" onclick="showSection('banner')">Banner</a>
        </div>

        <!-- Posts Section -->
        <div id="posts-section" class="admin-section">
            <!-- Create/Edit Post Form -->
            <div class="admin-form">
                <h3><?php echo $edit_post ? 'Edit Post' : 'Create New Post'; ?></h3>
                <form method="post">
                    <?php if ($edit_post): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_post['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" name="title" value="<?php echo $edit_post ? htmlspecialchars($edit_post['title']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category_id">
                            <option value="">Select a category (optional)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                    <?php echo ($edit_post && $edit_post['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Content:</label>
                        <textarea name="content" rows="10" required><?php echo $edit_post ? htmlspecialchars($edit_post['content']) : ''; ?></textarea>
                    </div>

                    <button type="submit" name="<?php echo $edit_post ? 'update_post' : 'create_post'; ?>">
                        <?php echo $edit_post ? 'Update Post' : 'Create Post'; ?>
                    </button>

                    <?php if ($edit_post): ?>
                        <a href="admin.php" class="cancel-btn">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Posts List -->
            <div class="admin-posts">
                <h3>All Posts</h3>
                <?php if (empty($posts)): ?>
                    <p>No posts available.</p>
                <?php else: ?>
                    <table class="posts-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <a href="post.php?id=<?php echo $post['id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($post['category_name']): ?>
                                            <span class="category-badge" style="background-color: <?php echo htmlspecialchars($post['category_color']); ?>">
                                                <?php echo htmlspecialchars($post['category_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="no-category">No category</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <a href="admin.php?edit=<?php echo $post['id']; ?>" class="edit-btn">Edit</a>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?')">
                                            <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" name="delete_post" class="delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Categories Section -->
        <div id="categories-section" class="admin-section" style="display: none;">
            <!-- Create/Edit Category Form -->
            <div class="admin-form">
                <h3><?php echo $edit_category ? 'Edit Category' : 'Create New Category'; ?></h3>
                <form method="post">
                    <?php if ($edit_category): ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Category Name:</label>
                        <input type="text" name="category_name" value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>URL Slug:</label>
                        <input type="text" name="category_slug" value="<?php echo $edit_category ? htmlspecialchars($edit_category['slug']) : ''; ?>" required>
                        <small>Used in URLs (e.g., technology, lifestyle). Only letters, numbers, and hyphens.</small>
                    </div>

                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="category_description" rows="3"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Color:</label>
                        <input type="color" name="category_color" value="<?php echo $edit_category ? htmlspecialchars($edit_category['color']) : '#3498db'; ?>">
                        <small>Used for category badges and highlights.</small>
                    </div>

                    <button type="submit" name="<?php echo $edit_category ? 'update_category' : 'create_category'; ?>">
                        <?php echo $edit_category ? 'Update Category' : 'Create Category'; ?>
                    </button>

                    <?php if ($edit_category): ?>
                        <a href="admin.php" class="cancel-btn">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Categories List -->
            <div class="admin-categories">
                <h3>All Categories</h3>
                <?php if (empty($categories)): ?>
                    <p>No categories available.</p>
                <?php else: ?>
                    <table class="categories-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Color</th>
                                <th>Posts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                // Get post count for this category
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ?");
                                $stmt->execute([$category['id']]);
                                $post_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                                <tr>
                                    <td>
                                        <span class="category-badge" style="background-color: <?php echo htmlspecialchars($category['color']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                                    <td>
                                        <div class="color-preview" style="background-color: <?php echo htmlspecialchars($category['color']); ?>"></div>
                                        <?php echo htmlspecialchars($category['color']); ?>
                                    </td>
                                    <td><?php echo $post_count; ?> posts</td>
                                    <td>
                                        <a href="admin.php?edit_category=<?php echo $category['id']; ?>" class="edit-btn">Edit</a>
                                        <?php if ($post_count == 0): ?>
                                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" class="delete-btn">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="disabled-btn" title="Cannot delete category with posts">Delete</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div id="comments-section" class="admin-section" style="display: none;">
            <div class="admin-comments">
                <h3>Comment Management</h3>

                <!-- Filter Comments -->
                <div class="comment-filter">
                    <button onclick="filterComments('all')" class="filter-btn active">All (<?php echo count($comments); ?>)</button>
                    <button onclick="filterComments('pending')" class="filter-btn">Pending (<?php echo $pending_count; ?>)</button>
                    <button onclick="filterComments('approved')" class="filter-btn">Approved</button>
                    <button onclick="filterComments('rejected')" class="filter-btn">Rejected</button>
                </div>

                <?php if (empty($comments)): ?>
                    <p>No comments available.</p>
                <?php else: ?>
                    <div class="comments-admin-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-admin-item" data-status="<?php echo $comment['status']; ?>">
                                <div class="comment-admin-header">
                                    <div class="comment-admin-info">
                                        <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                        <span class="comment-email">(<?php echo htmlspecialchars($comment['email']); ?>)</span>
                                        <span class="comment-post">on "<a href="post.php?id=<?php echo $comment['post_id']; ?>" target="_blank"><?php echo htmlspecialchars($comment['post_title']); ?></a>"</span>
                                    </div>
                                    <div class="comment-admin-meta">
                                        <span class="comment-date"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                                        <span class="comment-status status-<?php echo $comment['status']; ?>"><?php echo ucfirst($comment['status']); ?></span>
                                    </div>
                                </div>
                                <div class="comment-admin-content">
                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                </div>
                                <div class="comment-admin-actions">
                                    <?php if ($comment['status'] !== 'approved'): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" name="approve_comment" class="approve-btn">Approve</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($comment['status'] !== 'rejected'): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" name="reject_comment" class="reject-btn">Reject</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this comment?')">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <button type="submit" name="delete_comment" class="delete-btn">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages Section -->
        <div id="messages-section" class="admin-section" style="display: none;">
            <div class="admin-messages">
                <h3>Contact Messages</h3>

                <!-- Filter Messages -->
                <div class="message-filter">
                    <button onclick="filterMessages('all')" class="filter-btn active">All (<?php echo count($contact_messages); ?>)</button>
                    <button onclick="filterMessages('unread')" class="filter-btn">Unread (<?php echo $unread_count; ?>)</button>
                    <button onclick="filterMessages('read')" class="filter-btn">Read</button>
                </div>

                <?php if (empty($contact_messages)): ?>
                    <p>No contact messages available.</p>
                <?php else: ?>
                    <div class="messages-admin-list">
                        <?php foreach ($contact_messages as $message): ?>
                            <div class="message-admin-item" data-status="<?php echo $message['status']; ?>">
                                <div class="message-admin-header">
                                    <div class="message-admin-info">
                                        <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                        <span class="message-email">(<?php echo htmlspecialchars($message['email']); ?>)</span>
                                    </div>
                                    <div class="message-admin-meta">
                                        <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                        <span class="message-status status-<?php echo $message['status']; ?>"><?php echo ucfirst($message['status']); ?></span>
                                    </div>
                                </div>
                                <div class="message-admin-content">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                                <div class="message-admin-actions">
                                    <?php if ($message['status'] !== 'read'): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" name="mark_read" class="read-btn">Mark as Read</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($message['status'] !== 'unread'): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" name="mark_unread" class="unread-btn">Mark as Unread</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?')">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" name="delete_message" class="delete-btn">Delete</button>
                                    </form>

                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: Your message&body=Hello <?php echo htmlspecialchars($message['name']); ?>,%0A%0AThank you for your message." class="reply-btn" target="_blank">Reply via Email</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banner Section -->
        <div id="banner-section" class="admin-section" style="display: none;">
            <div class="admin-banner">
                <h3>Banner Management</h3>

                <!-- Current Banner Preview -->
                <?php if ($banner && $banner['is_active']): ?>
                    <div class="banner-preview">
                        <h4>Current Banner</h4>
                        <div class="banner-preview-content">
                            <?php if ($banner['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" alt="Banner Image" class="banner-preview-image">
                            <?php endif; ?>
                            <div class="banner-preview-text">
                                <?php if ($banner['title']): ?>
                                    <h5><?php echo htmlspecialchars($banner['title']); ?></h5>
                                <?php endif; ?>
                                <?php if ($banner['subtitle']): ?>
                                    <p><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Banner Form -->
                <div class="banner-form">
                    <h4>Update Banner</h4>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="banner_title">Banner Title:</label>
                            <input type="text" id="banner_title" name="banner_title" value="<?php echo $banner ? htmlspecialchars($banner['title']) : ''; ?>" placeholder="Enter banner title">
                        </div>

                        <div class="form-group">
                            <label for="banner_subtitle">Banner Subtitle:</label>
                            <textarea id="banner_subtitle" name="banner_subtitle" rows="3" placeholder="Enter banner subtitle"><?php echo $banner ? htmlspecialchars($banner['subtitle']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="banner_image">Banner Image:</label>
                            <input type="file" id="banner_image" name="banner_image" accept="image/*">
                            <small>Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB. Recommended size: 1200x400px</small>
                            <?php if ($banner && $banner['image_path']): ?>
                                <p class="current-file">Current: <?php echo basename($banner['image_path']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" <?php echo ($banner && $banner['is_active']) ? 'checked' : ''; ?>>
                                Display banner on homepage
                            </label>
                        </div>

                        <button type="submit" name="update_banner">Update Banner</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
    function showSection(section) {
        // Hide all sections
        document.getElementById('posts-section').style.display = 'none';
        document.getElementById('categories-section').style.display = 'none';
        document.getElementById('comments-section').style.display = 'none';
        document.getElementById('messages-section').style.display = 'none';
        document.getElementById('banner-section').style.display = 'none';

        // Remove active class from all nav links
        document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));

        // Show selected section
        document.getElementById(section + '-section').style.display = 'block';

        // Add active class to clicked nav link
        event.target.classList.add('active');
    }

    function filterComments(status) {
        const comments = document.querySelectorAll('.comment-admin-item');
        const buttons = document.querySelectorAll('.comment-filter .filter-btn');

        // Remove active class from all buttons
        buttons.forEach(btn => btn.classList.remove('active'));

        // Add active class to clicked button
        event.target.classList.add('active');

        // Show/hide comments based on filter
        comments.forEach(comment => {
            if (status === 'all' || comment.dataset.status === status) {
                comment.style.display = 'block';
            } else {
                comment.style.display = 'none';
            }
        });
    }

    function filterMessages(status) {
        const messages = document.querySelectorAll('.message-admin-item');
        const buttons = document.querySelectorAll('.message-filter .filter-btn');

        // Remove active class from all buttons
        buttons.forEach(btn => btn.classList.remove('active'));

        // Add active class to clicked button
        event.target.classList.add('active');

        // Show/hide messages based on filter
        messages.forEach(message => {
            if (status === 'all' || message.dataset.status === status) {
                message.style.display = 'block';
            } else {
                message.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>