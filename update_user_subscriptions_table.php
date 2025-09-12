<?php
require_once 'config.php';

echo "<h1>Update user_subscriptions table</h1>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check if amount_paid field exists
    $stmt = $pdo->query("SHOW COLUMNS FROM user_subscriptions LIKE 'amount_paid'");
    $amountPaidExists = $stmt->fetch();
    
    if (!$amountPaidExists) {
        echo "<p>Adding amount_paid field...</p>";
        $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT NULL");
        echo "<p>✅ amount_paid field added</p>";
    } else {
        echo "<p>✅ amount_paid field already exists</p>";
    }
    
    // Check if billing_cycle field exists
    $stmt = $pdo->query("SHOW COLUMNS FROM user_subscriptions LIKE 'billing_cycle'");
    $billingCycleExists = $stmt->fetch();
    
    if (!$billingCycleExists) {
        echo "<p>Adding billing_cycle field...</p>";
        $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN billing_cycle ENUM('monthly', 'yearly') DEFAULT NULL");
        echo "<p>✅ billing_cycle field added</p>";
    } else {
        echo "<p>✅ billing_cycle field already exists</p>";
    }
    
    // Check if current_period_start field exists
    $stmt = $pdo->query("SHOW COLUMNS FROM user_subscriptions LIKE 'current_period_start'");
    $currentPeriodStartExists = $stmt->fetch();
    
    if (!$currentPeriodStartExists) {
        echo "<p>Adding current_period_start field...</p>";
        $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN current_period_start TIMESTAMP NULL DEFAULT NULL");
        echo "<p>✅ current_period_start field added</p>";
    } else {
        echo "<p>✅ current_period_start field already exists</p>";
    }
    
    // Check if current_period_end field exists
    $stmt = $pdo->query("SHOW COLUMNS FROM user_subscriptions LIKE 'current_period_end'");
    $currentPeriodEndExists = $stmt->fetch();
    
    if (!$currentPeriodEndExists) {
        echo "<p>Adding current_period_end field...</p>";
        $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN current_period_end TIMESTAMP NULL DEFAULT NULL");
        echo "<p>✅ current_period_end field added</p>";
    } else {
        echo "<p>✅ current_period_end field already exists</p>";
    }
    
    echo "<h2>✅ Database update completed!</h2>";
    echo "<p>The user_subscriptions table now has the following fields:</p>";
    
    // Show current table structure
    $stmt = $pdo->query("DESCRIBE user_subscriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(nullable)' : '(not null)') . 
             ($column['Default'] ? " - Default: {$column['Default']}" : '') . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error updating database:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
