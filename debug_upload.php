<?php
/**
 * Debug File Upload Validation
 */

// Skip auto CORS headers
define('SKIP_AUTO_CORS', true);

echo "🔍 DEBUGGING FILE UPLOAD VALIDATION\n";
echo "===================================\n\n";

require_once 'secure_upload.php';

$uploader = new SecureUpload();

// Create a temporary file for testing
$tempFile = tempnam(sys_get_temp_dir(), 'test_audio_');
file_put_contents($tempFile, 'fake audio content');

echo "1. Created temp file: $tempFile\n";
echo "File exists: " . (file_exists($tempFile) ? 'Yes' : 'No') . "\n";
echo "File size: " . filesize($tempFile) . " bytes\n\n";

// Test valid file
$validFile = [
    'tmp_name' => $tempFile,
    'size' => 1024,
    'name' => 'test.mp3',
    'type' => 'audio/mpeg'
];

echo "2. Testing file validation...\n";
echo "File array:\n";
print_r($validFile);

$errors = $uploader->validateFile($validFile);

echo "\n3. Validation errors:\n";
if (empty($errors)) {
    echo "✅ No errors - validation passed\n";
} else {
    echo "❌ Errors found:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Clean up
unlink($tempFile);
echo "\n4. Cleaned up temp file\n";
?>
