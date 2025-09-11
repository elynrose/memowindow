<?php
// get_waveforms.php
// Retrieve user's waveforms from database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
require_once 'config.php';
require_once 'secure_auth.php';

// --- CONFIG --- //
$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;
$table  = 'wave_assets';

// Get user ID from session or URL parameter (for backward compatibility)
$userId = null;

// Check session first
if (isLoggedIn()) {
    $userId = getCurrentUser()['user_id'];
} else {
    // Fallback to URL parameter for backward compatibility
    $userId = $_GET['user_id'] ?? null;
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

try {
  // Connect to database
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);

  // Get total count for pagination
  $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM `$table` WHERE user_id = :user_id");
  $countStmt->execute([':user_id' => $userId]);
  $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

  // Get user's waveforms with pagination
  $stmt = $pdo->prepare("
    SELECT 
      id,
      title,
      original_name,
      image_url,
      qr_url,
      audio_url,
      created_at
    FROM `$table` 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
  ");
  
  $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
  $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $waveforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Return the waveforms with pagination info
  echo json_encode([
    'waveforms' => $waveforms,
    'total' => $totalCount,
    'offset' => $offset,
    'limit' => $limit,
    'has_more' => ($offset + $limit) < $totalCount
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
?>
