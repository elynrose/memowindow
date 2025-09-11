<?php
/**
 * Fix Upload Directories - Create missing directories
 */

echo "🔧 FIXING UPLOAD DIRECTORIES\n";
echo "============================\n\n";

// Define required directories
$requiredDirectories = [
    'uploads',
    'uploads/qr',
    'audio_cache',
    'backups',
    'backups/audio',
    'backups/generated-audio',
    'backups/qr-codes',
    'backups/waveforms',
    'temp',
    'rate_limit_storage'
];

$created = 0;
$existing = 0;
$failed = 0;

foreach ($requiredDirectories as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directory exists: $dir\n";
        $existing++;
    } else {
        echo "❌ Directory missing: $dir - creating...\n";
        
        if (mkdir($dir, 0755, true)) {
            echo "✅ Successfully created: $dir\n";
            $created++;
        } else {
            echo "❌ Failed to create: $dir\n";
            $failed++;
        }
    }
}

echo "\n📊 DIRECTORY CREATION RESULTS\n";
echo "=============================\n";
echo "✅ Existing directories: $existing\n";
echo "✅ Created directories: $created\n";
echo "❌ Failed to create: $failed\n";

if ($failed == 0) {
    echo "\n🎉 All required directories are now present!\n";
} else {
    echo "\n⚠️  Some directories failed to create. Check permissions.\n";
}

// Test directory permissions
echo "\n🔐 TESTING DIRECTORY PERMISSIONS\n";
echo "================================\n";

$writable = 0;
$notWritable = 0;

foreach ($requiredDirectories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Writable: $dir\n";
            $writable++;
        } else {
            echo "❌ Not writable: $dir\n";
            $notWritable++;
        }
    }
}

echo "\n📊 PERMISSION RESULTS\n";
echo "====================\n";
echo "✅ Writable directories: $writable\n";
echo "❌ Not writable directories: $notWritable\n";

if ($notWritable == 0) {
    echo "\n🎉 All directories have proper write permissions!\n";
} else {
    echo "\n⚠️  Some directories are not writable. You may need to run:\n";
    echo "   chmod 755 " . implode(' ', $requiredDirectories) . "\n";
}
?>
