<?php
// get_user_audio_limit.php - Get user's current audio length limit
header('Content-Type: application/json');
require_once 'config.php';
require_once 'SubscriptionManager.php';

try {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    $subscriptionManager = new SubscriptionManager();
    $userSubscription = $subscriptionManager->getUserSubscription($userId);
    
    // Default to free plan if no subscription
    $maxAudioLength = 60; // 1 minute default
    $packageName = 'Free Plan';
    
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
