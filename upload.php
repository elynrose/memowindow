<?php
// upload.php
// Requires: PHP with cURL (recommended), MySQL (PDO), write permissions to ./uploads and ./uploads/qr

header('Content-Type: application/json');

// Load configuration
require_once 'config.php';

// --- CONFIG --- //
$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;
$table  = 'wave_assets'; // see SQL below

$uploadDir      = __DIR__ . '/uploads';
$uploadQrDir    = __DIR__ . '/uploads/qr';
$publicBaseUrl  = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; // e.g., https://example.com
$publicUploads  = $publicBaseUrl . '/uploads';              // assumes /uploads is web-accessible
$qrApiEndpoint  = 'https://api.qrserver.com/v1/create-qr-code/'; // external QR service

// Ensure folders exist
if (!is_dir($uploadDir))   { mkdir($uploadDir, 0755, true); }
if (!is_dir($uploadQrDir)) { mkdir($uploadQrDir, 0755, true); }

// Validate authentication and Firebase Storage URLs
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Authentication required - user_id missing']);
  exit;
}

if (!isset($_POST['image_url']) || empty($_POST['image_url'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Image URL required']);
  exit;
}

if (!isset($_POST['qr_url']) || empty($_POST['qr_url'])) {
  http_response_code(400);
  echo json_encode(['error' => 'QR URL required']);
  exit;
}

if (!isset($_POST['title']) || empty(trim($_POST['title']))) {
  http_response_code(400);
  echo json_encode(['error' => 'Memory title is required']);
  exit;
}

// Get URLs from POST data
$imageUrl = $_POST['image_url'];
$qrUrl = $_POST['qr_url'];

if (!filter_var($imageUrl, FILTER_VALIDATE_URL) || !str_contains($imageUrl, 'firebasestorage.googleapis.com')) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid Firebase Storage image URL']);
  exit;
}

// Allow QR API URLs or Firebase Storage URLs
if (!filter_var($qrUrl, FILTER_VALIDATE_URL) && $qrUrl !== 'TEMP_QR_URL') {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid QR URL']);
  exit;
}

// Save to MySQL
try {
  // First try to connect to MySQL server (without database)
  $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);
  
  // Create database if it doesn't exist
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  
  // Now connect to the specific database
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);

  // Ensure table exists (idempotent)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `$table` (
      `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` VARCHAR(255) NOT NULL,
      `unique_id` VARCHAR(255) NULL,
      `title` VARCHAR(255) NULL,
      `original_name` VARCHAR(255) NULL,
      `image_url` VARCHAR(1024) NOT NULL,
      `qr_url` VARCHAR(1024) NOT NULL,
      `audio_url` VARCHAR(1024) NULL,
      `play_url` VARCHAR(1024) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_user_id` (`user_id`),
      INDEX `idx_unique_id` (`unique_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $stmt = $pdo->prepare("INSERT INTO `$table` (`user_id`,`unique_id`,`title`,`original_name`,`image_url`,`qr_url`,`audio_url`,`play_url`) VALUES (:user_id,:unique_id,:title,:original_name,:image_url,:qr_url,:audio_url,:play_url)");
  $stmt->execute([
    ':user_id'       => $_POST['user_id'],
    ':unique_id'     => isset($_POST['unique_id']) ? $_POST['unique_id'] : null,
    ':title'         => trim($_POST['title']),
    ':original_name' => isset($_POST['original_name']) ? $_POST['original_name'] : null,
    ':image_url'     => $imageUrl,
    ':qr_url'        => $qrUrl,
    ':audio_url'     => isset($_POST['audio_url']) ? $_POST['audio_url'] : null,
    ':play_url'      => isset($_POST['play_url']) ? $_POST['play_url'] : null,
  ]);

  echo json_encode([
    'success' => true,
    'image_url' => $imageUrl,
    'qr_url'    => $qrUrl,
    'id'        => $pdo->lastInsertId(),
    'message' => 'Memory saved successfully'
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  
  // Provide more specific error messages
  if ($e->getCode() == 1045) {
    echo json_encode([
      'error' => 'Database authentication failed', 
      'detail' => 'Please check MySQL username/password in upload.php config',
      'suggestion' => 'Try accessing phpMyAdmin at http://localhost/phpmyadmin to verify credentials'
    ]);
  } else {
    echo json_encode(['error' => 'Database error', 'detail' => $e->getMessage(), 'code' => $e->getCode()]);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'General error', 'detail' => $e->getMessage()]);
}
