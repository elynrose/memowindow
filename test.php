<?php
// Simple PHP test file
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

// Test database connection
try {
    $host = 'localhost';
    $dbname = 'memowindow';
    $username = 'memowindow_user';
    $password = 'your_secure_password_here'; // Update this
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection: SUCCESS<br>";
} catch(PDOException $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

// Test file permissions
echo "File permissions: " . substr(sprintf('%o', fileperms(__FILE__)), -4) . "<br>";

// Test if config file exists
if (file_exists('config.php')) {
    echo "Config file: EXISTS<br>";
} else {
    echo "Config file: MISSING<br>";
}

// Show any PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
