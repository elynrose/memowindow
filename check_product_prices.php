<?php
// check_product_prices.php - Check actual product prices in database
header('Content-Type: text/plain');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== CHECKING PRODUCT PRICES IN DATABASE ===\n\n";
    
    // Get all products
    $products = $pdo->query("SELECT * FROM print_products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "❌ No products found in database.\n";
        exit;
    }
    
    echo "Raw database values:\n";
    foreach ($products as $product) {
        echo "ID: {$product['id']}\n";
        echo "Product Key: {$product['product_key']}\n";
        echo "Name: {$product['name']}\n";
        echo "Raw Price Value: {$product['price']} (type: " . gettype($product['price']) . ")\n";
        echo "Price / 100: " . ($product['price'] / 100) . "\n";
        echo "Formatted Price: $" . number_format($product['price'] / 100, 2) . "\n";
        echo "---\n";
    }
    
    echo "\n=== TESTING getProduct() FUNCTION ===\n";
    
    // Test the getProduct function
    foreach ($products as $product) {
        $result = getProduct($product['product_key']);
        echo "Testing getProduct('{$product['product_key']}'):\n";
        echo "  Returned price: {$result['price']} cents\n";
        echo "  Formatted: $" . number_format($result['price'] / 100, 2) . "\n";
        echo "---\n";
    }
    
    echo "\n=== CHECKING ORDERS TABLE ===\n";
    
    // Check if there are any orders and what prices they have
    try {
        $orders = $pdo->query("SELECT id, product_id, amount_paid, total_price, unit_price FROM orders LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($orders)) {
            echo "No orders found in database.\n";
        } else {
            echo "Sample orders:\n";
            foreach ($orders as $order) {
                echo "Order ID: {$order['id']}\n";
                echo "Product ID: {$order['product_id']}\n";
                echo "Amount Paid: {$order['amount_paid']} cents\n";
                echo "Total Price: {$order['total_price']}\n";
                echo "Unit Price: {$order['unit_price']}\n";
                echo "---\n";
            }
        }
    } catch (Exception $e) {
        echo "Error checking orders: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
