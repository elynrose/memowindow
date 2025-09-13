<?php
/**
 * upload_invitation_audio.php - Upload audio files for invitation submissions
 * No authentication required - uses invitation validation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
require_once 'invitation_system.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No audio file uploaded']);
        exit;
    }
    
    $audioFile = $_FILES['audio'];
    $invitationId = $_POST['invitation_id'] ?? '';
    
    if (empty($invitationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
        exit;
    }
    
    // Validate invitation
    $invitationSystem = new InvitationSystem();
    $invitation = $invitationSystem->getInvitationById($invitationId);
    
    if (!$invitation) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid invitation']);
        exit;
    }
    
    // Use comprehensive validation
    $validation = $invitationSystem->validateInvitationForSubmission($invitation['invitation_token']);
    
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit;
    }
    
    // Validate CAPTCHA
    $captchaResponse = $_POST['captcha_response'] ?? '';
    if (empty($captchaResponse)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'CAPTCHA verification required']);
        exit;
    }
    
    // Verify CAPTCHA with Google
    $captchaSecret = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Test secret key
    $captchaVerifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $captchaData = [
        'secret' => $captchaSecret,
        'response' => $captchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $captchaOptions = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($captchaData)
        ]
    ];
    
    $captchaContext = stream_context_create($captchaOptions);
    $captchaResult = file_get_contents($captchaVerifyUrl, false, $captchaContext);
    $captchaJson = json_decode($captchaResult, true);
    
    if (!$captchaJson['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'CAPTCHA verification failed']);
        exit;
    }
    
    // Validate email if invitation is not public
    $submitterEmail = $_POST['submitter_email'] ?? '';
    if (!$invitation['allow_public'] && strtolower($submitterEmail) !== strtolower($invitation['invited_email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email must match the invited email address']);
        exit;
    }
    
    // Validate file type
    $allowedTypes = ['audio/wav', 'audio/mpeg', 'audio/mp3', 'audio/webm', 'audio/ogg', 'application/octet-stream'];
    $fileType = mime_content_type($audioFile['tmp_name']);
    
    // Also check file extension as fallback
    $extension = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['wav', 'mp3', 'm4a', 'webm', 'ogg'];
    
    if (!in_array($fileType, $allowedTypes) && !in_array($extension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid audio file type. Detected: ' . $fileType . ', Extension: ' . $extension]);
        exit;
    }
    
    // Validate file size (max 50MB)
    $maxSize = 50 * 1024 * 1024; // 50MB
    if ($audioFile['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Audio file too large (max 50MB)']);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'uploads/invitation_audio/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($audioFile['name'], PATHINFO_EXTENSION);
    $filename = 'invitation_' . $invitationId . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($audioFile['tmp_name'], $filePath)) {
        // Generate public URL
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $audioUrl = $baseUrl . '/' . $filePath;
        
        echo json_encode([
            'success' => true,
            'audio_url' => $audioUrl,
            'filename' => $filename,
            'file_size' => $audioFile['size']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save audio file']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Get invitation by ID (helper function)
 */
function getInvitationById($invitationId) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT * FROM email_invitations WHERE id = ?");
        $stmt->execute([$invitationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}
?>
