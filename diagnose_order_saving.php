<?php
// diagnose_order_saving.php - Diagnostic tool to check order saving issues
require_once 'config.php';

echo "<h1>🔍 Order Saving Diagnostic Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .success { background: #d4edda; border-color: #c3e6cb; }
    .error { background: #f8d7da; border-color: #f5c6cb; }
    .warning { background: #fff3cd; border-color: #ffeaa7; }
    .info { background: #d1ecf1; border-color: #bee5eb; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f2f2f2; }
</style>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "<div class='section info'>";
    echo "<h2>📊 Database Connection</h2>";
    echo "<p>✅ Successfully connected to database: " . DB_NAME . "</p>";
    echo "</div>";
    
    // Check orders table structure
    echo "<div class='section info'>";
    echo "<h2>🗂️ Orders Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Check users table structure
    echo "<div class='section info'>";
    echo "<h2>👥 Users Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($userColumns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Check recent orders
    echo "<div class='section info'>";
    echo "<h2>📦 Recent Orders (Last 10)</h2>";
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p class='warning'>⚠️ No orders found in database</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Stripe Session</th><th>User ID</th><th>Status</th><th>Amount</th><th>Created</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($order['stripe_session_id'], -8)) . "</td>";
            echo "<td>" . htmlspecialchars($order['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['status']) . "</td>";
            echo "<td>$" . number_format($order['amount_paid'] / 100, 2) . "</td>";
            echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Check users
    echo "<div class='section info'>";
    echo "<h2>👤 Users in Database</h2>";
    $stmt = $pdo->query("SELECT id, firebase_uid, email, name FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p class='warning'>⚠️ No users found in database</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Firebase UID</th><th>Email</th><th>Name</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($user['firebase_uid'], 0, 20)) . "...</td>";
            echo "<td>" . htmlspecialchars($user['email'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($user['name'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // Check for data type mismatches
    echo "<div class='section warning'>";
    echo "<h2>⚠️ Potential Issues</h2>";
    
    // Check if orders.user_id is string but users.id is int
    $ordersStmt = $pdo->query("SELECT DISTINCT user_id FROM orders LIMIT 5");
    $orderUserIds = $ordersStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $usersStmt = $pdo->query("SELECT DISTINCT id FROM users LIMIT 5");
    $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Data Type Analysis:</h3>";
    echo "<p><strong>Orders user_id samples:</strong> " . implode(', ', array_map('htmlspecialchars', $orderUserIds)) . "</p>";
    echo "<p><strong>Users id samples:</strong> " . implode(', ', array_map('htmlspecialchars', $userIds)) . "</p>";
    
    // Check if there are any orders with user_id that don't match users.id
    $mismatchStmt = $pdo->query("
        SELECT o.id, o.user_id, o.stripe_session_id 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE u.id IS NULL 
        LIMIT 5
    ");
    $mismatches = $mismatchStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($mismatches)) {
        echo "<p class='error'>❌ Found orders with user_id that don't match any user:</p>";
        echo "<ul>";
        foreach ($mismatches as $mismatch) {
            echo "<li>Order ID: " . htmlspecialchars($mismatch['id']) . 
                 ", User ID: " . htmlspecialchars($mismatch['user_id']) . 
                 ", Session: " . htmlspecialchars(substr($mismatch['stripe_session_id'], -8)) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='success'>✅ All orders have valid user_id references</p>";
    }
    echo "</div>";
    
    // Check webhook logs (if any)
    echo "<div class='section info'>";
    echo "<h2>📝 Recent Webhook Activity</h2>";
    echo "<p>Check your server error logs for recent webhook activity:</p>";
    echo "<pre>";
    echo "tail -f /var/log/apache2/error.log | grep stripe_webhook\n";
    echo "tail -f /var/log/nginx/error.log | grep stripe_webhook\n";
    echo "</pre>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ General Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='section info'>";
echo "<h2>🔧 Next Steps</h2>";
echo "<ol>";
echo "<li>Check if orders are being created with correct user_id format</li>";
echo "<li>Verify webhook is receiving and processing Stripe events</li>";
echo "<li>Check server error logs for any webhook failures</li>";
echo "<li>Test a new order to see if it saves properly</li>";
echo "</ol>";
echo "</div>";
?>
