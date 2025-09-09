<?php
// test_order_data.php - Test order data and memory lookup
header('Content-Type: text/plain');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== TESTING ORDER DATA ===\n\n";
    
    // Get the order
    $stmt = $pdo->query("SELECT * FROM orders WHERE id = 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "Order data:\n";
        echo "ID: " . $order['id'] . "\n";
        echo "Memory ID: " . $order['memory_id'] . "\n";
        echo "Amount paid: " . $order['amount_paid'] . " cents\n";
        echo "Total price: " . ($order['total_price'] ?? 'NULL') . "\n";
        echo "Unit price: " . ($order['unit_price'] ?? 'NULL') . "\n";
        echo "Memory title: " . ($order['memory_title'] ?? 'NULL') . "\n";
        echo "Memory image URL: " . ($order['memory_image_url'] ?? 'NULL') . "\n";
        echo "Product name: " . ($order['product_name'] ?? 'NULL') . "\n";
        
        echo "\n=== CHECKING MEMORY IN WAVE_ASSETS ===\n";
        
        // Check if memory exists in wave_assets
        $memoryStmt = $pdo->prepare("SELECT * FROM wave_assets WHERE id = ?");
        $memoryStmt->execute([$order['memory_id']]);
        $memory = $memoryStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($memory) {
            echo "Memory found in wave_assets:\n";
            echo "Title: " . $memory['title'] . "\n";
            echo "Image URL: " . $memory['image_url'] . "\n";
            echo "Original name: " . $memory['original_name'] . "\n";
        } else {
            echo "❌ No memory found with ID " . $order['memory_id'] . " in wave_assets table\n";
        }
        
        echo "\n=== CHECKING PRODUCT ===\n";
        
        // Check product
        $product = getProduct($order['product_id']);
        if ($product) {
            echo "Product found:\n";
            echo "Name: " . $product['name'] . "\n";
            echo "Price: " . $product['price'] . " cents\n";
        } else {
            echo "❌ No product found with ID " . $order['product_id'] . "\n";
        }
        
    } else {
        echo "No order found with ID 1.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
