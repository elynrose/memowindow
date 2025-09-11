<?php
/**
 * Quick MemoWindow Directory Setup (PHP Version)
 * Simple script for quick directory creation and permission setting
 */

echo "🚀 Quick MemoWindow Setup (PHP)\n";
echo "===============================\n\n";

// Define directories to create
$directories = [
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

// Create directories
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created: $dir\n";
            $created++;
        } else {
            echo "❌ Failed to create: $dir\n";
        }
    } else {
        echo "✅ Exists: $dir\n";
        $existing++;
    }
}

// Set permissions
echo "\n🔐 Setting permissions...\n";
$permissionsSet = 0;

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (chmod($dir, 0755)) {
            $permissionsSet++;
        }
    }
}

echo "\n📊 RESULTS\n";
echo "==========\n";
echo "✅ Directories created: $created\n";
echo "✅ Directories existing: $existing\n";
echo "✅ Permissions set: $permissionsSet\n";

echo "\n🎉 Setup complete! MemoWindow is ready to use.\n";
echo "📁 Created: uploads, audio_cache, backups, temp, rate_limit_storage\n";
echo "🔐 Permissions: 755 (read/write/execute for owner, read/execute for group/others)\n";
?>
