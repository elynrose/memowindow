<?php
// test_webhook_simulation.php - Test webhook functionality by simulating a Stripe event
require_once 'config.php';

echo "<h1>🧪 Webhook Simulation Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .error { background: #f8d7da; border-color: #f5c6cb; }
    .warning { background: #fff3cd; border-color: #ffeaa7; }
    .info { background: #d1ecf1; border-color: #bee5eb; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

// Test 1: Check if we can get user ID from Firebase UID
echo "<div class='section info'>";
echo "<h2>🔍 Test 1: User ID Mapping</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Test with the admin user's Firebase UID
    $testFirebaseUID = 'FG8w39qVEySCnzotJDYBWQ30g5J2';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE firebase_uid = ?");
    $stmt->execute([$testFirebaseUID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='success'>✅ Successfully found user ID: " . $user['id'] . " for Firebase UID: " . substr($testFirebaseUID, 0, 20) . "...</p>";
        $testUserId = $user['id'];
    } else {
        echo "<p class='error'>❌ Could not find user ID for Firebase UID: " . substr($testFirebaseUID, 0, 20) . "...</p>";
        $testUserId = 1; // Fallback to admin user
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $testUserId = 1;
}
echo "</div>";

// Test 2: Test order insertion directly
echo "<div class='section info'>";
echo "<h2>🔍 Test 2: Direct Order Insertion</h2>";

try {
    // Test inserting an order directly using the webhook function
    $testOrderData = [
        'stripe_session_id' => 'cs_test_' . time(),
        'user_id' => $testUserId,
        'status' => 'paid',
        'amount_paid' => 2250,
        'memory_title' => 'Test Memory',
        'memory_image_url' => 'https://example.com/test.jpg',
        'product_name' => 'Test Product',
        'product_variant_id' => 'test_variant',
        'quantity' => 1,
        'unit_price' => 22.50,
        'total_price' => 22.50,
        'shipping_address' => json_encode(['test' => 'address']),
        'printful_order_id' => null
    ];
    
    // Use the webhook's saveOrderToDatabase function
    require_once 'stripe_webhook.php';
    $orderId = saveOrderToDatabase($testOrderData);
    
    echo "<p class='success'>✅ Successfully inserted test order with ID: " . $orderId . "</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error inserting test order: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Check if order appears in database
echo "<div class='section info'>";
echo "<h2>🔍 Test 3: Verify Order in Database</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total orders in database: " . $result['count'] . "</p>";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 1");
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>✅ Latest order:</p>";
        echo "<pre>" . print_r($order, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking orders: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Test webhook function directly
echo "<div class='section info'>";
echo "<h2>🔍 Test 4: Test Webhook Function</h2>";

try {
    // Include the webhook functions
    require_once 'stripe_webhook.php';
    
    // Test the getUserIdFromFirebaseUID function
    $testFirebaseUID = 'FG8w39qVEySCnzotJDYBWQ30g5J2';
    $userId = getUserIdFromFirebaseUID($testFirebaseUID);
    
    if ($userId) {
        echo "<p class='success'>✅ getUserIdFromFirebaseUID() works: " . $userId . "</p>";
    } else {
        echo "<p class='error'>❌ getUserIdFromFirebaseUID() failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error testing webhook function: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='section warning'>";
echo "<h2>🔧 Next Steps</h2>";
echo "<ol>";
echo "<li>If direct insertion works, the issue is with the webhook not being called</li>";
echo "<li>If direct insertion fails, there's a database schema issue</li>";
echo "<li>Check if Stripe webhook is configured to call your local endpoint</li>";
echo "<li>Test with a real Stripe checkout session</li>";
echo "</ol>";
echo "</div>";
?>
