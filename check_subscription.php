<?php
require_once 'config.php';
require_once 'secure_auth.php';
require_once 'SubscriptionManager.php';

header('Content-Type: application/json');

try {
    // Get user ID from session or URL parameter (for backward compatibility)
    $userId = null;
    
    // Check session first
    if (isLoggedIn()) {
        $userId = getCurrentUser()['user_id'];
    } else {
        // Fallback to URL parameter for backward compatibility
        $userId = $_GET['user_id'] ?? null;
    }
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $subscriptionManager = new SubscriptionManager();
    $limits = $subscriptionManager->getUserLimits($userId);
    
    echo json_encode([
        'success' => true,
        'limits' => $limits
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
