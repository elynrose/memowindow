<?php
// save_custom_url.php - Save custom URL for a memory
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Debug: Log that the script is being called
error_log("Custom URL script called at " . date('Y-m-d H:i:s'));

require_once 'config.php';
require_once 'unified_auth.php';

// Get current user from unified auth system
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Get POST data
$memoryId = filter_input(INPUT_POST, 'memory_id', FILTER_VALIDATE_INT);
$customUrl = filter_input(INPUT_POST, 'custom_url', FILTER_SANITIZE_STRING);

if (!$memoryId || !$customUrl) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// No validation - accept any custom URL

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check if memory exists and belongs to user
    $stmt = $pdo->prepare("SELECT id, user_id, title FROM wave_assets WHERE id = ?");
    $stmt->execute([$memoryId]);
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memory) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Memory not found']);
        exit;
    }
    
    // Check ownership
    if ($memory['user_id'] !== $currentUser['uid']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Check if the custom URL is already the same
    if ($memory['custom_url'] === $customUrl) {
        // Generate the new URL - play page with custom URL
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $newUrl = $baseUrl . '/play.php/' . $customUrl;
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom URL is already set',
            'new_url' => $newUrl,
            'custom_url' => $customUrl
        ]);
        exit;
    }
    
    // Subscription check temporarily disabled for testing
    // TODO: Re-enable subscription check later
    
    // Check if custom URL is already taken by another memory
    $stmt = $pdo->prepare("SELECT id FROM wave_assets WHERE custom_url = ? AND id != ?");
    $stmt->execute([$customUrl, $memoryId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This custom URL is already taken. Please choose a different one.']);
        exit;
    }
    
    // Debug: Check what we're trying to update
    error_log("Custom URL update attempt: memory_id=$memoryId, custom_url=$customUrl, user_id=" . $currentUser['uid']);
    
    // Update the memory with custom URL (remove user_id check for now)
    $stmt = $pdo->prepare("UPDATE wave_assets SET custom_url = ? WHERE id = ?");
    $stmt->execute([$customUrl, $memoryId]);
    
    error_log("Custom URL update query executed. Rows affected: " . $stmt->rowCount());
    
    if ($stmt->rowCount() > 0) {
        // Generate the new URL - play page with custom URL
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $newUrl = $baseUrl . '/play.php/' . $customUrl;
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom URL saved successfully',
            'new_url' => $newUrl,
            'custom_url' => $customUrl
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'URL already saved'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Custom URL save error: " . $e->getMessage());
    error_log("Custom URL save error details: " . print_r([
        'memory_id' => $memoryId,
        'custom_url' => $customUrl,
        'user_id' => $currentUser['uid'] ?? 'unknown'
    ], true));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred: ' . $e->getMessage()]);
}
?>
