<?php
// save_order.php - Save order immediately when created (before payment)
header('Content-Type: application/json');
require_once 'config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required = ['stripe_session_id', 'user_id', 'memory_id', 'product_id', 'customer_email', 'customer_name', 'amount'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Connect to database
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
        (`stripe_session_id`, `user_id`, `memory_id`, `product_id`, 
         `customer_email`, `customer_name`, `amount_paid`, `status`) 
        VALUES (:stripe_session_id, :user_id, :memory_id, :product_id,
                :customer_email, :customer_name, :amount_paid, :status)
    ");
    
    $stmt->execute([
        ':stripe_session_id' => $input['stripe_session_id'],
        ':user_id' => $input['user_id'],
        ':memory_id' => intval($input['memory_id']),
        ':product_id' => $input['product_id'],
        ':customer_email' => $input['customer_email'],
        ':customer_name' => $input['customer_name'],
        ':amount_paid' => intval($input['amount']),
        ':status' => 'pending'
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Order saved with pending status'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
