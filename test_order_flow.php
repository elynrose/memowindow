<?php
// test_order_flow.php - Test script to debug order flow
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Order Flow Test</h1>";

try {
    require_once 'config.php';
    echo "<p>✅ Config loaded successfully</p>";
    
    // Test database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<p>✅ Database connected successfully</p>";
    
    // Test get_products.php endpoint
    echo "<h3>Testing get_products.php:</h3>";
    $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/get_products.php';
    echo "<p>URL: <a href='{$url}' target='_blank'>{$url}</a></p>";
    
    $response = file_get_contents($url);
    if ($response === false) {
        echo "<p>❌ Failed to fetch from get_products.php</p>";
    } else {
        echo "<p>✅ Response from get_products.php:</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        $products = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($products)) {
            echo "<p>✅ Valid JSON response with " . count($products) . " products</p>";
            
            // Test getProduct function with first product
            $firstProduct = $products[0];
            echo "<h3>Testing getProduct() function:</h3>";
            echo "<p>Product ID from frontend: <strong>{$firstProduct['id']}</strong></p>";
            
            $productDetails = getProduct($firstProduct['id']);
            if ($productDetails) {
                echo "<p>✅ getProduct() found product:</p>";
                echo "<ul>";
                foreach ($productDetails as $key => $value) {
                    echo "<li><strong>{$key}:</strong> " . htmlspecialchars($value) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>❌ getProduct() returned null for ID: {$firstProduct['id']}</p>";
                
                // Debug: Check what's in the database
                echo "<h4>Debug: Database contents:</h4>";
                $stmt = $pdo->query("SELECT product_key, name, price FROM print_products WHERE is_active = 1");
                $dbProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<ul>";
                foreach ($dbProducts as $dbProduct) {
                    echo "<li><strong>{$dbProduct['product_key']}</strong> - {$dbProduct['name']} - {$dbProduct['price']}</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p>❌ Invalid JSON or empty products: " . json_last_error_msg() . "</p>";
        }
    }
    
    // Test create_checkout.php with sample data
    echo "<h3>Testing create_checkout.php:</h3>";
    $testData = [
        'product_id' => 'memory_frame_8x10',
        'memory_id' => 1,
        'image_url' => 'https://example.com/test.jpg',
        'user_id' => 'test_user_123',
        'user_email' => 'test@example.com',
        'user_name' => 'Test User'
    ];
    
    echo "<p>Test data:</p>";
    echo "<pre>" . htmlspecialchars(json_encode($testData, JSON_PRETTY_PRINT)) . "</pre>";
    
    $checkoutUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/create_checkout.php';
    echo "<p>Checkout URL: <a href='{$checkoutUrl}' target='_blank'>{$checkoutUrl}</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
