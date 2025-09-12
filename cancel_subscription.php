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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

// Verify the user ID matches the logged-in user
if ($input['user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Update the subscription status to cancelled
    $stmt = $pdo->prepare("
        UPDATE subscriptions 
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
