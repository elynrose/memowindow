<?php
// test_products.php - Test script to debug product loading
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Product Loading Test</h1>";

try {
    require_once 'config.php';
    echo "<p>✅ Config loaded successfully</p>";
    
    // Test database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<p>✅ Database connected successfully</p>";
    
    // Test if print_products table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'print_products'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p>✅ Print products table exists</p>";
        
        // Test table structure
        $stmt = $pdo->query("DESCRIBE print_products");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Test product count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM print_products");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 Products in database: {$count}</p>";
        
        // Test product query
        $stmt = $pdo->query("
            SELECT 
                product_key as id,
                printful_id,
                name,
                description,
                price,
                size,
                material
            FROM print_products 
            WHERE is_active = 1 
            ORDER BY name ASC
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Products Found:</h3>";
        if (empty($products)) {
            echo "<p>❌ No products found</p>";
        } else {
            echo "<ul>";
            foreach ($products as $product) {
                echo "<li><strong>{$product['name']}</strong> - {$product['price']} - {$product['size']} - {$product['material']}</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p>❌ Print products table does not exist</p>";
    }
    
    // Test get_products.php endpoint
    echo "<h3>Testing get_products.php endpoint:</h3>";
    $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/get_products.php';
    echo "<p>URL: <a href='{$url}' target='_blank'>{$url}</a></p>";
    
    $response = file_get_contents($url);
    if ($response === false) {
        echo "<p>❌ Failed to fetch from get_products.php</p>";
    } else {
        echo "<p>✅ Response from get_products.php:</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Valid JSON response</p>";
            echo "<p>Products returned: " . count($json) . "</p>";
        } else {
            echo "<p>❌ Invalid JSON: " . json_last_error_msg() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
