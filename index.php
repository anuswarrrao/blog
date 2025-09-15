<?php
include 'config.php';

// Get all posts ordered by newest first with category information
$stmt = $pdo->query("SELECT p.*, c.name as category_name, c.color as category_color, c.slug as category_slug
                     FROM posts p
                     LEFT JOIN categories c ON p.category_id = c.id
                     ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active banner
$stmt = $pdo->query("SELECT * FROM banner_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$banner = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Blog</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">Simple Blog</a></h1>
            <nav>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="admin.php">Admin</a>
            </nav>
        </div>
    </header>

    <?php if ($banner): ?>
        <!-- Banner Section -->
        <section class="hero-banner">
            <?php if ($banner['image_path']): ?>
                <div class="banner-image">
                    <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" alt="Banner Image">
                </div>
            <?php endif; ?>
            <div class="banner-content">
                <div class="container">
                    <?php if ($banner['title']): ?>
                        <h1 class="banner-title"><?php echo htmlspecialchars($banner['title']); ?></h1>
                    <?php endif; ?>
                    <?php if ($banner['subtitle']): ?>
                        <p class="banner-subtitle"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <main class="container">
        <div class="content-header">
            <h2>Latest Posts</h2>

            <!-- Category Filter -->
            <?php if (!empty($categories)): ?>
                <div class="category-filter">
                    <button class="filter-btn active" onclick="filterByCategory('all')">All</button>
                    <?php foreach ($categories as $category): ?>
                        <button class="filter-btn" onclick="filterByCategory('<?php echo $category['slug']; ?>')"
                                style="border-color: <?php echo htmlspecialchars($category['color']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($posts)): ?>
            <p>No posts available yet.</p>
        <?php else: ?>
            <div class="posts">
                <?php foreach ($posts as $post): ?>
                    <article class="post-preview" data-category="<?php echo $post['category_slug'] ? htmlspecialchars($post['category_slug']) : 'uncategorized'; ?>">
                        <?php if ($post['category_name']): ?>
                            <div class="post-category">
                                <span class="category-badge" style="background-color: <?php echo htmlspecialchars($post['category_color']); ?>">
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <h3><a href="post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                        <div class="post-meta">
                            Posted on <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                        </div>
                        <div class="post-excerpt">
                            <?php echo substr(htmlspecialchars($post['content']), 0, 200) . '...'; ?>
                        </div>
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="read-more">Read More</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
    function filterByCategory(category) {
        const posts = document.querySelectorAll('.post-preview');
        const buttons = document.querySelectorAll('.filter-btn');

        // Remove active class from all buttons
        buttons.forEach(btn => btn.classList.remove('active'));

        // Add active class to clicked button
        event.target.classList.add('active');

        // Show/hide posts based on category
        posts.forEach(post => {
            if (category === 'all' || post.dataset.category === category) {
                post.style.display = 'block';
            } else {
                post.style.display = 'none';
            }
        });
    }
    </script>

    <footer>
        <div class="container">
            <p>&copy; 2025 Simple Blog. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>