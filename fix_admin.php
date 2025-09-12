<?php
/**
 * Fix admin status for user
 */

require_once 'config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $userId = 'FG8w39qVEySCnzotJDYBWQ30g5J2';
    
    // Check if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() == 0) {
        echo "Creating admin_users table...\n";
        $pdo->exec("CREATE TABLE admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            firebase_uid VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255),
            name VARCHAR(255) DEFAULT '',
            is_admin TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        // Check table structure
        $stmt = $pdo->query("DESCRIBE admin_users");
        $columns = $stmt->fetchAll();
        echo "Admin table structure:\n";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
    // Check if user exists in admin table
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found in admin table. Current admin status: " . ($user['is_admin'] ? 'YES' : 'NO') . "\n";
        
        // Update to admin
        $stmt = $pdo->prepare("UPDATE admin_users SET is_admin = 1 WHERE firebase_uid = ?");
        $stmt->execute([$userId]);
        echo "Updated user to admin status.\n";
    } else {
        echo "User not found in admin table. Adding as admin...\n";
        $stmt = $pdo->prepare("INSERT INTO admin_users (firebase_uid, email, name, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->execute([$userId, 'elyayertey@gmail.com', 'Ely Ayertey']);
        echo "Added user as admin.\n";
    }
    
    // Verify
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    echo "Final status: " . ($user['is_admin'] ? 'ADMIN' : 'NOT ADMIN') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
