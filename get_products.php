<?php
// get_products.php - Get available print products from database
header('Content-Type: application/json');
require_once 'config.php';
require_once 'PriceManager.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Get active products from database, fallback to config if table doesn't exist
    try {
        $stmt = $pdo->query("
            SELECT 
                product_key as id,
                printful_variant_id as printful_id,
                name,
                description,
                price,
                size,
                material,
                image_url
            FROM print_products 
            WHERE is_active = 1 
            ORDER BY name ASC
        ");
        $dbProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($dbProducts)) {
            // Use database products
            $products = array_map(function($product) {
                $priceInCents = PriceManager::fromDatabase($product['price']);
                return [
                    'id' => $product['id'], // Use the id from the query (product_key as id)
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $priceInCents, // Price in cents
                    'price_formatted' => PriceManager::formatPrice($priceInCents),
                    'size' => $product['size'],
                    'material' => $product['material'],
                    'printful_id' => $product['printful_id'],
                    'image_url' => $product['image_url']
                ];
            }, $dbProducts);
        } else {
            throw new Exception('No products in database');
        }
        
    } catch (Exception $e) {
        // Fallback to hardcoded products if database fails
        $products = [
            [
                'id' => 'memory_frame_8x10',
                'name' => '8x10 Memory Frame',
                'description' => 'Beautiful 8x10 inch frame for your memory',
                'price' => 2499, // Price in cents
                'price_formatted' => '$24.99',
                'size' => '8x10 inches',
                'material' => 'Premium Wood',
                'printful_id' => '12345',
                'image_url' => 'https://files.cdn.printful.com/products/2/4651_1527683086.jpg'
            ],
            [
                'id' => 'memory_frame_11x14',
                'name' => '11x14 Memory Frame',
                'description' => 'Larger 11x14 inch frame for your memory',
                'price' => 3499, // Price in cents
                'price_formatted' => '$34.99',
                'size' => '11x14 inches',
                'material' => 'Premium Wood',
                'printful_id' => '12346',
                'image_url' => 'https://files.cdn.printful.com/products/2/14292_1643092634.jpg'
            ],
            [
                'id' => 'memory_frame_16x20',
                'name' => '16x20 Memory Frame',
                'description' => 'Premium 16x20 inch frame for your memory',
                'price' => 4999, // Price in cents
                'price_formatted' => '$49.99',
                'size' => '16x20 inches',
                'material' => 'Premium Wood',
                'printful_id' => '12347',
                'image_url' => 'https://files.cdn.printful.com/products/2/1350_1527683296.jpg'
            ]
        ];
    }
    
    echo json_encode($products);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load products']);
}
?>
