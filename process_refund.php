<?php
// process_refund.php - Process Stripe refunds for orders
header('Content-Type: application/json');
require_once 'secure_auth.php';
require_once 'config.php';

// Require admin authentication
$adminUID = requireSecureAdmin();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';
$paymentIntentId = $input['payment_intent_id'] ?? '';
$amount = $input['amount'] ?? 0;
$reason = $input['reason'] ?? 'requested_by_customer';

if (!$orderId || !$paymentIntentId || !$amount) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Verify admin user
    $adminStmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ? AND is_admin = 1");
    $adminStmt->execute([$adminUID]);
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
    
    // Initialize Stripe
    require_once 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Process refund with Stripe
    $refund = \Stripe\Refund::create([
        'payment_intent' => $paymentIntentId,
        'amount' => $amount,
        'reason' => $reason,
        'metadata' => [
            'order_id' => $orderId,
            'admin_user' => $adminUID,
            'refund_reason' => $reason
        ]
    ]);
    
    // Update order status in database
    $updateStmt = $pdo->prepare("UPDATE orders SET status = 'refunded' WHERE id = ?");
    $updateStmt->execute([$orderId]);
    
    // Log the refund
    error_log("Refund processed by admin {$adminUID} for order {$orderId}. Stripe refund ID: {$refund->id}. Amount: " . ($amount / 100) . ". Reason: {$reason}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Refund processed successfully',
        'refund_id' => $refund->id,
        'amount' => $amount,
        'order_id' => $orderId,
        'processed_by' => $admin['name'] ?? $adminUID
    ]);
    
} catch (\Stripe\Exception\InvalidRequestException $e) {
    error_log("Stripe refund error for order {$orderId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Stripe error: ' . $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Database error during refund for order {$orderId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error during refund for order {$orderId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error processing refund: ' . $e->getMessage()]);
}
?>
