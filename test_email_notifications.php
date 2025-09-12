<?php
/**
 * Test script for email notifications
 * This script tests the email notification system
 */

require_once 'EmailNotification.php';

// Handle individual template testing from admin interface
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_id']) && isset($_POST['test_email'])) {
    $templateId = $_POST['template_id'];
    $test_email = $_POST['test_email'];
    $test_name = 'Test User';
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($template) {
            $emailNotification = new EmailNotification();
            
            // Test based on template type
            switch ($template['template_key']) {
                case 'payment_confirmation':
                    $result = $emailNotification->sendPaymentConfirmation($test_email, $test_name, [
                        'amount' => 29.99,
                        'transaction_id' => 'txn_test_' . time(),
                        'payment_method' => 'Credit Card',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'subscription_confirmation':
                    $result = $emailNotification->sendSubscriptionConfirmation($test_email, $test_name, [
                        'package_name' => 'Premium Plan',
                        'amount' => 19.99,
                        'billing_cycle' => 'monthly',
                        'stripe_subscription_id' => 'sub_test_' . time(),
                        'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
                    ]);
                    break;
                    
                case 'subscription_cancellation':
                    $result = $emailNotification->sendSubscriptionCancellation($test_email, $test_name, [
                        'package_name' => 'Premium Plan',
                        'stripe_subscription_id' => 'sub_test_' . time(),
                        'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
                    ]);
                    break;
                    
                case 'order_confirmation':
                    $result = $emailNotification->sendOrderConfirmation($test_email, $test_name, [
                        'order_id' => 'ORD-' . time(),
                        'product_name' => 'Premium Canvas Print',
                        'amount' => 49.99,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                default:
                    $result = ['success' => false, 'message' => 'Unknown template type'];
            }
            
            echo json_encode($result);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Template not found']);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Test email configuration
$test_email = 'test@example.com'; // Change this to your test email
$test_name = 'Test User';

echo "<h1>Email Notification System Test</h1>";

try {
    $emailNotification = new EmailNotification();
    
    echo "<h2>Testing Email Notification System...</h2>";
    
    // Test 1: Payment Confirmation
    echo "<h3>1. Testing Payment Confirmation Email</h3>";
    $payment_data = [
        'amount' => 29.99,
        'transaction_id' => 'txn_test_123456',
        'payment_method' => 'Credit Card',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $result = $emailNotification->sendPaymentConfirmation($test_email, $test_name, $payment_data);
    echo "<p>Payment Confirmation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test 2: Subscription Confirmation
    echo "<h3>2. Testing Subscription Confirmation Email</h3>";
    $subscription_data = [
        'package_name' => 'Premium Plan',
        'amount' => 19.99,
        'billing_cycle' => 'monthly',
        'stripe_subscription_id' => 'sub_test_123456',
        'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
    ];
    
    $result = $emailNotification->sendSubscriptionConfirmation($test_email, $test_name, $subscription_data);
    echo "<p>Subscription Confirmation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test 3: Subscription Cancellation
    echo "<h3>3. Testing Subscription Cancellation Email</h3>";
    $cancellation_data = [
        'package_name' => 'Premium Plan',
        'stripe_subscription_id' => 'sub_test_123456',
        'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
    ];
    
    $result = $emailNotification->sendSubscriptionCancellation($test_email, $test_name, $cancellation_data);
    echo "<p>Subscription Cancellation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    // Test 4: Order Confirmation
    echo "<h3>4. Testing Order Confirmation Email</h3>";
    $order_data = [
        'order_id' => 'ORD-123456',
        'product_name' => 'Premium Canvas Print',
        'amount' => 49.99,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $result = $emailNotification->sendOrderConfirmation($test_email, $test_name, $order_data);
    echo "<p>Order Confirmation: " . ($result['success'] ? '✅ Success' : '❌ Failed - ' . $result['message']) . "</p>";
    
    echo "<h2>✅ Email Notification System Test Complete!</h2>";
    echo "<p><strong>Note:</strong> If emails are not being sent, check your server's mail configuration and ensure SMTP settings are properly configured in your environment variables.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Testing Email System</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1 { color: #333; }
h2 { color: #667eea; }
h3 { color: #666; }
p { margin: 10px 0; }
</style>
