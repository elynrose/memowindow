<?php
/**
 * Check Users Table Structure
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "📋 USERS TABLE STRUCTURE\n";
    echo "========================\n\n";
    
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        $required = $column['Null'] === 'NO' ? 'REQUIRED' : 'OPTIONAL';
        $default = $column['Default'] ? "DEFAULT: {$column['Default']}" : 'NO DEFAULT';
        echo sprintf("%-20s %-10s %-15s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $required,
            $default
        );
    }
    
    echo "\n🧪 Testing minimal user creation...\n";
    
    // Try with just the required fields
    $testUserId = 'test_user_' . time();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (firebase_uid) VALUES (?)");
        $result = $stmt->execute([$testUserId]);
        
        if ($result) {
            echo "✅ Minimal user creation successful\n";
            $pdo->prepare("DELETE FROM users WHERE firebase_uid = ?")->execute([$testUserId]);
        }
    } catch (Exception $e) {
        echo "❌ Minimal user creation failed: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
