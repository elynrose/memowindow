<?php
require_once 'config.php';
require_once 'unified_auth.php';

header('Content-Type: application/json');

// Check if user is authenticated
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
$userId = $currentUser['uid'];
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

try {
    // Get current subscription details
    $currentSubscription = null;
    $hasActiveSubscription = false;
    
    $stmt = $pdo->prepare("
        SELECT us.*, sp.name as package_name, sp.slug as package_slug, sp.price_monthly, sp.price_yearly, sp.features
        FROM user_subscriptions us 
        JOIN subscription_packages sp ON us.package_id = sp.id 
        WHERE us.user_id = ? AND us.status = 'active'
        ORDER BY us.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $currentSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasActiveSubscription = !empty($currentSubscription);
    
    // Get subscription history
    $subscriptionHistory = [];
    $stmt = $pdo->prepare("
        SELECT us.*, sp.name as package_name, sp.slug as package_slug
        FROM user_subscriptions us 
        JOIN subscription_packages sp ON us.package_id = sp.id 
        WHERE us.user_id = ? 
        ORDER BY us.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $subscriptionHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available packages
    $availablePackages = [];
    $stmt = $pdo->prepare("SELECT * FROM subscription_packages WHERE is_active = 1 ORDER BY price_monthly ASC");
    $stmt->execute();
    $availablePackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user limits/usage
    $userLimits = [
        'memories_used' => 0,
        'memories_limit' => 5, // Default free limit
        'audio_minutes_used' => 0,
        'audio_minutes_limit' => 60 // Default free limit
    ];
    
    // Get actual usage from memories table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM memories WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $userLimits['memories_used'] = $result['count'] ?? 0;
    
    // Get audio usage (approximate - you might need to calculate actual audio duration)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM memories WHERE user_id = ? AND audio_url IS NOT NULL");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $userLimits['audio_minutes_used'] = ($result['count'] ?? 0) * 2; // Approximate 2 minutes per memory
    
    // Update limits based on subscription
    if ($hasActiveSubscription) {
        $features = json_decode($currentSubscription['features'], true);
        if ($features) {
            foreach ($features as $feature) {
                if (strpos($feature, 'memories') !== false) {
                    preg_match('/(\d+)/', $feature, $matches);
                    if ($matches) {
                        $userLimits['memories_limit'] = (int)$matches[1];
                    }
                }
                if (strpos($feature, 'audio') !== false) {
                    preg_match('/(\d+)/', $feature, $matches);
                    if ($matches) {
                        $userLimits['audio_minutes_limit'] = (int)$matches[1];
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'currentSubscription' => $currentSubscription,
            'hasActiveSubscription' => $hasActiveSubscription,
            'subscriptionHistory' => $subscriptionHistory,
            'availablePackages' => $availablePackages,
            'userLimits' => $userLimits
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching subscription data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error occurred'
    ]);
}
?>
