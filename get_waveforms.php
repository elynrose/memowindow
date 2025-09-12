<?php
// get_waveforms.php
// Retrieve user's waveforms from database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Load unified authentication
require_once 'unified_auth.php';

// --- CONFIG --- //
$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;
$table  = 'wave_assets';

// Debug: Check session state
error_log("get_waveforms.php called - Session ID: " . session_id() . " Session data: " . print_r($_SESSION, true));

$debugInfo = [
  'session_id' => session_id(),
  'session_data' => $_SESSION,
  'is_authenticated' => isAuthenticated()
];

// Check authentication - get current user
try {
  $currentUser = getCurrentUser();
  if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required', 'debug' => $debugInfo]);
    exit;
  }
  
  // Debug: Check if user has required fields
  if (!isset($currentUser['uid'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid user data', 'detail' => 'User object missing uid field', 'debug' => $debugInfo]);
    exit;
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Authentication error', 'detail' => $e->getMessage(), 'debug' => $debugInfo]);
  exit;
}

$userId = $currentUser['uid']; // Get UID from the authenticated user object
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
