<?php
// create_checkout.php - Create Stripe checkout session
header('Content-Type: application/json');
require_once 'config.php';
require_once 'unified_auth.php';

// Check if user is authenticated
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required = ['product_id', 'memory_id', 'image_url'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

$productId = $input['product_id'];
$memoryId = intval($input['memory_id']);
$imageUrl = $input['image_url'];
$userId = $currentUser['uid']; // Use authenticated user's ID
$userEmail = $currentUser['email']; // Use authenticated user's email
$userName = $currentUser['displayName'] ?? $currentUser['email'];

// Get product details
$product = getProduct($productId);
if (!$product) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

try {
    // Check if Stripe SDK is available
    if (!file_exists('vendor/autoload.php')) {
        throw new Exception('Stripe SDK not installed. Run: composer require stripe/stripe-php');
    }
    
    // Initialize Stripe
    require_once 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Create Stripe checkout session
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $product['name'],
                    'description' => $product['description'] . "\nMemory: " . $input['memory_title'] ?? 'Custom MemoryWave',
                    'images' => [$imageUrl], // Show the waveform image in checkout
                ],
                'unit_amount' => $product['price'], // Price in cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => CANCEL_URL,
        'customer_email' => $userEmail,
        'metadata' => [
            'memory_id' => $memoryId,
            'product_id' => $productId,
            'user_id' => $userId,
            'image_url' => $imageUrl,
            'printful_product_id' => $product['printful_id']
        ],
        'shipping_address_collection' => [
            'allowed_countries' => ['US', 'CA', 'GB', 'AU'], // Adjust based on Printful shipping
        ],
    ]);
    
    // Save order to database immediately (with pending status)
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Create orders table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `stripe_session_id` VARCHAR(255) NOT NULL,
                `user_id` VARCHAR(255) NOT NULL,
                `memory_id` INT UNSIGNED NOT NULL,
                `product_id` VARCHAR(100) NOT NULL,
                `printful_order_id` VARCHAR(255) NULL,
                `customer_email` VARCHAR(255) NOT NULL,
                `customer_name` VARCHAR(255) NOT NULL,
                `amount_paid` INT NOT NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_memory_id` (`memory_id`),
                INDEX `idx_stripe_session` (`stripe_session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Save order with pending status
        $stmt = $pdo->prepare("
            INSERT INTO `orders` 
            (`stripe_session_id`, `user_id`, `memory_id`, `memory_title`, `memory_image_url`,
             `product_id`, `product_name`, `product_variant_id`, `printful_order_id`,
             `customer_email`, `customer_name`, `amount_paid`, `unit_price`, `total_price`,
             `quantity`, `status`) 
            VALUES (:stripe_session_id, :user_id, :memory_id, :memory_title, :memory_image_url,
                    :product_id, :product_name, :product_variant_id, :printful_order_id,
                    :customer_email, :customer_name, :amount_paid, :unit_price, :total_price,
                    :quantity, :status)
        ");
        
        $stmt->execute([
            ':stripe_session_id' => $checkout_session->id,
            ':user_id' => $userId,
            ':memory_id' => $memoryId,
            ':memory_title' => preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', trim(html_entity_decode($input['memory_title'] ?? 'Custom Memory', ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
            ':memory_image_url' => $imageUrl,
            ':product_id' => $productId,
            ':product_name' => str_replace(['″', '×'], ['"', 'x'], preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', trim(html_entity_decode($product['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')))),
            ':product_variant_id' => $product['printful_id'],
            ':printful_order_id' => null,
            ':customer_email' => $userEmail,
            ':customer_name' => $userName,
            ':amount_paid' => $product['price'],
            ':unit_price' => PriceFormatter::centsToDollars($product['price']),
            ':total_price' => PriceFormatter::centsToDollars($product['price']),
            ':quantity' => 1,
            ':status' => 'pending'
        ]);
        
        $orderId = $pdo->lastInsertId();
        
    } catch (Exception $e) {
        // Don't fail the checkout if database save fails
        error_log('Failed to save order to database: ' . $e->getMessage());
        $orderId = null;
    }
    
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkout_session->url,
        'session_id' => $checkout_session->id,
        'order_id' => $orderId
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Checkout creation failed: ' . $e->getMessage()]);
}
?>
