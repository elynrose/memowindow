<?php
// upload.php
// Updated with secure authentication and validation
// Requires: PHP with cURL (recommended), MySQL (PDO), write permissions to ./uploads and ./uploads/qr

header('Content-Type: application/json');

// Load configuration and secure modules
require_once 'config.php';
require_once 'secure_auth.php';
require_once 'secure_upload.php';
require_once 'secure_db.php';

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

// Secure authentication check
try {
    $userId = requireSecureAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
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

// Get and validate URLs from POST data
$imageUrl = sanitizeInput($_POST['image_url'], 'url');
$qrUrl = sanitizeInput($_POST['qr_url'], 'url');

// Validate URLs using secure upload handler
$uploader = new SecureUpload();

if (!$uploader->validateFirebaseStorageURL($imageUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Firebase Storage image URL']);
    exit;
}

if (!$uploader->validateQRCodeURL($qrUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid QR URL']);
    exit;
}

// Save to MySQL using secure database helper
try {
  $db = SecureDB::getInstance();
  
  // Ensure table exists (idempotent) - using secure table name validation
  $db->getPDO()->exec("
    CREATE TABLE IF NOT EXISTS `wave_assets` (
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

  // Insert using secure database helper
  $memoryId = $db->insert(
    "INSERT INTO wave_assets (user_id, unique_id, title, original_name, image_url, qr_url, audio_url, play_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [
      $userId,
      isset($_POST['unique_id']) ? sanitizeInput($_POST['unique_id']) : null,
      sanitizeInput(trim($_POST['title'])),
      isset($_POST['original_name']) ? sanitizeInput($_POST['original_name']) : null,
      $imageUrl,
      $qrUrl,
      isset($_POST['audio_url']) ? sanitizeInput($_POST['audio_url'], 'url') : null,
      isset($_POST['play_url']) ? sanitizeInput($_POST['play_url'], 'url') : null,
    ]);

  echo json_encode([
    'success' => true,
    'image_url' => $imageUrl,
    'qr_url'    => $qrUrl,
    'id'        => $memoryId,
    'message' => 'Memory saved successfully'
  ]);
} catch (Exception $e) {
  http_response_code(500);
  
  // Log the full error for debugging
  error_log("Upload error: " . $e->getMessage());
  
  // Return generic error message to user
  echo json_encode(['error' => 'Failed to save memory. Please try again.']);
}
