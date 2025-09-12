<?php
require_once 'config.php';
require_once 'unified_auth.php';
require_once 'SubscriptionManager.php';

header('Content-Type: application/json');

try {
    // Get current user from unified auth system
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $userId = $currentUser['uid'];
    
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
