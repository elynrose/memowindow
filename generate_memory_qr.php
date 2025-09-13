<?php
// generate_memory_qr.php - Generate QR code for an existing memory
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
require_once 'unified_auth.php';
require_once 'generate_qr_code.php';

// Get current user from unified auth system
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Get POST data
$memoryId = filter_input(INPUT_POST, 'memory_id', FILTER_VALIDATE_INT);

if (!$memoryId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Memory ID is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Get memory details
    $stmt = $pdo->prepare("
        SELECT id, user_id, unique_id, play_url, qr_url, title 
        FROM wave_assets 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$memoryId, $currentUser['uid']]);
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memory) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Memory not found or access denied']);
        exit;
    }
    
    // Generate play URL if not exists
    $playUrl = $memory['play_url'];
    if (!$playUrl && $memory['unique_id']) {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $playUrl = $baseUrl . '/play.php?uid=' . $memory['unique_id'];
    }
    
    if (!$playUrl) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cannot generate QR code: no play URL available']);
        exit;
    }
    
    // Generate QR code locally
    $filename = 'memory_' . $memoryId . '_' . time();
    $qrFilePath = generateQRCode($playUrl, $filename);
    
    if (!$qrFilePath) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to generate QR code']);
        exit;
    }
    
    // Get web URL for the QR code
    $qrWebUrl = getQRCodeUrl($qrFilePath);
    
    // Update database with new QR URL
    $stmt = $pdo->prepare("UPDATE wave_assets SET qr_url = ?, play_url = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$qrWebUrl, $playUrl, $memoryId, $currentUser['uid']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'QR code generated successfully',
            'qr_url' => $qrWebUrl,
            'play_url' => $playUrl,
            'memory_id' => $memoryId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update memory with QR code']);
    }
    
} catch (Exception $e) {
    error_log("QR generation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
