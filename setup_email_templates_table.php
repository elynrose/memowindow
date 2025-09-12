<?php
/**
 * Setup script to create email_templates table
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Create email_templates table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `email_templates` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `template_key` VARCHAR(100) NOT NULL UNIQUE,
            `template_name` VARCHAR(255) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `html_body` LONGTEXT NOT NULL,
            `text_body` LONGTEXT NULL,
            `variables` JSON NULL,
            `is_active` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_template_key` (`template_key`),
            INDEX `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "✅ Email templates table created successfully!\n";
    
    // Insert default email templates
    $defaultTemplates = [
        [
            'template_key' => 'payment_confirmation',
            'template_name' => 'Payment Confirmation',
            'subject' => 'Payment Confirmation - MemoWindow',
            'html_body' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 20px 0; }
        .button:hover { background: #5a6fd8; }
        .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎵 MemoWindow</h1>
        </div>
        <div class="content">
            <h2>Payment Confirmed!</h2>
            <p>Hi {{user_name}},</p>
            <p>Thank you for your payment! Your transaction has been successfully processed.</p>
            
            <div class="info-box">
                <h3>Payment Details</h3>
                <p><strong>Amount:</strong> <span class="amount">${{amount}}</span></p>
                <p><strong>Date:</strong> {{date}}</p>
                <p><strong>Transaction ID:</strong> {{transaction_id}}</p>
                <p><strong>Payment Method:</strong> {{payment_method}}</p>
            </div>
            
            <p>Your payment has been processed and your account has been updated accordingly.</p>
            
            <a href="{{site_url}}/orders.php" class="button">View Your Orders</a>
        </div>
        <div class="footer">
            <p>Thank you for using MemoWindow!</p>
            <p>If you have any questions, please contact us at support@memowindow.com</p>
            <p><a href="{{site_url}}" style="color: #667eea;">Visit MemoWindow</a></p>
        </div>
    </div>
</body>
</html>',
            'text_body' => 'PAYMENT CONFIRMED - MemoWindow

Hi {{user_name}},

Thank you for your payment! Your transaction has been successfully processed.

Payment Details:
- Amount: ${{amount}}
- Date: {{date}}
- Transaction ID: {{transaction_id}}
- Payment Method: {{payment_method}}

Your payment has been processed and your account has been updated accordingly.

View your orders: {{site_url}}/orders.php

Thank you for using MemoWindow!',
            'variables' => json_encode(['user_name', 'amount', 'date', 'transaction_id', 'payment_method', 'site_url'])
        ],
        [
            'template_key' => 'subscription_confirmation',
            'template_name' => 'Subscription Confirmation',
            'subject' => 'Subscription Confirmed - MemoWindow',
            'html_body' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 20px 0; }
        .button:hover { background: #5a6fd8; }
        .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status.active { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎵 MemoWindow</h1>
        </div>
        <div class="content">
            <h2>Subscription Confirmed!</h2>
            <p>Hi {{user_name}},</p>
            <p>Welcome to MemoWindow! Your subscription has been successfully activated.</p>
            
            <div class="info-box">
                <h3>Subscription Details</h3>
                <p><strong>Plan:</strong> {{package_name}}</p>
                <p><strong>Amount:</strong> <span class="amount">${{amount}}</span> per {{billing_cycle}}</p>
                <p><strong>Status:</strong> <span class="status active">Active</span></p>
                <p><strong>Next Billing:</strong> {{next_billing}}</p>
                <p><strong>Subscription ID:</strong> {{stripe_subscription_id}}</p>
            </div>
            
            <p>You now have access to all premium features including unlimited memories, voice cloning, and priority support!</p>
            
            <a href="{{site_url}}/subscription_management.php" class="button">Manage Subscription</a>
        </div>
        <div class="footer">
            <p>Thank you for using MemoWindow!</p>
            <p>If you have any questions, please contact us at support@memowindow.com</p>
            <p><a href="{{site_url}}" style="color: #667eea;">Visit MemoWindow</a></p>
        </div>
    </div>
</body>
</html>',
            'text_body' => 'SUBSCRIPTION CONFIRMED - MemoWindow

Hi {{user_name}},

Welcome to MemoWindow! Your subscription has been successfully activated.

Subscription Details:
- Plan: {{package_name}}
- Amount: ${{amount}} per {{billing_cycle}}
- Status: Active
- Next Billing: {{next_billing}}
- Subscription ID: {{stripe_subscription_id}}

You now have access to all premium features including unlimited memories, voice cloning, and priority support!

Manage your subscription: {{site_url}}/subscription_management.php

Thank you for using MemoWindow!',
            'variables' => json_encode(['user_name', 'package_name', 'amount', 'billing_cycle', 'next_billing', 'stripe_subscription_id', 'site_url'])
        ],
        [
            'template_key' => 'subscription_cancellation',
            'template_name' => 'Subscription Cancellation',
            'subject' => 'Subscription Cancelled - MemoWindow',
            'html_body' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Cancelled</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 20px 0; }
        .button:hover { background: #5a6fd8; }
        .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status.cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎵 MemoWindow</h1>
        </div>
        <div class="content">
            <h2>Subscription Cancelled</h2>
            <p>Hi {{user_name}},</p>
            <p>We\'re sorry to see you go! Your subscription has been cancelled as requested.</p>
            
            <div class="info-box">
                <h3>Cancellation Details</h3>
                <p><strong>Plan:</strong> {{package_name}}</p>
                <p><strong>Status:</strong> <span class="status cancelled">Cancelled</span></p>
                <p><strong>Access Until:</strong> {{end_date}}</p>
                <p><strong>Subscription ID:</strong> {{stripe_subscription_id}}</p>
            </div>
            
            <p>Your subscription will remain active until {{end_date}}. After that date, you\'ll be moved to the free plan.</p>
            <p>You can reactivate your subscription anytime before the end date.</p>
            
            <a href="{{site_url}}/subscription_management.php" class="button">Reactivate Subscription</a>
        </div>
        <div class="footer">
            <p>Thank you for using MemoWindow!</p>
            <p>If you have any questions, please contact us at support@memowindow.com</p>
            <p><a href="{{site_url}}" style="color: #667eea;">Visit MemoWindow</a></p>
        </div>
    </div>
</body>
</html>',
            'text_body' => 'SUBSCRIPTION CANCELLED - MemoWindow

Hi {{user_name}},

We\'re sorry to see you go! Your subscription has been cancelled as requested.

Cancellation Details:
- Plan: {{package_name}}
- Status: Cancelled
- Access Until: {{end_date}}
- Subscription ID: {{stripe_subscription_id}}

Your subscription will remain active until {{end_date}}. After that date, you\'ll be moved to the free plan.

You can reactivate your subscription anytime before the end date.

Manage your subscription: {{site_url}}/subscription_management.php

Thank you for using MemoWindow!',
            'variables' => json_encode(['user_name', 'package_name', 'end_date', 'stripe_subscription_id', 'site_url'])
        ],
        [
            'template_key' => 'order_confirmation',
            'template_name' => 'Order Confirmation',
            'subject' => 'Order Confirmation - MemoWindow',
            'html_body' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 20px 0; }
        .button:hover { background: #5a6fd8; }
        .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status.pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎵 MemoWindow</h1>
        </div>
        <div class="content">
            <h2>Order Confirmed!</h2>
            <p>Hi {{user_name}},</p>
            <p>Thank you for your order! We\'ve received your print order and will process it shortly.</p>
            
            <div class="info-box">
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> {{order_id}}</p>
                <p><strong>Product:</strong> {{product_name}}</p>
                <p><strong>Amount:</strong> <span class="amount">${{amount}}</span></p>
                <p><strong>Date:</strong> {{date}}</p>
                <p><strong>Status:</strong> <span class="status pending">Processing</span></p>
            </div>
            
            <p>We\'ll send you another email once your order ships with tracking information.</p>
            
            <a href="{{site_url}}/orders.php" class="button">View Order Status</a>
        </div>
        <div class="footer">
            <p>Thank you for using MemoWindow!</p>
            <p>If you have any questions, please contact us at support@memowindow.com</p>
            <p><a href="{{site_url}}" style="color: #667eea;">Visit MemoWindow</a></p>
        </div>
    </div>
</body>
</html>',
            'text_body' => 'ORDER CONFIRMED - MemoWindow

Hi {{user_name}},

Thank you for your order! We\'ve received your print order and will process it shortly.

Order Details:
- Order ID: {{order_id}}
- Product: {{product_name}}
- Amount: ${{amount}}
- Date: {{date}}
- Status: Processing

We\'ll send you another email once your order ships with tracking information.

View order status: {{site_url}}/orders.php

Thank you for using MemoWindow!',
            'variables' => json_encode(['user_name', 'order_id', 'product_name', 'amount', 'date', 'site_url'])
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO email_templates (template_key, template_name, subject, html_body, text_body, variables) 
        VALUES (:template_key, :template_name, :subject, :html_body, :text_body, :variables)
        ON DUPLICATE KEY UPDATE
        template_name = VALUES(template_name),
        subject = VALUES(subject),
        html_body = VALUES(html_body),
        text_body = VALUES(text_body),
        variables = VALUES(variables)
    ");
    
    foreach ($defaultTemplates as $template) {
        $stmt->execute($template);
    }
    
    echo "✅ Default email templates inserted successfully!\n";
    echo "📧 Email templates are now ready for admin editing.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
