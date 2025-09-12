<?php
require_once 'config.php';

echo "<h1>Create user_subscriptions table</h1>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "<p>Connected to database: " . DB_NAME . "</p>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_subscriptions'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<p>Creating user_subscriptions table...</p>";
        
        $pdo->exec("
            CREATE TABLE user_subscriptions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                package_id INT UNSIGNED NOT NULL,
                stripe_subscription_id VARCHAR(255) NULL,
                stripe_customer_id VARCHAR(255) NULL,
                status ENUM('active', 'canceled', 'past_due', 'unpaid', 'trialing') DEFAULT 'active',
                current_period_start TIMESTAMP NULL DEFAULT NULL,
                current_period_end TIMESTAMP NULL DEFAULT NULL,
                trial_end TIMESTAMP NULL DEFAULT NULL,
                amount_paid DECIMAL(10,2) DEFAULT NULL,
                billing_cycle ENUM('monthly', 'yearly') DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_package_id (package_id),
                INDEX idx_stripe_subscription (stripe_subscription_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        echo "<p>✅ user_subscriptions table created successfully!</p>";
    } else {
        echo "<p>✅ user_subscriptions table already exists</p>";
    }
    
    // Show table structure
    echo "<h2>Table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE user_subscriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(nullable)' : '(not null)') . 
             ($column['Default'] ? " - Default: {$column['Default']}" : '') . "</li>";
    }
    echo "</ul>";
    
    // Test the table
    echo "<h2>Testing table:</h2>";
    $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, package_id, amount_paid, billing_cycle, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['test_user', 1, 9.99, 'monthly', 'active']);
    echo "<p>✅ Test insert successful</p>";
    
    $stmt = $pdo->prepare("SELECT * FROM user_subscriptions WHERE user_id = ?");
    $stmt->execute(['test_user']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Test select successful - Amount: " . $result['amount_paid'] . "</p>";
    
    // Clean up test data
    $stmt = $pdo->prepare("DELETE FROM user_subscriptions WHERE user_id = ?");
    $stmt->execute(['test_user']);
    echo "<p>🧹 Test data cleaned up</p>";
    
    echo "<h2>✅ Database setup completed!</h2>";
    echo "<p>The user_subscriptions table is now ready for subscription amount recording.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
