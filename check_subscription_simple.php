<?php
// Simplified subscription check for testing - bypasses database lookups
header('Content-Type: application/json');

// Load unified auth
require_once 'unified_auth.php';

try {
    // Get current user from unified auth system
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $userId = $currentUser['uid'];
    
    // Always allow memory creation for testing
    echo json_encode([
        'success' => true,
        'limits' => [
            'memory_limit' => 100,
            'memory_used' => 0,
            'can_create_memory' => [
                'allowed' => true,
                'reason' => 'Testing mode - always allowed'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
