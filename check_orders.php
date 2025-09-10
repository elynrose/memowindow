<?php
// check_orders.php - Check if orders are being saved to database
header('Content-Type: text/plain');

// Load configuration
require_once 'config.php';

$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== CHECKING ORDERS TABLE ===\n\n";
    
    // Check if orders table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'orders'")->fetchAll();
    
    if (empty($tables)) {
        echo "❌ Orders table does not exist yet.\n";
        echo "This is normal - the table is created when the first webhook is received.\n\n";
        echo "To create the table manually, the webhook will create it automatically,\n";
        echo "or you can create it by processing a test payment.\n";
    } else {
        echo "✅ Orders table exists.\n\n";
        
        // Show table structure
        echo "=== TABLE STRUCTURE ===\n";
        $columns = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            printf("%-20s %-20s %-10s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null']
            );
        }
        
        // Count orders
        $count = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
        echo "\n=== ORDER COUNT ===\n";
        echo "Total orders: $count\n\n";
        
        if ($count > 0) {
            echo "=== RECENT ORDERS ===\n";
            $orders = $pdo->query("
                SELECT 
                    id,
                    memory_id,
                    product_id,
                    customer_name,
                    amount_paid,
                    status,
                    created_at
                FROM orders 
                ORDER BY created_at DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orders as $order) {
                printf("ID: %d | Memory: %d | Product: %s | Customer: %s | Amount: $%.2f | Status: %s | Date: %s\n",
                    $order['id'],
                    $order['memory_id'],
                    $order['product_id'],
                    $order['customer_name'],
                    $order['amount_paid'] / 100,
                    $order['status'],
                    $order['created_at']
                );
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>

<p><a href="login.html">← Back to MemoWindow</a></p>
