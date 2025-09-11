<?php
/**
 * Admin Access Diagnostic Script
 * Diagnoses why admin panel is not accessible on the online web server
 */

echo "🔍 ADMIN ACCESS DIAGNOSTIC\n";
echo "==========================\n\n";

require_once 'config.php';

// Test 1: Check if admin_users table exists
echo "1. 📊 CHECKING DATABASE STRUCTURE\n";
echo "==================================\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Database connection successful\n";
    
    // Check if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ admin_users table exists\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE admin_users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📋 Table structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check if there are any admin users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users WHERE is_admin = 1");
        $adminCount = $stmt->fetch()['count'];
        
        echo "\n📊 Admin users count: $adminCount\n";
        
        if ($adminCount > 0) {
            echo "✅ Admin users found in database\n";
            
            // List admin users
            $stmt = $pdo->query("SELECT firebase_uid, email, name, is_admin FROM admin_users WHERE is_admin = 1");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "👥 Admin users:\n";
            foreach ($admins as $admin) {
                echo "  - {$admin['firebase_uid']} ({$admin['email']}) - {$admin['name']}\n";
            }
        } else {
            echo "❌ No admin users found in database\n";
        }
        
    } else {
        echo "❌ admin_users table does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n2. 🔐 CHECKING AUTHENTICATION SYSTEM\n";
echo "=====================================\n";

// Test 2: Check if secure_auth.php exists and is working
if (file_exists('secure_auth.php')) {
    echo "✅ secure_auth.php exists\n";
    
    // Test if we can include it without errors
    try {
        require_once 'secure_auth.php';
        echo "✅ secure_auth.php loads without errors\n";
    } catch (Exception $e) {
        echo "❌ Error loading secure_auth.php: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ secure_auth.php does not exist\n";
}

// Test 3: Check if auth_check.php exists and is working
if (file_exists('auth_check.php')) {
    echo "✅ auth_check.php exists\n";
    
    try {
        require_once 'auth_check.php';
        echo "✅ auth_check.php loads without errors\n";
    } catch (Exception $e) {
        echo "❌ Error loading auth_check.php: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ auth_check.php does not exist\n";
}

echo "\n3. 🌐 CHECKING WEB SERVER CONFIGURATION\n";
echo "=======================================\n";

// Test 4: Check if admin.php exists
if (file_exists('admin.php')) {
    echo "✅ admin.php exists\n";
} else {
    echo "❌ admin.php does not exist\n";
}

// Test 5: Check BASE_URL configuration
echo "🌐 BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "\n";

// Test 6: Check if we can access admin.php directly
echo "\n4. 🧪 TESTING ADMIN ACCESS\n";
echo "==========================\n";

// Simulate a request to admin.php
$testUserId = 'test_admin_user';
$adminUrl = BASE_URL . "/admin.php?user_id=$testUserId";

echo "🔗 Admin URL: $adminUrl\n";

// Test if the URL is accessible
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($adminUrl, false, $context);

if ($response !== false) {
    echo "✅ Admin page is accessible\n";
    
    // Check if it's showing an error or the actual admin page
    if (strpos($response, 'Access denied') !== false) {
        echo "⚠️  Admin page shows 'Access denied' - authentication issue\n";
    } elseif (strpos($response, 'Authentication required') !== false) {
        echo "⚠️  Admin page shows 'Authentication required' - auth system working\n";
    } elseif (strpos($response, 'admin') !== false) {
        echo "✅ Admin page content detected\n";
    } else {
        echo "⚠️  Admin page accessible but content unclear\n";
    }
} else {
    echo "❌ Admin page is not accessible\n";
    
    // Check HTTP response code
    if (isset($http_response_header)) {
        $statusLine = $http_response_header[0];
        echo "📊 HTTP Status: $statusLine\n";
    }
}

echo "\n5. 🔧 TROUBLESHOOTING RECOMMENDATIONS\n";
echo "=====================================\n";

// Provide specific recommendations based on findings
if (!isset($tableExists) || !$tableExists) {
    echo "❌ ISSUE: admin_users table missing\n";
    echo "💡 SOLUTION: Run setup_admin_user.php to create the table\n";
}

if (isset($adminCount) && $adminCount == 0) {
    echo "❌ ISSUE: No admin users in database\n";
    echo "💡 SOLUTION: Create an admin user using setup_admin_user.php\n";
    echo "   Example: https://memowindow.com/setup_admin_user.php?user_id=YOUR_FIREBASE_UID\n";
}

if (!file_exists('secure_auth.php')) {
    echo "❌ ISSUE: secure_auth.php missing\n";
    echo "💡 SOLUTION: Ensure secure_auth.php is uploaded to the server\n";
}

if (!file_exists('admin.php')) {
    echo "❌ ISSUE: admin.php missing\n";
    echo "💡 SOLUTION: Ensure admin.php is uploaded to the server\n";
}

echo "\n6. 🚀 QUICK FIX COMMANDS\n";
echo "========================\n";
echo "To fix admin access issues, run these commands:\n\n";

echo "1. Create admin user:\n";
echo "   https://memowindow.com/setup_admin_user.php?user_id=YOUR_FIREBASE_UID&email=your@email.com&name=Your Name\n\n";

echo "2. Test admin access:\n";
echo "   https://memowindow.com/admin.php?user_id=YOUR_FIREBASE_UID\n\n";

echo "3. Check admin status:\n";
echo "   https://memowindow.com/check_admin.php?user_id=YOUR_FIREBASE_UID\n\n";

echo "📋 DIAGNOSTIC COMPLETE\n";
echo "======================\n";
echo "Run this script to identify the specific issue with admin access.\n";
echo "Most common issues:\n";
echo "- Missing admin_users table\n";
echo "- No admin users in database\n";
echo "- Incorrect Firebase UID\n";
echo "- Missing authentication files\n";
?>
