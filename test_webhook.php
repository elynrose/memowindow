<?php
// test_webhook.php - Test webhook functionality
require_once 'config.php';

echo "<h1>🧪 Webhook Test Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .error { background: #f8d7da; border-color: #f5c6cb; }
    .warning { background: #fff3cd; border-color: #ffeaa7; }
    .info { background: #d1ecf1; border-color: #bee5eb; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

echo "<div class='section info'>";
echo "<h2>🔍 Webhook Configuration Check</h2>";

// Check if Stripe is configured
if (defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY !== 'sk_test_your_stripe_secret_key_here') {
    echo "<p class='success'>✅ Stripe secret key is configured</p>";
} else {
    echo "<p class='error'>❌ Stripe secret key not configured properly</p>";
}

if (defined('STRIPE_WEBHOOK_SECRET') && STRIPE_WEBHOOK_SECRET !== 'whsec_your_webhook_secret_here') {
    echo "<p class='success'>✅ Stripe webhook secret is configured</p>";
} else {
    echo "<p class='error'>❌ Stripe webhook secret not configured properly</p>";
}

echo "</div>";

echo "<div class='section info'>";
echo "<h2>📋 Test Webhook Endpoint</h2>";
echo "<p>Your webhook endpoint should be configured at:</p>";
echo "<pre>" . BASE_URL . "/stripe_webhook.php</pre>";
echo "<p>Make sure this URL is configured in your Stripe dashboard under Webhooks.</p>";
echo "</div>";

echo "<div class='section warning'>";
echo "<h2>⚠️ Common Issues</h2>";
echo "<ol>";
echo "<li><strong>User ID Mismatch:</strong> Orders table expects integer user_id, but webhook might be passing Firebase UID string</li>";
echo "<li><strong>Missing Memory Data:</strong> Webhook tries to get memory details but memory_id might not exist</li>";
echo "<li><strong>Database Permissions:</strong> Webhook might not have permission to insert into orders table</li>";
echo "<li><strong>Webhook Signature:</strong> Stripe signature verification might be failing</li>";
echo "</ol>";
echo "</div>";

echo "<div class='section info'>";
echo "<h2>🔧 Debug Steps</h2>";
echo "<ol>";
echo "<li>Run the diagnostic tool: <a href='diagnose_order_saving.php'>diagnose_order_saving.php</a></li>";
echo "<li>Check your Stripe dashboard for webhook delivery attempts</li>";
echo "<li>Look at server error logs for any webhook failures</li>";
echo "<li>Test with a small order to see if it processes correctly</li>";
echo "</ol>";
echo "</div>";

// Test database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "<div class='section success'>";
    echo "<h2>✅ Database Connection Test</h2>";
    echo "<p>Successfully connected to database: " . DB_NAME . "</p>";
    
    // Test if we can insert a test record
    $testStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
    $testStmt->execute();
    $result = $testStmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Current orders count: " . $result['count'] . "</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
