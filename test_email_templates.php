<?php
/**
 * Test script for email template system
 * Tests both database templates and email sending functionality
 */

require_once 'config.php';
require_once 'EmailNotification.php';

// Test email configuration
$test_email = 'test@example.com'; // Change this to your test email
$test_name = 'Test User';

echo "<h1>Email Template System Test</h1>";

try {
    // Test 1: Check if email templates table exists and has data
    echo "<h2>1. Testing Database Templates</h2>";
    
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE is_active = 1 ORDER BY template_name");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($templates)) {
        echo "<p style='color: red;'>❌ No active email templates found in database!</p>";
        echo "<p>Please run: <code>php setup_email_templates_table.php</code></p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($templates) . " active email templates:</p>";
        echo "<ul>";
        foreach ($templates as $template) {
            echo "<li><strong>{$template['template_name']}</strong> ({$template['template_key']})</li>";
        }
        echo "</ul>";
    }
    
    // Test 2: Test EmailNotification class with database templates
    echo "<h2>2. Testing EmailNotification Class</h2>";
    
    $emailNotification = new EmailNotification();
    
    // Test payment confirmation
    echo "<h3>Payment Confirmation Template Test</h3>";
    $payment_data = [
        'amount' => 29.99,
        'transaction_id' => 'txn_test_123456',
        'payment_method' => 'Credit Card',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $result = $emailNotification->sendPaymentConfirmation($test_email, $test_name, $payment_data);
    echo "<p>Payment Confirmation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test subscription confirmation
    echo "<h3>Subscription Confirmation Template Test</h3>";
    $subscription_data = [
        'package_name' => 'Premium Plan',
        'amount' => 19.99,
        'billing_cycle' => 'monthly',
        'stripe_subscription_id' => 'sub_test_123456',
        'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
    ];
    
    $result = $emailNotification->sendSubscriptionConfirmation($test_email, $test_name, $subscription_data);
    echo "<p>Subscription Confirmation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test subscription cancellation
    echo "<h3>Subscription Cancellation Template Test</h3>";
    $cancellation_data = [
        'package_name' => 'Premium Plan',
        'stripe_subscription_id' => 'sub_test_123456',
        'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
    ];
    
    $result = $emailNotification->sendSubscriptionCancellation($test_email, $test_name, $cancellation_data);
    echo "<p>Subscription Cancellation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test order confirmation
    echo "<h3>Order Confirmation Template Test</h3>";
    $order_data = [
        'order_id' => 'ORD-123456',
        'product_name' => 'Premium Canvas Print',
        'amount' => 49.99,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $result = $emailNotification->sendOrderConfirmation($test_email, $test_name, $order_data);
    echo "<p>Order Confirmation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test 3: Test template variable replacement
    echo "<h2>3. Testing Template Variable Replacement</h2>";
    
    $template = $templates[0] ?? null;
    if ($template) {
        echo "<h3>Testing Variable Replacement for: {$template['template_name']}</h3>";
        
        // Test variables
        $testVariables = [
            'user_name' => 'John Doe',
            'amount' => '$29.99',
            'date' => date('F j, Y \a\t g:i A'),
            'transaction_id' => 'txn_test_123',
            'payment_method' => 'Credit Card',
            'site_url' => 'https://memowindow.com'
        ];
        
        $originalSubject = $template['subject'];
        $originalBody = $template['html_body'];
        
        // Replace variables
        $newSubject = $originalSubject;
        $newBody = $originalBody;
        
        foreach ($testVariables as $key => $value) {
            $newSubject = str_replace('{{' . $key . '}}', $value, $newSubject);
            $newBody = str_replace('{{' . $key . '}}', $value, $newBody);
        }
        
        echo "<p><strong>Original Subject:</strong> " . htmlspecialchars($originalSubject) . "</p>";
        echo "<p><strong>Replaced Subject:</strong> " . htmlspecialchars($newSubject) . "</p>";
        
        if ($originalSubject !== $newSubject) {
            echo "<p style='color: green;'>✅ Variable replacement working in subject</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No variables found in subject</p>";
        }
        
        if ($originalBody !== $newBody) {
            echo "<p style='color: green;'>✅ Variable replacement working in body</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No variables found in body</p>";
        }
    }
    
    // Test 4: Test admin interface accessibility
    echo "<h2>4. Testing Admin Interface</h2>";
    
    if (file_exists('admin_email_templates.php')) {
        echo "<p style='color: green;'>✅ Admin email templates interface exists</p>";
        echo "<p><a href='admin_email_templates.php' target='_blank'>Open Admin Email Templates</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Admin email templates interface not found</p>";
    }
    
    echo "<h2>✅ Email Template System Test Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Configure SMTP settings in your environment variables</li>";
    echo "<li>Test the admin interface at <a href='admin_email_templates.php'>admin_email_templates.php</a></li>";
    echo "<li>Edit templates using the admin interface</li>";
    echo "<li>Test email sending with real SMTP configuration</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Testing Email System</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1 { color: #333; }
h2 { color: #667eea; }
h3 { color: #666; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
