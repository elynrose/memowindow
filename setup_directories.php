<?php
/**
 * MemoWindow Directory Setup Script (PHP Version)
 * Creates all required directories and sets appropriate permissions
 */

echo "🔧 MEMOWINDOW DIRECTORY SETUP (PHP)\n";
echo "===================================\n\n";

// Define required directories
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

// Colors for output (ANSI escape codes)
$colors = [
    'red' => "\033[0;31m",
    'green' => "\033[0;32m",
    'yellow' => "\033[1;33m",
    'blue' => "\033[0;34m",
    'reset' => "\033[0m"
];

// Counters
$created = 0;
$existing = 0;
$failed = 0;

echo "📁 Creating directories...\n\n";

// Create directories
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "{$colors['blue']}✅ Directory exists:{$colors['reset']} $dir\n";
        $existing++;
    } else {
        echo "{$colors['yellow']}🔨 Creating directory:{$colors['reset']} $dir\n";
        if (mkdir($dir, 0755, true)) {
            echo "{$colors['green']}✅ Successfully created:{$colors['reset']} $dir\n";
            $created++;
        } else {
            echo "{$colors['red']}❌ Failed to create:{$colors['reset']} $dir\n";
            $failed++;
        }
    }
}

echo "\n📊 DIRECTORY CREATION RESULTS\n";
echo "=============================\n";
echo "{$colors['green']}✅ Existing directories:{$colors['reset']} $existing\n";
echo "{$colors['green']}✅ Created directories:{$colors['reset']} $created\n";
echo "{$colors['red']}❌ Failed to create:{$colors['reset']} $failed\n";

if ($failed == 0) {
    echo "\n{$colors['green']}🎉 All required directories are now present!{$colors['reset']}\n";
} else {
    echo "\n{$colors['red']}⚠️  Some directories failed to create. Check permissions.{$colors['reset']}\n";
}

echo "\n🔐 Setting directory permissions...\n\n";

// Set permissions
$permissionsSet = 0;
$permissionsFailed = 0;

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "{$colors['yellow']}🔧 Setting permissions for:{$colors['reset']} $dir\n";
        if (chmod($dir, 0755)) {
            echo "{$colors['green']}✅ Permissions set:{$colors['reset']} $dir\n";
            $permissionsSet++;
        } else {
            echo "{$colors['red']}❌ Failed to set permissions:{$colors['reset']} $dir\n";
            $permissionsFailed++;
        }
    }
}

echo "\n📊 PERMISSION RESULTS\n";
echo "=====================\n";
echo "{$colors['green']}✅ Permissions set:{$colors['reset']} $permissionsSet\n";
echo "{$colors['red']}❌ Failed to set permissions:{$colors['reset']} $permissionsFailed\n";

if ($permissionsFailed == 0) {
    echo "\n{$colors['green']}🎉 All directories have proper permissions!{$colors['reset']}\n";
} else {
    echo "\n{$colors['red']}⚠️  Some directories failed to set permissions.{$colors['reset']}\n";
    echo "{$colors['yellow']}💡 You may need to run:{$colors['reset']} sudo chmod 755 " . implode(' ', $directories) . "\n";
}

echo "\n🧪 Testing directory access...\n\n";

// Test write access
$writable = 0;
$notWritable = 0;

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "{$colors['green']}✅ Writable:{$colors['reset']} $dir\n";
            $writable++;
        } else {
            echo "{$colors['red']}❌ Not writable:{$colors['reset']} $dir\n";
            $notWritable++;
        }
    }
}

echo "\n📊 WRITE ACCESS RESULTS\n";
echo "=======================\n";
echo "{$colors['green']}✅ Writable directories:{$colors['reset']} $writable\n";
echo "{$colors['red']}❌ Not writable directories:{$colors['reset']} $notWritable\n";

if ($notWritable == 0) {
    echo "\n{$colors['green']}🎉 All directories are writable!{$colors['reset']}\n";
} else {
    echo "\n{$colors['red']}⚠️  Some directories are not writable.{$colors['reset']}\n";
    echo "{$colors['yellow']}💡 You may need to run:{$colors['reset']} sudo chown -R \$(whoami):\$(whoami) " . implode(' ', $directories) . "\n";
}

echo "\n📋 FINAL SUMMARY\n";
echo "================\n";
echo "{$colors['blue']}Total directories:{$colors['reset']} " . count($directories) . "\n";
echo "{$colors['green']}Existing:{$colors['reset']} $existing\n";
echo "{$colors['green']}Created:{$colors['reset']} $created\n";
echo "{$colors['red']}Failed to create:{$colors['reset']} $failed\n";
echo "{$colors['green']}Permissions set:{$colors['reset']} $permissionsSet\n";
echo "{$colors['red']}Permissions failed:{$colors['reset']} $permissionsFailed\n";
echo "{$colors['green']}Writable:{$colors['reset']} $writable\n";
echo "{$colors['red']}Not writable:{$colors['reset']} $notWritable\n";

if ($failed == 0 && $permissionsFailed == 0 && $notWritable == 0) {
    echo "\n{$colors['green']}🎉 ALL SETUP COMPLETED SUCCESSFULLY!{$colors['reset']}\n";
    echo "{$colors['green']}✅ MemoWindow directories are ready for production!{$colors['reset']}\n";
    exit(0);
} else {
    echo "\n{$colors['yellow']}⚠️  Setup completed with some issues.{$colors['reset']}\n";
    echo "{$colors['yellow']}💡 Please review the errors above and fix them manually.{$colors['reset']}\n";
    exit(1);
}
?>