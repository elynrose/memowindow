<?php
// update_existing_order.php - Update existing order with missing data
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== UPDATING EXISTING ORDER ===\n\n";
    
    // Get the order that needs fixing
    $stmt = $pdo->query("SELECT * FROM orders WHERE id = 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "Found order ID: " . $order['id'] . "\n";
        echo "Current amount_paid: " . $order['amount_paid'] . " cents\n";
        echo "Current total_price: " . ($order['total_price'] ?? 'NULL') . "\n";
        echo "Current unit_price: " . ($order['unit_price'] ?? 'NULL') . "\n";
        
        // Get product details
        $product = getProduct($order['product_id']);
        echo "Product name: " . ($product['name'] ?? 'Unknown') . "\n";
        
        // Get memory details
        $memoryStmt = $pdo->prepare("SELECT title, image_url FROM wave_assets WHERE id = ?");
        $memoryStmt->execute([$order['memory_id']]);
        $memory = $memoryStmt->fetch(PDO::FETCH_ASSOC);
        echo "Memory title: " . ($memory['title'] ?? 'Unknown') . "\n";
        
        // Update the order with missing data
        $updateStmt = $pdo->prepare("
            UPDATE orders SET 
                memory_title = :memory_title,
                memory_image_url = :memory_image_url,
                product_name = :product_name,
                product_variant_id = :product_variant_id,
                quantity = :quantity,
                unit_price = :unit_price,
                total_price = :total_price
            WHERE id = :order_id
        ");
        
        $result = $updateStmt->execute([
            ':memory_title' => $memory['title'] ?? 'Untitled Memory',
            ':memory_image_url' => $memory['image_url'] ?? '',
            ':product_name' => $product['name'] ?? 'Custom Print',
            ':product_variant_id' => $product['printful_id'] ?? '',
            ':quantity' => 1,
            ':unit_price' => ($order['amount_paid'] / 100), // Convert from cents to dollars
            ':total_price' => ($order['amount_paid'] / 100), // Convert from cents to dollars
            ':order_id' => $order['id']
        ]);
        
        if ($result) {
            echo "\n✅ Order updated successfully!\n";
            echo "Updated total_price: $" . number_format($order['amount_paid'] / 100, 2) . "\n";
            echo "Updated unit_price: $" . number_format($order['amount_paid'] / 100, 2) . "\n";
        } else {
            echo "\n❌ Failed to update order.\n";
        }
        
    } else {
        echo "No order found with ID 1.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
