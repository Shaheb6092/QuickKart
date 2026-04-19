<?php
session_start();


// Database settings
$hostname = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'quickkart';


// $hostname = 'sql308.infinityfree.com';
// $username = 'if0_39609293';
// $password = 'aDpkOdHAik';
// $dbname = 'if0_39609293_quickkart';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If the database connection fails, it might be because it's not installed yet.
    // Check if install.php exists and redirect.
    if (file_exists('install.php')) {
        header('Location: install.php');
        exit;
    }
    // If install.php is not found, die with an error.
    die("Database connection failed: " . $e->getMessage() . "<br>Please ensure the database is set up correctly.");
}

// Global function to format price
function format_price($price) {
    return '₹' . number_format($price, 2);
}
?>
