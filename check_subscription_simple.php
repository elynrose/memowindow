<?php
// Simplified subscription check for testing - bypasses database lookups
header('Content-Type: application/json');

try {
    $userId = $_GET['user_id'] ?? '';
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
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
