<?php
// delete_memory.php - Delete memory from database and Firebase Storage
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
// Load configuration
require_once 'config.php';

$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;
$table = 'wave_assets';

// Validate required parameters
if (!isset($_POST['memory_id']) || !isset($_POST['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$memoryId = intval($_POST['memory_id']);
$userId = $_POST['user_id'];

if ($memoryId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid memory ID']);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Get memory details first (to verify ownership and get file URLs)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            title,
            image_url,
            qr_url,
            audio_url
        FROM `$table` 
        WHERE id = :id AND user_id = :user_id
    ");
    
    $stmt->execute([
        ':id' => $memoryId,
        ':user_id' => $userId
    ]);
    
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memory) {
        http_response_code(404);
        echo json_encode(['error' => 'Memory not found or access denied']);
        exit;
    }

    // Delete from database first
    $deleteStmt = $pdo->prepare("DELETE FROM `$table` WHERE id = :id AND user_id = :user_id");
    $deleteStmt->execute([
        ':id' => $memoryId,
        ':user_id' => $userId
    ]);

    // Prepare file URLs for deletion (extract Firebase Storage paths)
    $filesToDelete = [];
    
    if ($memory['image_url'] && strpos($memory['image_url'], 'firebasestorage.googleapis.com') !== false) {
        // Extract the file path from Firebase Storage URL
        $imagePath = extractFirebaseStoragePath($memory['image_url']);
        if ($imagePath) {
            $filesToDelete[] = ['type' => 'image', 'path' => $imagePath, 'url' => $memory['image_url']];
        }
    }
    
    if ($memory['audio_url'] && strpos($memory['audio_url'], 'firebasestorage.googleapis.com') !== false) {
        $audioPath = extractFirebaseStoragePath($memory['audio_url']);
        if ($audioPath) {
            $filesToDelete[] = ['type' => 'audio', 'path' => $audioPath, 'url' => $memory['audio_url']];
        }
    }
    
    // Note: QR codes might be from external API, so we don't delete those from Firebase Storage
    
    echo json_encode([
        'success' => true,
        'message' => 'Memory deleted successfully',
        'deleted_memory' => [
            'id' => $memory['id'],
            'title' => $memory['title']
        ],
        'files_to_delete' => $filesToDelete,
        'note' => 'Database record deleted. Firebase Storage files should be deleted via client-side SDK for security.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'detail' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'detail' => $e->getMessage()
    ]);
}

// Helper function to extract Firebase Storage path from URL
function extractFirebaseStoragePath($url) {
    // Firebase Storage URLs look like: https://firebasestorage.googleapis.com/v0/b/bucket/o/path%2Fto%2Ffile.ext?alt=media&token=...
    $pattern = '/\/o\/([^?]+)/';
    if (preg_match($pattern, $url, $matches)) {
        return urldecode($matches[1]);
    }
    return null;
}
?>
