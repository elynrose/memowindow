<?php
// logout.php - Proper logout endpoint that destroys both Firebase and PHP sessions
header('Content-Type: application/json');
require_once 'config.php';
require_once 'secure_auth.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Destroy the PHP session
    logoutUser();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Logout error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Logout failed: ' . $e->getMessage()
    ]);
}
?>
