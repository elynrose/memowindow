<?php
// setup_admin_user.php - Set up admin user
header('Content-Type: text/plain');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== SETTING UP ADMIN USER ===\n\n";
    
    // Create admin_users table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `firebase_uid` VARCHAR(255) NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `is_admin` BOOLEAN DEFAULT FALSE,
            `permissions` JSON DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `last_login` TIMESTAMP NULL,
            INDEX `idx_firebase_uid` (`firebase_uid`),
            INDEX `idx_is_admin` (`is_admin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "✅ Admin users table created/verified.\n\n";
    
    // Get your Firebase UID from the URL parameter
    $firebaseUID = $_GET['user_id'] ?? '';
    
    if (!$firebaseUID) {
        echo "❌ Please provide your Firebase UID as a URL parameter:\n";
        echo "https://memowindow.com/setup_admin_user.php?user_id=YOUR_FIREBASE_UID\n\n";
        echo "To get your Firebase UID, check the orders URL:\n";
        echo "https://memowindow.com/orders.php?user_id=YOUR_FIREBASE_UID\n";
        exit;
    }
    
    echo "Setting up admin for Firebase UID: $firebaseUID\n\n";
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUID]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "User already exists in admin_users table:\n";
        echo "Email: " . $existingUser['email'] . "\n";
        echo "Name: " . $existingUser['name'] . "\n";
        echo "Is Admin: " . ($existingUser['is_admin'] ? 'Yes' : 'No') . "\n";
        
        if (!$existingUser['is_admin']) {
            // Make them admin
            $updateStmt = $pdo->prepare("UPDATE admin_users SET is_admin = 1 WHERE firebase_uid = ?");
            $updateStmt->execute([$firebaseUID]);
            echo "\n✅ User has been granted admin privileges!\n";
        } else {
            echo "\n✅ User already has admin privileges!\n";
        }
    } else {
        // Create new admin user
        $insertStmt = $pdo->prepare("
            INSERT INTO admin_users (firebase_uid, email, name, is_admin, permissions) 
            VALUES (?, ?, ?, 1, ?)
        ");
        
        $email = $_GET['email'] ?? 'admin@memowindow.com';
        $name = $_GET['name'] ?? 'Admin User';
        $permissions = json_encode(['all' => true]);
        
        $insertStmt->execute([$firebaseUID, $email, $name, $permissions]);
        
        echo "✅ New admin user created:\n";
        echo "Firebase UID: $firebaseUID\n";
        echo "Email: $email\n";
        echo "Name: $name\n";
        echo "Admin: Yes\n";
    }
    
    echo "\n=== ADMIN ACCESS URLS ===\n";
    echo "Main Admin Dashboard: https://memowindow.com/admin.php?user_id=$firebaseUID\n";
    echo "User Management: https://memowindow.com/admin_users.php?user_id=$firebaseUID\n";
    echo "Product Management: https://memowindow.com/admin_products.php?user_id=$firebaseUID\n";
    echo "Analytics: https://memowindow.com/analytics.php?user_id=$firebaseUID\n";
    echo "Backup Management: https://memowindow.com/admin_backups.php?user_id=$firebaseUID\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
