<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");

        // Create posts table
        $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Create admin table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL
        )");

        // Create comments table
        $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )");

        // Create contact messages table
        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('unread', 'read') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create banner settings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS banner_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            subtitle TEXT,
            image_path VARCHAR(500),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Create categories table
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            color VARCHAR(7) DEFAULT '#3498db',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Add category_id to posts table
        $pdo->exec("ALTER TABLE posts ADD COLUMN category_id INT NULL");
        $pdo->exec("ALTER TABLE posts ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");

        // Insert default categories
        $default_categories = [
            ['Technology', 'technology', 'Posts about technology, programming, and digital innovation', '#3498db'],
            ['Lifestyle', 'lifestyle', 'Posts about lifestyle, personal development, and wellness', '#2ecc71'],
            ['Travel', 'travel', 'Posts about travel experiences and destinations', '#e74c3c'],
            ['Business', 'business', 'Posts about business, entrepreneurship, and career', '#f39c12']
        ];

        foreach ($default_categories as $category) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)");
            $stmt->execute($category);
        }

        // Insert admin user
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute([$admin_username, $hashed_password]);

        // Update config file
        $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException \$e) {
    die(\"Connection failed: \" . \$e->getMessage());
}
?>";

        file_put_contents('config.php', $config_content);

        echo "<div class='success'>Installation completed successfully! <a href='index.php'>Go to blog</a></div>";

    } catch(PDOException $e) {
        echo "<div class='error'>Installation failed: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blog Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .success { color: green; padding: 10px; background: #f0fff0; border: 1px solid green; border-radius: 4px; }
        .error { color: red; padding: 10px; background: #fff0f0; border: 1px solid red; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Blog Installation</h1>
    <form method="post">
        <div class="form-group">
            <label>Database Host:</label>
            <input type="text" name="db_host" value="localhost" required>
        </div>
        <div class="form-group">
            <label>Database Name:</label>
            <input type="text" name="db_name" value="simple_blog" required>
        </div>
        <div class="form-group">
            <label>Database Username:</label>
            <input type="text" name="db_user" value="root" required>
        </div>
        <div class="form-group">
            <label>Database Password:</label>
            <input type="password" name="db_pass">
        </div>
        <div class="form-group">
            <label>Admin Username:</label>
            <input type="text" name="admin_username" value="admin" required>
        </div>
        <div class="form-group">
            <label>Admin Password:</label>
            <input type="password" name="admin_password" required>
        </div>
        <button type="submit">Install Blog</button>
    </form>
</body>
</html>