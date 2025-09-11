<?php
// stripe_webhook.php - Handle Stripe payment completion and send order to Printful
require_once 'config.php';

// Set webhook endpoint secret from config
$endpoint_secret = STRIPE_WEBHOOK_SECRET;

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    require_once 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Verify webhook signature
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    
    // Handle the checkout.session.completed event
    if ($event['type'] == 'checkout.session.completed') {
        $session = $event['data']['object'];
        
        // Extract metadata
        $memoryId = $session['metadata']['memory_id'];
        $productId = $session['metadata']['product_id'];
        $firebaseUID = $session['metadata']['user_id']; // This is Firebase UID
        $imageUrl = $session['metadata']['image_url'];
        $printfulProductId = $session['metadata']['printful_product_id'];
        
        // Map Firebase UID to database user ID
        $userId = getUserIdFromFirebaseUID($firebaseUID);
        if (!$userId) {
            error_log("Webhook error: Could not find user ID for Firebase UID: " . $firebaseUID);
            http_response_code(400);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        // Get customer details
        $customerEmail = $session['customer_details']['email'];
        $customerName = $session['customer_details']['name'];
        $shippingAddress = $session['shipping_details']['address'];
        
        // Create Printful order
        $printfulOrder = createPrintfulOrder([
            'memory_id' => $memoryId,
            'product_id' => $productId,
            'printful_product_id' => $printfulProductId,
            'image_url' => $imageUrl,
            'customer_email' => $customerEmail,
            'customer_name' => $customerName,
            'shipping_address' => $shippingAddress,
            'stripe_session_id' => $session['id'],
            'amount_paid' => $session['amount_total']
        ]);
        
        // Get product and memory details for complete order data
        $product = getProduct($productId);
        $memory = getMemoryDetails($memoryId);
        
        // Save order to database
        saveOrderToDatabase([
            'stripe_session_id' => $session['id'],
            'user_id' => $userId,
            'printful_order_id' => $printfulOrder['id'] ?? null,
            'amount_paid' => $session['amount_total'],
            'status' => 'paid',
            'memory_title' => $memory['title'] ?? 'Untitled Memory',
            'memory_image_url' => $memory['image_url'] ?? '',
            'product_name' => $product['name'] ?? 'Custom Print',
            'product_variant_id' => $product['printful_id'] ?? '',
            'quantity' => 1,
            'unit_price' => $session['amount_total'] / 100, // Convert from cents to dollars
            'total_price' => $session['amount_total'] / 100, // Convert from cents to dollars
            'shipping_address' => json_encode($shippingAddress)
        ]);
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    }
    
    // Handle subscription events
    if ($event['type'] == 'customer.subscription.created' || 
        $event['type'] == 'customer.subscription.updated') {
        
        $subscription = $event['data']['object'];
        $customerId = $subscription['customer'];
        
        // Get customer details to find user_id
        $customer = \Stripe\Customer::retrieve($customerId);
        $userId = $customer->metadata['user_id'] ?? null;
        
        if ($userId) {
            // Get subscription package from metadata or price
            $packageSlug = $subscription['metadata']['package_slug'] ?? 'standard';
            
            require_once 'SubscriptionManager.php';
            $subscriptionManager = new SubscriptionManager();
            $package = $subscriptionManager->getPackageBySlug($packageSlug);
            
            if ($package) {
                // Update user subscription in database
                $subscriptionManager->createOrUpdateSubscription(
                    $userId,
                    $package['id'],
                    $subscription['id'],
                    $customerId,
                    $subscription['status']
                );
            }
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'subscription_updated']);
    }
    
    // Handle subscription cancellation
    if ($event['type'] == 'customer.subscription.deleted') {
        $subscription = $event['data']['object'];
        $customerId = $subscription['customer'];
        
        // Get customer details to find user_id
        $customer = \Stripe\Customer::retrieve($customerId);
        $userId = $customer->metadata['user_id'] ?? null;
        
        if ($userId) {
            require_once 'SubscriptionManager.php';
            $subscriptionManager = new SubscriptionManager();
            $subscriptionManager->cancelSubscription($userId);
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'subscription_cancelled']);
    }
    
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Function to get database user ID from Firebase UID
function getUserIdFromFirebaseUID($firebaseUID) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE firebase_uid = ?");
        $stmt->execute([$firebaseUID]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ? $user['id'] : null;
    } catch (PDOException $e) {
        error_log("Error getting user ID from Firebase UID: " . $e->getMessage());
        return null;
    }
}

// Function to create Printful order
function createPrintfulOrder($orderData) {
    // According to Printful API docs, the correct format is:
    $printfulData = [
        'recipient' => [
            'name' => $orderData['customer_name'],
            'email' => $orderData['customer_email'],
            'address1' => $orderData['shipping_address']['line1'],
            'address2' => $orderData['shipping_address']['line2'] ?? '',
            'city' => $orderData['shipping_address']['city'],
            'state_code' => $orderData['shipping_address']['state'],
            'country_code' => $orderData['shipping_address']['country'],
            'zip' => $orderData['shipping_address']['postal_code'],
        ],
        'items' => [[
            'variant_id' => intval($orderData['printful_product_id']), // Use variant_id for store products
            'quantity' => 1,
            'retail_price' => PriceFormatter::formatForPrintful($orderData['amount_paid']),
            'files' => [[
                'url' => $orderData['image_url'],
                'type' => 'default'
            ]]
        ]],
        'external_id' => substr('mw_' . $orderData['stripe_session_id'], 0, 32) // Printful external ID limit is 32 chars
    ];
    
    // Use the correct API endpoint format from documentation
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PRINTFUL_API_URL . 'orders?store_id=' . PRINTFUL_STORE_ID);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($printfulData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PRINTFUL_API_KEY,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return $responseData;
    } else {
        // Log the detailed error for debugging
        error_log("Printful API Error (HTTP $httpCode): " . $response);
        throw new Exception('Printful API error (' . $httpCode . '): ' . ($responseData['error']['message'] ?? $response));
    }
}

// Function to get memory details
function getMemoryDetails($memoryId) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT title, image_url FROM wave_assets WHERE id = ?");
        $stmt->execute([$memoryId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
    } catch (PDOException $e) {
        return [];
    }
}

// Function to save order to database
function saveOrderToDatabase($orderData) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Create orders table if it doesn't exist (matching existing schema)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT(11) NOT NULL,
                `order_number` VARCHAR(50) NULL,
                `status` ENUM('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending',
                `amount` DECIMAL(10,2) NOT NULL,
                `currency` VARCHAR(3) NULL,
                `stripe_payment_intent_id` VARCHAR(255) NULL,
                `stripe_session_id` VARCHAR(255) NULL,
                `shipping_address` LONGTEXT NULL,
                `billing_address` LONGTEXT NULL,
                `items` LONGTEXT NOT NULL,
                `printful_order_id` VARCHAR(100) NULL,
                `tracking_number` VARCHAR(100) NULL,
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                `amount_paid` INT(11) NOT NULL DEFAULT 0,
                `memory_title` VARCHAR(255) NULL,
                `memory_image_url` VARCHAR(1024) NULL,
                `product_name` VARCHAR(255) NULL,
                `product_variant_id` VARCHAR(255) NULL,
                `quantity` INT(11) DEFAULT 1,
                `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_stripe_session` (`stripe_session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Insert order record with all fields (matching existing schema)
        $stmt = $pdo->prepare("
            INSERT INTO `orders` 
            (`user_id`, `stripe_session_id`, `status`, `amount`, `amount_paid`, 
             `memory_title`, `memory_image_url`, `product_name`, `product_variant_id`, 
             `quantity`, `unit_price`, `total_price`, `shipping_address`, 
             `printful_order_id`, `items`, `created_at`, `updated_at`) 
            VALUES (:user_id, :stripe_session_id, :status, :amount, :amount_paid,
                    :memory_title, :memory_image_url, :product_name, :product_variant_id,
                    :quantity, :unit_price, :total_price, :shipping_address,
                    :printful_order_id, :items, NOW(), NOW())
        ");
        
        // Create items JSON
        $items = json_encode([
            [
                'product_name' => $orderData['product_name'],
                'product_variant_id' => $orderData['product_variant_id'],
                'quantity' => $orderData['quantity'],
                'unit_price' => $orderData['unit_price'],
                'total_price' => $orderData['total_price']
            ]
        ]);
        
        $stmt->execute([
            ':user_id' => $orderData['user_id'],
            ':stripe_session_id' => $orderData['stripe_session_id'],
            ':status' => $orderData['status'],
            ':amount' => $orderData['amount_paid'] / 100, // Convert cents to dollars
            ':amount_paid' => $orderData['amount_paid'],
            ':memory_title' => $orderData['memory_title'],
            ':memory_image_url' => $orderData['memory_image_url'],
            ':product_name' => $orderData['product_name'],
            ':product_variant_id' => $orderData['product_variant_id'],
            ':quantity' => $orderData['quantity'],
            ':unit_price' => $orderData['unit_price'],
            ':total_price' => $orderData['total_price'],
            ':shipping_address' => $orderData['shipping_address'],
            ':printful_order_id' => $orderData['printful_order_id'],
            ':items' => $items
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        throw new Exception('Database error: ' . $e->getMessage());
    }
}
?>
