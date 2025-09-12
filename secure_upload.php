<?php
/**
 * Secure File Upload Handler for MemoWindow
 * Provides secure file upload validation and processing
 */

require_once 'unified_auth.php';

class SecureUpload {
    private $maxFileSize;
    private $allowedTypes;
    private $uploadDir;
    
    public function __construct($uploadDir = null) {
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB default
        $this->allowedTypes = [
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/mp4' => 'm4a',
            'audio/aac' => 'aac',
            'audio/ogg' => 'ogg',
            'audio/webm' => 'webm'
        ];
        $this->uploadDir = $uploadDir ?: __DIR__ . '/uploads';
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded or invalid upload';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'File too large. Maximum size: ' . $this->formatBytes($this->maxFileSize);
        }
        
        // Check file size is not 0
        if ($file['size'] === 0) {
            $errors[] = 'File is empty';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, $this->allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', array_keys($this->allowedTypes));
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = array_values($this->allowedTypes);
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension. Allowed extensions: ' . implode(', ', $allowedExtensions);
        }
        
        // Check for malicious content (basic check)
        $fileContent = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if (strpos($fileContent, '<?php') !== false || strpos($fileContent, '<script') !== false) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        return $errors;
    }
    
    /**
     * Generate secure filename
     */
    public function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $secureName = bin2hex(random_bytes(16)) . '.' . $extension;
        return $secureName;
    }
    
    /**
     * Move uploaded file to secure location
     */
    public function moveUploadedFile($file, $secureFilename = null) {
        if (!$secureFilename) {
            $secureFilename = $this->generateSecureFilename($file['name']);
        }
        
        $destination = $this->uploadDir . '/' . $secureFilename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Set secure permissions
            chmod($destination, 0644);
            return $secureFilename;
        }
        
        return false;
    }
    
    /**
     * Get file duration for audio files
     */
    public function getAudioDuration($filePath) {
        // This would require ffmpeg or similar tool
        // For now, return a default value
        return 0;
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Clean up old temporary files
     */
    public function cleanupTempFiles($maxAge = 3600) {
        $files = glob($this->uploadDir . '/temp_*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }
    
    /**
     * Validate Firebase Storage URL
     */
    public function validateFirebaseStorageURL($url) {
        // Check if it's a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if it's from Firebase Storage
        if (!str_contains($url, 'firebasestorage.googleapis.com')) {
            return false;
        }
        
        // Additional validation could be added here
        return true;
    }
    
    /**
     * Validate QR code URL
     */
    public function validateQRCodeURL($url) {
        // Allow Firebase Storage URLs or QR service URLs
        if ($url === 'TEMP_QR_URL') {
            return true;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Allow Firebase Storage or QR service URLs
        $allowedDomains = [
            'firebasestorage.googleapis.com',
            'api.qrserver.com',
            'qrserver.com'
        ];
        
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        foreach ($allowedDomains as $domain) {
            if (str_contains($host, $domain)) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Rate limiting for uploads
 */
class UploadRateLimit {
    private $maxUploads;
    private $timeWindow;
    
    public function __construct($maxUploads = 10, $timeWindow = 3600) {
        $this->maxUploads = $maxUploads;
        $this->timeWindow = $timeWindow;
    }
    
    public function checkRateLimit($userId) {
        $key = "upload_rate_limit_{$userId}";
        $current = apcu_fetch($key) ?: 0;
        
        if ($current >= $this->maxUploads) {
            return false;
        }
        
        apcu_store($key, $current + 1, $this->timeWindow);
        return true;
    }
    
    public function getRemainingUploads($userId) {
        $key = "upload_rate_limit_{$userId}";
        $current = apcu_fetch($key) ?: 0;
        return max(0, $this->maxUploads - $current);
    }
}

/**
 * Secure upload handler function
 */
function handleSecureUpload($file, $userId) {
    $uploader = new SecureUpload();
    $rateLimit = new UploadRateLimit();
    
    // Check rate limit
    if (!$rateLimit->checkRateLimit($userId)) {
        return [
            'success' => false,
            'error' => 'Upload rate limit exceeded. Please try again later.'
        ];
    }
    
    // Validate file
    $errors = $uploader->validateFile($file);
    if (!empty($errors)) {
        return [
            'success' => false,
            'error' => implode(', ', $errors)
        ];
    }
    
    // Move file to secure location
    $secureFilename = $uploader->moveUploadedFile($file);
    if (!$secureFilename) {
        return [
            'success' => false,
            'error' => 'Failed to save uploaded file'
        ];
    }
    
    return [
        'success' => true,
        'filename' => $secureFilename,
        'size' => $file['size'],
        'type' => $file['type']
    ];
}
?>
