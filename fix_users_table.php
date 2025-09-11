<?php
/**
 * Fix Users Table - Add missing 'name' column
 */

require_once 'config.php';

echo "🔧 FIXING USERS TABLE - Adding missing 'name' column\n";
echo "====================================================\n\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Connected to database successfully\n";
    
    // Check if 'name' column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'name'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "✅ 'name' column already exists in users table\n";
    } else {
        echo "❌ 'name' column missing - adding it now...\n";
        
        // Add the 'name' column
        $pdo->exec("ALTER TABLE users ADD COLUMN name VARCHAR(255) NULL AFTER email");
        
        echo "✅ Successfully added 'name' column to users table\n";
    }
    
    // Verify the column was added
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📋 Current users table structure:\n";
    foreach ($columns as $column) {
        echo "  - $column\n";
    }
    
    // Test user creation to make sure it works
    echo "\n🧪 Testing user creation...\n";
    $testUserId = 'test_user_' . time();
    
    $stmt = $pdo->prepare("INSERT INTO users (firebase_uid, email, name, password, display_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $result = $stmt->execute([$testUserId, 'test@example.com', 'Test User', 'test_password', 'Test User']);
    
    if ($result) {
        echo "✅ User creation test successful\n";
        
        // Clean up test user
        $pdo->prepare("DELETE FROM users WHERE firebase_uid = ?")->execute([$testUserId]);
        echo "✅ Test user cleaned up\n";
    } else {
        echo "❌ User creation test failed\n";
    }
    
    echo "\n🎉 Users table fix completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
