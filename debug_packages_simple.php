<?php
// Simple debug script for production
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once 'config.php';
    require_once 'SubscriptionManager.php';
    
    $subscriptionManager = new SubscriptionManager();
    $packages = $subscriptionManager->getAvailablePackages();
    
    echo json_encode([
        'success' => true,
        'count' => count($packages),
        'packages' => $packages
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
