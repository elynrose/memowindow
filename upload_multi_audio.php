<?php
// upload_multi_audio.php - Handle multiple audio file uploads for a single memory
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$requiredFields = ['user_id', 'title', 'image_url', 'qr_url', 'unique_id', 'play_url', 'audio_files'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

$userId = $input['user_id'];
$title = trim($input['title']);
$imageUrl = $input['image_url'];
$qrUrl = $input['qr_url'];
$uniqueId = $input['unique_id'];
$playUrl = $input['play_url'];
$audioFiles = $input['audio_files']; // Array of audio file objects

if (empty($title) || empty($audioFiles) || !is_array($audioFiles)) {
    http_response_code(400);
    echo json_encode(['error' => 'Title and audio files are required']);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Start transaction
    $pdo->beginTransaction();

    // Insert main memory record
    $stmt = $pdo->prepare("
        INSERT INTO wave_assets (
            user_id, title, original_name, image_url, qr_url, 
            audio_url, play_url, unique_id, multi_audio_enabled, audio_count
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Use first audio file as primary audio_url for backward compatibility
    $primaryAudioUrl = $audioFiles[0]['audio_url'] ?? null;
    $originalName = $audioFiles[0]['original_filename'] ?? 'Multi-audio Memory';
    
    $stmt->execute([
        $userId,
        $title,
        $originalName,
        $imageUrl,
        $qrUrl,
        $primaryAudioUrl,
        $playUrl,
        $uniqueId,
        true, // multi_audio_enabled
        count($audioFiles) // audio_count
    ]);
    
    $memoryId = $pdo->lastInsertId();
    
    // Insert individual audio files
    $audioStmt = $pdo->prepare("
        INSERT INTO memory_audio_files (
            memory_id, audio_url, original_filename, file_size, 
            duration, upload_order, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($audioFiles as $index => $audioFile) {
        $audioStmt->execute([
            $memoryId,
            $audioFile['audio_url'],
            $audioFile['original_filename'],
            $audioFile['file_size'] ?? null,
            $audioFile['duration'] ?? null,
            $index + 1, // upload_order (1-based)
            true // is_active
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'memory_id' => $memoryId,
        'image_url' => $imageUrl,
        'qr_url' => $qrUrl,
        'audio_count' => count($audioFiles),
        'message' => 'Multi-audio memory saved successfully'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'detail' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'General error',
        'detail' => $e->getMessage()
    ]);
}
?>
