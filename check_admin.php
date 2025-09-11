<?php
/**
 * Check if user is admin
 * API endpoint to verify admin status for a given user ID
 */

require_once 'config.php';
require_once 'secure_auth.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Get user ID from request
    // Check authentication - session only
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $userId = getCurrentUser()['user_id'];
    
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check if user is admin
    $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $isAdmin = $user && $user['is_admin'] == 1;
    
    echo json_encode([
        'success' => true,
        'is_admin' => $isAdmin,
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'is_admin' => false
    ]);
}
?>
