<?php
// fix_product_prices.php - Fix incorrect product prices in database
header('Content-Type: text/plain');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== FIXING PRODUCT PRICES ===\n\n";
    
    // Get current products
    $products = $pdo->query("SELECT * FROM print_products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "❌ No products found in database.\n";
        exit;
    }
    
    echo "Current products and their prices:\n";
    foreach ($products as $product) {
        echo "- {$product['name']}: {$product['price']} cents (${$product['price']/100})\n";
    }
    
    echo "\n=== UPDATING PRICES ===\n";
    
    // Define correct prices for each product
    $correctPrices = [
        'memory_frame_8x10' => 2499,  // $24.99
        'memory_frame_11x14' => 3499, // $34.99
        'memory_frame_16x20' => 4999, // $49.99
    ];
    
    $updated = 0;
    foreach ($products as $product) {
        $productKey = $product['product_key'];
        $currentPrice = $product['price'];
        
        if (isset($correctPrices[$productKey])) {
            $correctPrice = $correctPrices[$productKey];
            
            if ($currentPrice != $correctPrice) {
                $stmt = $pdo->prepare("UPDATE print_products SET price = :price WHERE id = :id");
                $stmt->execute([
                    ':price' => $correctPrice,
                    ':id' => $product['id']
                ]);
                
                echo "✅ Updated {$product['name']}: {$currentPrice} → {$correctPrice} cents\n";
                $updated++;
            } else {
                echo "✅ {$product['name']}: Already correct ({$correctPrice} cents)\n";
            }
        } else {
            echo "⚠️  {$product['name']}: No correct price defined for key '{$productKey}'\n";
        }
    }
    
    echo "\n=== FINAL PRICES ===\n";
    $products = $pdo->query("SELECT * FROM print_products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $product) {
        echo "- {$product['name']}: {$product['price']} cents (${$product['price']/100})\n";
    }
    
    echo "\n✅ Price fix complete! Updated {$updated} products.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
