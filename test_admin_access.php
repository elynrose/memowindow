<?php
/**
 * Test Admin Access Script
 * Tests admin access with the actual admin user
 */

// Skip auto CORS headers
define('SKIP_AUTO_CORS', true);

echo "🧪 TESTING ADMIN ACCESS\n";
echo "=======================\n\n";

require_once 'config.php';
require_once 'secure_auth.php';

// Use the actual admin user ID from the diagnostic
$adminUserId = 'FG8w39qVEySCnzotJDYBWQ30g5J2';

echo "👤 Testing with admin user: $adminUserId\n\n";

// Test 1: Check if user is admin
echo "1. 🔐 CHECKING ADMIN STATUS\n";
echo "===========================\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ? AND is_admin = 1");
    $stmt->execute([$adminUserId]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminUser) {
        echo "✅ User is admin\n";
        echo "📧 Email: {$adminUser['email']}\n";
        echo "👤 Name: {$adminUser['name']}\n";
        echo "🔑 Admin: " . ($adminUser['is_admin'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ User is not admin or not found\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 2: Test admin authentication function
echo "\n2. 🧪 TESTING AUTHENTICATION FUNCTION\n";
echo "=====================================\n";

// Simulate the admin authentication
try {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set the user_id in GET for testing
    $_GET['user_id'] = $adminUserId;
    
    // Test the authentication function
    $result = requireSecureAdmin();
    
    if ($result) {
        echo "✅ Admin authentication successful\n";
        echo "🆔 Returned user ID: $result\n";
    } else {
        echo "❌ Admin authentication failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
}

// Test 3: Test admin page access
echo "\n3. 🌐 TESTING ADMIN PAGE ACCESS\n";
echo "===============================\n";

$adminUrl = BASE_URL . "/admin.php?user_id=$adminUserId";
echo "🔗 Admin URL: $adminUrl\n";

// Test if the admin page is accessible
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
    
    // Check response content
    if (strpos($response, 'Access denied') !== false) {
        echo "⚠️  Response: Access denied\n";
    } elseif (strpos($response, 'Authentication required') !== false) {
        echo "⚠️  Response: Authentication required\n";
    } elseif (strpos($response, 'admin') !== false) {
        echo "✅ Response: Admin content detected\n";
    } else {
        echo "⚠️  Response: Content unclear\n";
    }
    
    // Show first 200 characters of response
    echo "📄 Response preview: " . substr(strip_tags($response), 0, 200) . "...\n";
    
} else {
    echo "❌ Admin page is not accessible\n";
    
    if (isset($http_response_header)) {
        $statusLine = $http_response_header[0];
        echo "📊 HTTP Status: $statusLine\n";
    }
}

// Test 4: Test other admin pages
echo "\n4. 📋 TESTING OTHER ADMIN PAGES\n";
echo "===============================\n";

$adminPages = [
    'admin_users.php' => 'User Management',
    'admin_products.php' => 'Product Management',
    'admin_orders.php' => 'Order Management',
    'analytics.php' => 'Analytics'
];

foreach ($adminPages as $page => $description) {
    $url = BASE_URL . "/$page?user_id=$adminUserId";
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        echo "✅ $description ($page): Accessible\n";
    } else {
        echo "❌ $description ($page): Not accessible\n";
    }
}

echo "\n5. 🚀 ADMIN ACCESS URLS\n";
echo "=======================\n";
echo "Use these URLs to access admin functions:\n\n";

echo "Main Admin Dashboard:\n";
echo "https://www.memowindow.com/admin.php?user_id=$adminUserId\n\n";

echo "User Management:\n";
echo "https://www.memowindow.com/admin_users.php?user_id=$adminUserId\n\n";

echo "Product Management:\n";
echo "https://www.memowindow.com/admin_products.php?user_id=$adminUserId\n\n";

echo "Order Management:\n";
echo "https://www.memowindow.com/admin_orders.php?user_id=$adminUserId\n\n";

echo "Analytics:\n";
echo "https://www.memowindow.com/analytics.php?user_id=$adminUserId\n\n";

echo "Backup Management:\n";
echo "https://www.memowindow.com/admin_backups.php?user_id=$adminUserId\n\n";

echo "📋 TEST COMPLETE\n";
echo "================\n";
echo "If admin access is still not working, check:\n";
echo "1. Firebase UID is correct\n";
echo "2. User has is_admin = 1 in database\n";
echo "3. All admin files are uploaded to server\n";
echo "4. No server-side errors in logs\n";
?>
