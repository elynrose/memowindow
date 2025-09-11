<?php
// get_user_subscription.php - Get user's current subscription status
header('Content-Type: application/json');
require_once 'config.php';
require_once 'secure_auth.php';
require_once 'SubscriptionManager.php';

try {
    // Check authentication - session only
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $userId = getCurrentUser()['user_id'];
    
    $subscriptionManager = new SubscriptionManager();
    
    // Get user's subscription
    $subscription = $subscriptionManager->getUserSubscription($userId);
    
    if ($subscription) {
        // User has an active subscription
        echo json_encode([
            'success' => true,
            'has_subscription' => true,
            'subscription' => [
                'package_name' => $subscription['package_name'],
                'package_slug' => $subscription['package_slug'],
                'status' => $subscription['status'],
                'memory_limit' => $subscription['memory_limit'],
                'voice_clone_limit' => $subscription['voice_clone_limit'],
                'current_period_end' => $subscription['current_period_end']
            ]
        ]);
    } else {
        // User has no subscription (free tier)
        echo json_encode([
            'success' => true,
            'has_subscription' => false,
            'subscription' => [
                'package_name' => 'Free',
                'package_slug' => 'free',
                'status' => 'active',
                'memory_limit' => 3,
                'voice_clone_limit' => 0
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
