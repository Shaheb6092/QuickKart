<?php
// Database settings
$hostname = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'quickkart';

// --- Establish Connection to MySQL ---
try {
    $conn = new PDO("mysql:host=$hostname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("<h2>❌ DATABASE CONNECTION FAILED</h2><p>Could not connect to the MySQL server. Please check your `install.php` configuration.</p><p><strong>Error:</strong> " . $e->getMessage() . "</p>");
}

// --- Installation Process ---
$installed = false;
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['install'])) {
    try {
        // 1. Create Database
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->exec("USE `$dbname`");

        // 2. Create Tables
        // -- users table --
        $conn->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(15) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `address` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;");

        // -- admin table --
        $conn->exec("CREATE TABLE IF NOT EXISTS `admin` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB;");

        // -- slideshow table --
        $conn->exec("CREATE TABLE IF NOT EXISTS `slideshow` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `image` VARCHAR(255) NOT NULL,
            `link_url` VARCHAR(255) NULL,
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;");

        // -- categories table --
        $conn->exec("CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `image` VARCHAR(255)
        ) ENGINE=InnoDB;");

        // -- products table --
        $conn->exec("CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `cat_id` INT NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `price` DECIMAL(10, 2) NOT NULL,
            `stock` INT NOT NULL,
            `image` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`cat_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;");

        // -- orders table --
        $conn->exec("CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `total_amount` DECIMAL(10, 2) NOT NULL,
            `status` VARCHAR(50) DEFAULT 'Placed',
            `shipping_address` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;");

        // -- order_items table --
        $conn->exec("CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `price` DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;");

        // 3. Insert Default Admin User
        $admin_user = 'admin';
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("SELECT id FROM `admin` WHERE username = :username");
        $stmt->execute(['username' => $admin_user]);
        if ($stmt->rowCount() == 0) {
            $conn->prepare("INSERT INTO `admin` (username, password) VALUES (:username, :password)")
                 ->execute(['username' => $admin_user, 'password' => $admin_pass]);
        }
        
        // 4. Create upload directory
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $installed = true;

    } catch(PDOException $e) {
        $error_message = "Installation failed: " . $e->getMessage();
    }
}

// Redirect after successful installation
if ($installed) {
    echo "<script>
        alert('✅ Quick Kart installation complete! You will be redirected to the login page.');
        window.location.href = 'login.php';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Quick Kart - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-lg text-center">
        <h1 class="text-3xl font-bold text-slate-800 mb-2">🚀 Quick Kart Installer</h1>
        <p class="text-slate-600 mb-6">Welcome! Click the button below to set up your e-commerce application.</p>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6 text-left">
            <h3 class="font-semibold text-lg mb-2 text-slate-700">Database Configuration</h3>
            <p class="text-sm text-slate-500"><i class="fas fa-database mr-2"></i>Host: <span class="font-mono bg-slate-200 px-1 rounded"><?= htmlspecialchars($hostname) ?></span></p>
            <p class="text-sm text-slate-500"><i class="fas fa-user mr-2"></i>User: <span class="font-mono bg-slate-200 px-1 rounded"><?= htmlspecialchars($username) ?></span></p>
            <p class="text-sm text-slate-500"><i class="fas fa-key mr-2"></i>Password: <span class="font-mono bg-slate-200 px-1 rounded">******</span></p>
            <p class="text-sm text-slate-500"><i class="fas fa-file-alt mr-2"></i>Database Name: <span class="font-mono bg-slate-200 px-1 rounded"><?= htmlspecialchars($dbname) ?></span></p>
        </div>

        <form method="POST" action="install.php">
            <button type="submit" name="install" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-transform transform hover:scale-105">
                Install Now
            </button>
        </form>
        <p class="text-xs text-slate-400 mt-4">This will create the database, tables, and a default admin account (admin/admin123).</p>
    </div>
</body>
</html>