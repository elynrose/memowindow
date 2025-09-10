<?php
// test_admin_access.php - Test admin access
require_once 'auth_check.php';

echo "Testing admin access...\n";

// Check if user_id is provided
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
echo "User ID from URL: " . ($userId ?: 'NOT PROVIDED') . "\n";

if (!$userId) {
    echo "❌ No user_id provided. Admin pages require ?user_id=YOUR_FIREBASE_UID\n";
    echo "Example: admin.php?user_id=FG8w39qVEySCnzotJDYBWQ30g5J2\n";
    exit;
}

try {
    $adminId = requireAdmin();
    echo "✅ Admin access successful! User ID: $adminId\n";
} catch (Exception $e) {
    echo "❌ Admin access failed: " . $e->getMessage() . "\n";
}
?>
