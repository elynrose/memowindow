<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$memoryId = $input['memory_id'] ?? '';
$playUrl = $input['play_url'] ?? '';
$qrUrl = $input['qr_url'] ?? '';

if (!$memoryId || !$playUrl || !$qrUrl) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: memory_id, play_url, qr_url']);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Update the memory with correct URLs
    $stmt = $pdo->prepare("UPDATE wave_assets SET play_url = ?, qr_url = ? WHERE id = ?");
    $stmt->execute([$playUrl, $qrUrl, $memoryId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Memory URLs updated successfully',
            'memory_id' => $memoryId,
            'play_url' => $playUrl,
            'qr_url' => $qrUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No memory found with ID: ' . $memoryId
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
