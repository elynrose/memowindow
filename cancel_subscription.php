<?php
require_once 'config.php';
require_once 'unified_auth.php';
require_once 'SubscriptionManager.php';

header('Content-Type: application/json');

// Check if user is authenticated
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
$userId = $currentUser['uid'];

try {
    // Use SubscriptionManager to cancel subscription
    $subscriptionManager = new SubscriptionManager();
    $result = $subscriptionManager->cancelSubscription($userId);
    
    if ($result) {
        // Log the cancellation
        
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
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error occurred: ' . $e->getMessage()
    ]);
}
?>
