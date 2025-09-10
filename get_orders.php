<?php
// get_orders.php - API endpoint to fetch user's orders
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // Validate user_id parameter
    if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'user_id parameter required'
        ]);
        exit;
    }
    
    $userId = $_GET['user_id'];
    
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Get user's orders with memory details
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.stripe_session_id,
            o.memory_id,
            o.printful_order_id,
            o.memory_title,
            o.memory_image_url,
            o.product_name,
            o.product_variant_id,
            o.quantity,
            o.unit_price,
            o.total_price,
            o.status,
            o.shipping_address,
            o.tracking_number,
            o.created_at,
            o.updated_at,
            o.product_id,
            o.customer_name,
            o.customer_email,
            o.amount_paid
        FROM orders o
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
