<?php
/**
 * Test script for bulk email functionality
 */

require_once 'config.php';
require_once 'EmailNotification.php';

echo "<h1>Bulk Email Test</h1>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Test user filtering
    echo "<h2>Testing User Filters</h2>";
    
    $filters = ['all', 'active', 'subscription', 'recent', 'inactive', 'premium'];
    
    foreach ($filters as $filter) {
        $sql = "SELECT DISTINCT u.firebase_uid, u.email, u.display_name, u.created_at, u.last_login_at 
                FROM users u";
        
        switch ($filter) {
            case 'all':
                break;
            case 'active':
                $sql .= " WHERE u.last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'subscription':
                $sql .= " INNER JOIN user_subscriptions us ON u.firebase_uid = us.user_id 
                         WHERE us.status = 'active'";
                break;
            case 'recent':
                $sql .= " WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'inactive':
                $sql .= " WHERE u.last_login_at < DATE_SUB(NOW(), INTERVAL 30 DAY) OR u.last_login_at IS NULL";
                break;
            case 'premium':
                $sql .= " INNER JOIN user_subscriptions us ON u.firebase_uid = us.user_id 
                         INNER JOIN subscription_packages sp ON us.package_id = sp.id
                         WHERE us.status = 'active' AND sp.name LIKE '%Premium%'";
                break;
        }
        
        $sql .= " ORDER BY u.created_at DESC LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>{$filter} users (" . count($users) . " found):</h3>";
        if (empty($users)) {
            echo "<p>No users found for this filter.</p>";
        } else {
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>" . htmlspecialchars($user['email']) . " - " . htmlspecialchars($user['display_name'] ?: 'No name') . "</li>";
            }
            echo "</ul>";
        }
    }
    
    // Test email sending
    echo "<h2>Testing Email Sending</h2>";
    
    $emailNotification = new EmailNotification();
    $test_email = 'test@example.com';
    $test_name = 'Test User';
    
    // Test maintenance notice
    echo "<h3>Maintenance Notice Test</h3>";
    $result = $emailNotification->sendMaintenanceNotice($test_email, $test_name, [
        'date' => date('F j, Y', strtotime('+1 day')),
        'time' => '2:00 AM - 4:00 AM EST',
        'duration' => '2 hours',
        'reason' => 'System updates and performance improvements',
        'services' => 'All MemoWindow services will be temporarily unavailable'
    ]);
    
    echo "<p>Maintenance Notice: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test feature announcement
    echo "<h3>Feature Announcement Test</h3>";
    $result = $emailNotification->sendFeatureAnnouncement($test_email, $test_name, [
        'feature_name' => 'Bulk Email System',
        'description' => 'Admins can now send bulk emails to filtered users',
        'benefits' => 'Better communication with users, targeted messaging',
        'how_to_access' => 'Available in the admin bulk email section',
        'launch_date' => date('F j, Y')
    ]);
    
    echo "<p>Feature Announcement: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    echo "<h2>✅ Bulk Email Test Complete!</h2>";
    echo "<p><a href='admin_bulk_email.php'>Go to Bulk Email Interface</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1 { color: #333; }
h2 { color: #667eea; }
h3 { color: #666; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
a { color: #667eea; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
