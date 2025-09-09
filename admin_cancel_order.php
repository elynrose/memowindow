<?php
// admin_cancel_order.php - Admin function to cancel and delete orders
header('Content-Type: application/json');
require_once 'config.php';

// Get parameters
$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
$adminUserId = $_POST['admin_user_id'] ?? $_GET['admin_user_id'] ?? '';
$reason = $_POST['reason'] ?? $_GET['reason'] ?? 'Admin cancelled order';

if (!$orderId || !$adminUserId) {
    echo json_encode(['success' => false, 'error' => 'Missing order ID or admin user ID']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Verify admin user
    $adminStmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ? AND is_admin = 1");
    $adminStmt->execute([$adminUserId]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode(['success' => false, 'error' => 'Admin access denied']);
        exit;
    }
    
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Log the cancellation attempt
    error_log("Admin {$adminUserId} attempting to cancel order {$orderId}. Reason: {$reason}");
    
    // If order was paid, log refund requirement
    if ($order['status'] === 'paid' && $order['stripe_session_id']) {
        error_log("Order {$orderId} cancelled by admin - refund required for Stripe session: {$order['stripe_session_id']}");
    }
    
    // Delete the order
    $deleteStmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $deleteStmt->execute([$orderId]);
    
    if ($deleteStmt->rowCount() > 0) {
        // Log the successful cancellation
        error_log("Order {$orderId} cancelled and deleted by admin {$adminUserId}. Reason: {$reason}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order cancelled and deleted successfully',
            'order_id' => $orderId,
            'cancelled_by' => $admin['name'] ?? $adminUserId
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete order']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
