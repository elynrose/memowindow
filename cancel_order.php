<?php
// cancel_order.php - Cancel and delete an order
header('Content-Type: application/json');
require_once 'config.php';

// Get parameters
$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
$userId = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
$reason = $_POST['reason'] ?? $_GET['reason'] ?? 'User requested cancellation';

if (!$orderId || !$userId) {
    echo json_encode(['success' => false, 'error' => 'Missing order ID or user ID']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // First, verify the order belongs to the user and get order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found or access denied']);
        exit;
    }
    
    // Check if order can be cancelled (only pending or paid orders)
    if (!in_array($order['status'], ['pending', 'paid'])) {
        echo json_encode(['success' => false, 'error' => 'Order cannot be cancelled. Status: ' . $order['status']]);
        exit;
    }
    
    // If order was paid, we might want to process a refund
    if ($order['status'] === 'paid' && $order['stripe_session_id']) {
        // Log that a refund should be processed
        error_log("Order {$orderId} cancelled - refund required for Stripe session: {$order['stripe_session_id']}");
        
        // You might want to implement automatic refund here
        // For now, we'll just log it
    }
    
    // Delete the order
    $deleteStmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
    $deleteStmt->execute([$orderId, $userId]);
    
    if ($deleteStmt->rowCount() > 0) {
        // Log the cancellation
        error_log("Order {$orderId} cancelled and deleted for user {$userId}. Reason: {$reason}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order cancelled and deleted successfully',
            'order_id' => $orderId
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete order']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
