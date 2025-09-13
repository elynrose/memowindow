<?php
// generate_qr_code.php - Generate and save QR codes locally
require_once 'config.php';

/**
 * Generate QR code and save it locally
 * @param string $data The data to encode in the QR code
 * @param string $filename Optional custom filename (without extension)
 * @return string|false Returns the local file path on success, false on failure
 */
function generateQRCode($data, $filename = null) {
    // Create qr_codes directory if it doesn't exist
    $qrDir = __DIR__ . '/qr_codes';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }
    
    // Generate filename if not provided
    if (!$filename) {
        $filename = 'qr_' . md5($data) . '_' . time();
    }
    
    // Ensure filename is safe
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    $filepath = $qrDir . '/' . $filename . '.png';
    
    // Check if QR code already exists
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    // Build QR code API URL
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
    $params = [
        'size' => '1200x1200',
        'margin' => '1',
        'data' => $data
    ];
    
    $qrUrl = $qrApiUrl . '?' . http_build_query($params);
    
    // Download the QR code
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'MemoWindow QR Generator/1.0'
        ]
    ]);
    
    $qrImageData = file_get_contents($qrUrl, false, $context);
    
    if ($qrImageData === false) {
        error_log("Failed to download QR code from: " . $qrUrl);
        return false;
    }
    
    // Save the image locally
    $result = file_put_contents($filepath, $qrImageData);
    
    if ($result === false) {
        error_log("Failed to save QR code to: " . $filepath);
        return false;
    }
    
    // Verify the file was created and is a valid image
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        error_log("QR code file was not created properly: " . $filepath);
        return false;
    }
    
    // Get image info to verify it's a valid PNG
    $imageInfo = getimagesize($filepath);
    if ($imageInfo === false || $imageInfo['mime'] !== 'image/png') {
        error_log("Invalid QR code image: " . $filepath);
        unlink($filepath); // Remove invalid file
        return false;
    }
    
    return $filepath;
}

/**
 * Get the web-accessible URL for a QR code file
 * @param string $filepath The local file path
 * @return string The web URL
 */
function getQRCodeUrl($filepath) {
    if (!$filepath || !file_exists($filepath)) {
        return '';
    }
    
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $relativePath = str_replace(__DIR__ . '/', '', $filepath);
    
    return $baseUrl . '/' . $relativePath;
}

/**
 * Generate QR code for a memory
 * @param int $memoryId The memory ID
 * @param string $playUrl The play URL for the memory
 * @return string|false Returns the local file path on success, false on failure
 */
function generateMemoryQRCode($memoryId, $playUrl) {
    $filename = 'memory_' . $memoryId . '_' . time();
    return generateQRCode($playUrl, $filename);
}

// If called directly, handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['PHP_SELF']) === 'generate_qr_code.php') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $data = $input['data'] ?? '';
    $filename = $input['filename'] ?? null;
    
    if (empty($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Data parameter is required']);
        exit;
    }
    
    $filepath = generateQRCode($data, $filename);
    
    if ($filepath) {
        $webUrl = getQRCodeUrl($filepath);
        echo json_encode([
            'success' => true,
            'filepath' => $filepath,
            'web_url' => $webUrl,
            'filename' => basename($filepath)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to generate QR code']);
    }
}
?>
