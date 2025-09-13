<?php
// Simplified upload.php for testing - bypasses complex security modules
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
require_once 'config.php';
require_once 'generate_qr_code.php';

// Simple input validation
function sanitizeInput($data, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'url':
            return filter_var(trim($data), FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Get user ID from POST data (simplified for testing)
$userId = $_POST['user_id'] ?? '';
if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit;
}

// Validate required fields
if (!isset($_POST['image_url']) || empty($_POST['image_url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Image URL required']);
    exit;
}

if (!isset($_POST['title']) || empty(trim($_POST['title']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Memory title is required']);
    exit;
}

// Get and sanitize input
$imageUrl = sanitizeInput($_POST['image_url'], 'url');
$qrUrl = isset($_POST['qr_url']) ? sanitizeInput($_POST['qr_url'], 'url') : '';
$title = sanitizeInput(trim($_POST['title']));
$audioUrl = isset($_POST['audio_url']) ? sanitizeInput($_POST['audio_url'], 'url') : '';
$originalName = isset($_POST['original_name']) ? sanitizeInput($_POST['original_name']) : '';
$playUrl = isset($_POST['play_url']) ? sanitizeInput($_POST['play_url'], 'url') : '';
$uniqueId = isset($_POST['unique_id']) ? sanitizeInput($_POST['unique_id']) : '';

// Generate QR code if not provided and play URL exists
if (!$qrUrl && $playUrl) {
    $filename = 'memory_' . ($uniqueId ?: time()) . '_' . time();
    $qrFilePath = generateQRCode($playUrl, $filename);
    if ($qrFilePath) {
        $qrUrl = getQRCodeUrl($qrFilePath);
    }
}

// Validate URLs (basic check)
if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image URL']);
    exit;
}

// Save to MySQL
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Ensure table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `wave_assets` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` VARCHAR(255) NOT NULL,
            `unique_id` VARCHAR(255) NULL,
            `title` VARCHAR(255) NULL,
            `original_name` VARCHAR(255) NULL,
            `image_url` VARCHAR(1024) NOT NULL,
            `qr_url` VARCHAR(1024) NULL,
            `audio_url` VARCHAR(1024) NULL,
            `play_url` VARCHAR(1024) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_unique_id` (`unique_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Insert memory record
    $stmt = $pdo->prepare("
        INSERT INTO wave_assets (user_id, unique_id, title, original_name, image_url, qr_url, audio_url, play_url) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $uniqueId,
        $title,
        $originalName,
        $imageUrl,
        $qrUrl ?: null,
        $audioUrl ?: null,
        $playUrl ?: null
    ]);
    
    $memoryId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $memoryId,
        'image_url' => $imageUrl,
        'qr_url' => $qrUrl,
        'audio_url' => $audioUrl,
        'message' => 'Memory saved successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save memory: ' . $e->getMessage()
    ]);
}
?>
