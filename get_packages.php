<?php
// get_packages.php - API endpoint to fetch subscription packages
header('Content-Type: application/json');
require_once 'config.php';
require_once 'SubscriptionManager.php';

try {
    $subscriptionManager = new SubscriptionManager();
    $packages = $subscriptionManager->getAvailablePackages();
    
    // Format packages for frontend
    $formattedPackages = [];
    foreach ($packages as $package) {
        $features = json_decode($package['features'], true) ?: [];
        
        $formattedPackages[] = [
            'id' => $package['id'],
            'name' => $package['name'],
            'slug' => $package['slug'],
            'description' => $package['description'],
            'price_monthly' => floatval($package['price_monthly']),
            'price_yearly' => floatval($package['price_yearly']),
            'features' => $features,
            'is_popular' => $package['slug'] === 'standard', // Mark standard as popular
            'is_active' => (bool)$package['is_active']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'packages' => $formattedPackages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
