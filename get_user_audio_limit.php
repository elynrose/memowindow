<?php
// get_user_audio_limit.php - Get user's current audio length limit
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
    $userSubscription = $subscriptionManager->getUserSubscription($userId);
    
    // Get the basic/free plan as default
    $basicPackage = $subscriptionManager->getPackageBySlug('basic');
    $maxAudioLength = 60; // fallback default
    $packageName = 'Free Plan';
    
    if ($basicPackage) {
        $maxAudioLength = intval($basicPackage['max_audio_length_seconds'] ?? 60);
        $packageName = $basicPackage['name'];
    }
    
    // Override with user's subscription if they have one
    if ($userSubscription && $userSubscription['status'] === 'active') {
        $package = $subscriptionManager->getPackageBySlug($userSubscription['package_slug']);
        if ($package) {
            $maxAudioLength = intval($package['max_audio_length_seconds'] ?? 60);
            $packageName = $package['name'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'max_audio_length_seconds' => $maxAudioLength,
        'package_name' => $packageName,
        'has_subscription' => $userSubscription && $userSubscription['status'] === 'active'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
