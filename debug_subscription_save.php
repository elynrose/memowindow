<?php
require_once 'config.php';
require_once 'SubscriptionManager.php';

echo "<h1>Debug Subscription Save</h1>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check current table structure
    echo "<h2>Current user_subscriptions table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE user_subscriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(nullable)' : '(not null)') . 
             ($column['Default'] ? " - Default: {$column['Default']}" : '') . "</li>";
    }
    echo "</ul>";
    
    // Test SubscriptionManager
    echo "<h2>Testing SubscriptionManager:</h2>";
    $subscriptionManager = new SubscriptionManager();
    
    // Test with dummy data
    $testUserId = 'test_user_123';
    $testPackageId = 1;
    $testStripeId = 'sub_test_123';
    $testCustomerId = 'cus_test_123';
    $testAmount = 9.99;
    $testBillingCycle = 'monthly';
    $testPeriodStart = date('Y-m-d H:i:s');
    $testPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    echo "<p>Testing createOrUpdateSubscription with:</p>";
    echo "<ul>";
    echo "<li>User ID: $testUserId</li>";
    echo "<li>Package ID: $testPackageId</li>";
    echo "<li>Amount: $testAmount</li>";
    echo "<li>Billing Cycle: $testBillingCycle</li>";
    echo "</ul>";
    
    try {
        $subscriptionManager->createOrUpdateSubscription(
            $testUserId,
            $testPackageId,
            $testStripeId,
            $testCustomerId,
            'active',
            $testAmount,
            $testBillingCycle,
            $testPeriodStart,
            $testPeriodEnd
        );
        echo "<p>✅ SubscriptionManager test successful!</p>";
        
        // Check if it was saved
        $stmt = $pdo->prepare("SELECT * FROM user_subscriptions WHERE user_id = ?");
        $stmt->execute([$testUserId]);
        $saved = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($saved) {
            echo "<p>✅ Data was saved to database:</p>";
            echo "<pre>" . print_r($saved, true) . "</pre>";
        } else {
            echo "<p>❌ No data found in database</p>";
        }
        
        // Clean up test data
        $stmt = $pdo->prepare("DELETE FROM user_subscriptions WHERE user_id = ?");
        $stmt->execute([$testUserId]);
        echo "<p>🧹 Test data cleaned up</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ SubscriptionManager test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
