<?php
/**
 * Email Notification System for MemoWindow
 * Handles sending emails for payments, subscriptions, and orders
 */

class EmailNotification {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    private $site_url;
    
    public function __construct() {
        // Load email configuration from environment or config
        $this->smtp_host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtp_port = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtp_username = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtp_password = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->from_email = $_ENV['FROM_EMAIL'] ?? 'noreply@memowindow.com';
        $this->from_name = $_ENV['FROM_NAME'] ?? 'MemoWindow';
        $this->site_url = $_ENV['SITE_URL'] ?? 'https://memowindow.com';
    }
    
    /**
     * Send email using PHP's mail() function (fallback)
     */
    public function sendEmail($to, $subject, $html_body, $text_body = null) {
        try {
            // If no text body provided, create one from HTML
            if (!$text_body) {
                $text_body = strip_tags($html_body);
            }
            
            // Set headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="boundary123"',
                'From: ' . $this->from_name . ' <' . $this->from_email . '>',
                'Reply-To: ' . $this->from_email,
                'X-Mailer: MemoWindow Email System'
            ];
            
            // Create multipart message
            $message = "--boundary123\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= $text_body . "\r\n\r\n";
            $message .= "--boundary123\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $html_body . "\r\n\r\n";
            $message .= "--boundary123--\r\n";
            
            // Send email
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if ($result) {
                error_log("✅ Email sent successfully to: $to");
                return ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                error_log("❌ Failed to send email to: $to");
                return ['success' => false, 'message' => 'Failed to send email'];
            }
            
        } catch (Exception $e) {
            error_log("❌ Email error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($user_email, $user_name, $payment_data) {
        $subject = "Payment Confirmation - MemoWindow";
        
        $html_body = $this->getPaymentConfirmationTemplate($user_name, $payment_data);
        $text_body = $this->getPaymentConfirmationTextTemplate($user_name, $payment_data);
        
        return $this->sendEmail($user_email, $subject, $html_body, $text_body);
    }
    
    /**
     * Send subscription confirmation email
     */
    public function sendSubscriptionConfirmation($user_email, $user_name, $subscription_data) {
        $subject = "Subscription Confirmed - MemoWindow";
        
        $html_body = $this->getSubscriptionConfirmationTemplate($user_name, $subscription_data);
        $text_body = $this->getSubscriptionConfirmationTextTemplate($user_name, $subscription_data);
        
        return $this->sendEmail($user_email, $subject, $html_body, $text_body);
    }
    
    /**
     * Send subscription cancellation email
     */
    public function sendSubscriptionCancellation($user_email, $user_name, $subscription_data) {
        $subject = "Subscription Cancelled - MemoWindow";
        
        $html_body = $this->getSubscriptionCancellationTemplate($user_name, $subscription_data);
        $text_body = $this->getSubscriptionCancellationTextTemplate($user_name, $subscription_data);
        
        return $this->sendEmail($user_email, $subject, $html_body, $text_body);
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($user_email, $user_name, $order_data) {
        $subject = "Order Confirmation - MemoWindow";
        
        $html_body = $this->getOrderConfirmationTemplate($user_name, $order_data);
        $text_body = $this->getOrderConfirmationTextTemplate($user_name, $order_data);
        
        return $this->sendEmail($user_email, $subject, $html_body, $text_body);
    }
    
    /**
     * Get base email template with MemoWindow branding
     */
    private function getBaseTemplate($title, $content) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>$title</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
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
                .status.cancelled { background: #f8d7da; color: #721c24; }
                .status.pending { background: #fff3cd; color: #856404; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎵 MemoWindow</h1>
                </div>
                <div class='content'>
                    $content
                </div>
                <div class='footer'>
                    <p>Thank you for using MemoWindow!</p>
                    <p>If you have any questions, please contact us at support@memowindow.com</p>
                    <p><a href='{$this->site_url}' style='color: #667eea;'>Visit MemoWindow</a></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Payment confirmation email template
     */
    private function getPaymentConfirmationTemplate($user_name, $payment_data) {
        $amount = '$' . number_format($payment_data['amount'], 2);
        $date = date('F j, Y \a\t g:i A', strtotime($payment_data['created_at']));
        
        $content = "
            <h2>Payment Confirmed!</h2>
            <p>Hi $user_name,</p>
            <p>Thank you for your payment! Your transaction has been successfully processed.</p>
            
            <div class='info-box'>
                <h3>Payment Details</h3>
                <p><strong>Amount:</strong> <span class='amount'>$amount</span></p>
                <p><strong>Date:</strong> $date</p>
                <p><strong>Transaction ID:</strong> {$payment_data['transaction_id']}</p>
                <p><strong>Payment Method:</strong> {$payment_data['payment_method']}</p>
            </div>
            
            <p>Your payment has been processed and your account has been updated accordingly.</p>
            
            <a href='{$this->site_url}/orders.php' class='button'>View Your Orders</a>
        ";
        
        return $this->getBaseTemplate("Payment Confirmation", $content);
    }
    
    /**
     * Payment confirmation text template
     */
    private function getPaymentConfirmationTextTemplate($user_name, $payment_data) {
        $amount = '$' . number_format($payment_data['amount'], 2);
        $date = date('F j, Y \a\t g:i A', strtotime($payment_data['created_at']));
        
        return "
PAYMENT CONFIRMED - MemoWindow

Hi $user_name,

Thank you for your payment! Your transaction has been successfully processed.

Payment Details:
- Amount: $amount
- Date: $date
- Transaction ID: {$payment_data['transaction_id']}
- Payment Method: {$payment_data['payment_method']}

Your payment has been processed and your account has been updated accordingly.

View your orders: {$this->site_url}/orders.php

Thank you for using MemoWindow!
        ";
    }
    
    /**
     * Subscription confirmation email template
     */
    private function getSubscriptionConfirmationTemplate($user_name, $subscription_data) {
        $amount = '$' . number_format($subscription_data['amount'], 2);
        $billing_cycle = ucfirst($subscription_data['billing_cycle']);
        $next_billing = date('F j, Y', strtotime($subscription_data['current_period_end']));
        
        $content = "
            <h2>Subscription Confirmed!</h2>
            <p>Hi $user_name,</p>
            <p>Welcome to MemoWindow! Your subscription has been successfully activated.</p>
            
            <div class='info-box'>
                <h3>Subscription Details</h3>
                <p><strong>Plan:</strong> {$subscription_data['package_name']}</p>
                <p><strong>Amount:</strong> <span class='amount'>$amount</span> per $billing_cycle</p>
                <p><strong>Status:</strong> <span class='status active'>Active</span></p>
                <p><strong>Next Billing:</strong> $next_billing</p>
                <p><strong>Subscription ID:</strong> {$subscription_data['stripe_subscription_id']}</p>
            </div>
            
            <p>You now have access to all premium features including unlimited memories, voice cloning, and priority support!</p>
            
            <a href='{$this->site_url}/subscription_management.php' class='button'>Manage Subscription</a>
        ";
        
        return $this->getBaseTemplate("Subscription Confirmed", $content);
    }
    
    /**
     * Subscription confirmation text template
     */
    private function getSubscriptionConfirmationTextTemplate($user_name, $subscription_data) {
        $amount = '$' . number_format($subscription_data['amount'], 2);
        $billing_cycle = ucfirst($subscription_data['billing_cycle']);
        $next_billing = date('F j, Y', strtotime($subscription_data['current_period_end']));
        
        return "
SUBSCRIPTION CONFIRMED - MemoWindow

Hi $user_name,

Welcome to MemoWindow! Your subscription has been successfully activated.

Subscription Details:
- Plan: {$subscription_data['package_name']}
- Amount: $amount per $billing_cycle
- Status: Active
- Next Billing: $next_billing
- Subscription ID: {$subscription_data['stripe_subscription_id']}

You now have access to all premium features including unlimited memories, voice cloning, and priority support!

Manage your subscription: {$this->site_url}/subscription_management.php

Thank you for using MemoWindow!
        ";
    }
    
    /**
     * Subscription cancellation email template
     */
    private function getSubscriptionCancellationTemplate($user_name, $subscription_data) {
        $end_date = date('F j, Y', strtotime($subscription_data['current_period_end']));
        
        $content = "
            <h2>Subscription Cancelled</h2>
            <p>Hi $user_name,</p>
            <p>We're sorry to see you go! Your subscription has been cancelled as requested.</p>
            
            <div class='info-box'>
                <h3>Cancellation Details</h3>
                <p><strong>Plan:</strong> {$subscription_data['package_name']}</p>
                <p><strong>Status:</strong> <span class='status cancelled'>Cancelled</span></p>
                <p><strong>Access Until:</strong> $end_date</p>
                <p><strong>Subscription ID:</strong> {$subscription_data['stripe_subscription_id']}</p>
            </div>
            
            <p>Your subscription will remain active until $end_date. After that date, you'll be moved to the free plan.</p>
            <p>You can reactivate your subscription anytime before the end date.</p>
            
            <a href='{$this->site_url}/subscription_management.php' class='button'>Reactivate Subscription</a>
        ";
        
        return $this->getBaseTemplate("Subscription Cancelled", $content);
    }
    
    /**
     * Subscription cancellation text template
     */
    private function getSubscriptionCancellationTextTemplate($user_name, $subscription_data) {
        $end_date = date('F j, Y', strtotime($subscription_data['current_period_end']));
        
        return "
SUBSCRIPTION CANCELLED - MemoWindow

Hi $user_name,

We're sorry to see you go! Your subscription has been cancelled as requested.

Cancellation Details:
- Plan: {$subscription_data['package_name']}
- Status: Cancelled
- Access Until: $end_date
- Subscription ID: {$subscription_data['stripe_subscription_id']}

Your subscription will remain active until $end_date. After that date, you'll be moved to the free plan.

You can reactivate your subscription anytime before the end date.

Manage your subscription: {$this->site_url}/subscription_management.php

Thank you for using MemoWindow!
        ";
    }
    
    /**
     * Order confirmation email template
     */
    private function getOrderConfirmationTemplate($user_name, $order_data) {
        $amount = '$' . number_format($order_data['amount'], 2);
        $date = date('F j, Y \a\t g:i A', strtotime($order_data['created_at']));
        
        $content = "
            <h2>Order Confirmed!</h2>
            <p>Hi $user_name,</p>
            <p>Thank you for your order! We've received your print order and will process it shortly.</p>
            
            <div class='info-box'>
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> {$order_data['order_id']}</p>
                <p><strong>Product:</strong> {$order_data['product_name']}</p>
                <p><strong>Amount:</strong> <span class='amount'>$amount</span></p>
                <p><strong>Date:</strong> $date</p>
                <p><strong>Status:</strong> <span class='status pending'>Processing</span></p>
            </div>
            
            <p>We'll send you another email once your order ships with tracking information.</p>
            
            <a href='{$this->site_url}/orders.php' class='button'>View Order Status</a>
        ";
        
        return $this->getBaseTemplate("Order Confirmation", $content);
    }
    
    /**
     * Order confirmation text template
     */
    private function getOrderConfirmationTextTemplate($user_name, $order_data) {
        $amount = '$' . number_format($order_data['amount'], 2);
        $date = date('F j, Y \a\t g:i A', strtotime($order_data['created_at']));
        
        return "
ORDER CONFIRMED - MemoWindow

Hi $user_name,

Thank you for your order! We've received your print order and will process it shortly.

Order Details:
- Order ID: {$order_data['order_id']}
- Product: {$order_data['product_name']}
- Amount: $amount
- Date: $date
- Status: Processing

We'll send you another email once your order ships with tracking information.

View order status: {$this->site_url}/orders.php

Thank you for using MemoWindow!
        ";
    }
}
?>
