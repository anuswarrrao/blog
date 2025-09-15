<?php
include 'config.php';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $message]);
            $success = "Your message has been sent successfully! We'll get back to you soon.";

            // Clear form fields after successful submission
            $name = $email = $message = '';
        } else {
            $error = "Please enter a valid email address.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Us - Simple Blog</title>
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
        <div class="contact-page">
            <h1>Contact Us</h1>
            <p class="contact-intro">Have a question or want to get in touch? Send us a message and we'll respond as soon as possible.</p>

            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="contact-form-container">
                <form method="post" class="contact-form">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="8" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>

                    <button type="submit" name="submit_contact">Send Message</button>
                </form>
            </div>

            <div class="contact-info">
                <h3>Other Ways to Reach Us</h3>
                <div class="contact-details">
                    <div class="contact-item">
                        <strong>Email:</strong> info@simpleblog.com
                    </div>
                    <div class="contact-item">
                        <strong>Response Time:</strong> We typically respond within 24 hours
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Simple Blog. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>