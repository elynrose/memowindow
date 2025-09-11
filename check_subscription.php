<?php
require_once 'config.php';
require_once 'secure_auth.php';
require_once 'SubscriptionManager.php';

header('Content-Type: application/json');

try {
    // Check authentication - session only
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $userId = getCurrentUser()['user_id'];
    
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
