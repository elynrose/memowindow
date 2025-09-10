<?php
require_once 'config.php';
require_once 'SubscriptionManager.php';

header('Content-Type: application/json');

try {
    $userId = $_GET['user_id'] ?? '';
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
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
