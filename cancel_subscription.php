<?php
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// Check if user is authenticated
$userId = requireAuth();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// No need to get user_id from input since we already have it from authentication

try {
    // Update the subscription status to cancelled
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions 
        SET status = 'cancelled', 
            cancelled_at = NOW(),
            updated_at = NOW()
        WHERE user_id = ? AND status = 'active'
    ");
    
    $result = $stmt->execute([$userId]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the cancellation
        error_log("Subscription cancelled for user: $userId");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Subscription cancelled successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'No active subscription found to cancel'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error cancelling subscription: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error occurred'
    ]);
}
?>
