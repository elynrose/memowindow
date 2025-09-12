<?php
// API endpoint for memory upload - fixes 419 and missing endpoint errors
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Load configuration
require_once '../../config.php';

try {
    // Get JSON input or form data
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }
    
    if (!$input) {
        throw new Exception('No input data received');
    }
    
    // Validate required fields
    $requiredFields = ['title', 'user_id', 'image_url'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    // Sanitize input
    $title = htmlspecialchars(trim($input['title']), ENT_QUOTES, 'UTF-8');
    $userId = htmlspecialchars(trim($input['user_id']), ENT_QUOTES, 'UTF-8');
    $imageUrl = filter_var(trim($input['image_url']), FILTER_SANITIZE_URL);
    $qrUrl = isset($input['qr_url']) ? filter_var(trim($input['qr_url']), FILTER_SANITIZE_URL) : '';
    $audioUrl = isset($input['audio_url']) ? filter_var(trim($input['audio_url']), FILTER_SANITIZE_URL) : '';
    $originalName = isset($input['original_name']) ? htmlspecialchars(trim($input['original_name']), ENT_QUOTES, 'UTF-8') : '';
    $playUrl = isset($input['play_url']) ? filter_var(trim($input['play_url']), FILTER_SANITIZE_URL) : '';
    $uniqueId = isset($input['unique_id']) ? htmlspecialchars(trim($input['unique_id']), ENT_QUOTES, 'UTF-8') : '';
    
    // Validate URLs
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid image URL provided');
    }
    
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Ensure table exists (create if not)
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
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_unique_id` (`unique_id`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Insert memory record
    $stmt = $pdo->prepare("
        INSERT INTO wave_assets (
            user_id, unique_id, title, original_name, 
            image_url, qr_url, audio_url, play_url,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $success = $stmt->execute([
        $userId,
        $uniqueId,
        $title,
        $originalName,
        $imageUrl,
        $qrUrl ?: null,
        $audioUrl ?: null,
        $playUrl ?: null
    ]);
    
    if (!$success) {
        throw new Exception('Failed to save memory to database');
    }
    
    $memoryId = $pdo->lastInsertId();
    
    // Log successful upload
    error_log("Memory uploaded successfully: ID {$memoryId}, User: {$userId}, Title: {$title}");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'id' => (int)$memoryId,
        'message' => 'Memory saved successfully',
        'data' => [
            'title' => $title,
            'image_url' => $imageUrl,
            'qr_url' => $qrUrl,
            'audio_url' => $audioUrl,
            'play_url' => $playUrl,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    // Database error
    error_log("Database error in upload API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'details' => 'Please check server logs for more information'
    ]);
    
} catch (Exception $e) {
    // General error
    error_log("Upload API error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
