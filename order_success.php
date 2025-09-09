<?php
// order_success.php - Order success page
require_once 'config.php';

$sessionId = $_GET['session_id'] ?? '';

if (!$sessionId) {
    header('Location: index.html');
    exit;
}

// Get order details from Stripe
try {
    require_once 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    $memoryId = $session->metadata->memory_id ?? '';
    $productId = $session->metadata->product_id ?? '';
    
} catch (Exception $e) {
    $session = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - MemoWindow</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .success-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            color: #0b0d12;
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 24px;
        }
        .success-title {
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: #059669;
        }
        .success-message {
            font-size: 16px;
            color: #6b7280;
            margin: 0 0 32px 0;
            line-height: 1.5;
        }
        .order-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .detail-label {
            color: #6b7280;
        }
        .detail-value {
            color: #0b0d12;
            font-weight: 500;
        }
        .btn-primary {
            background: #2a4df5;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin: 8px;
        }
        .btn-secondary {
            background: #6b7280;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin: 8px;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">🎉</div>
        <h1 class="success-title">Order Confirmed!</h1>
        <p class="success-message">
            Thank you for your order! Your MemoryWave print is being prepared and will be shipped to you soon.
            You'll receive email updates about your order status.
        </p>
        
        <?php if ($session): ?>
        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <span class="detail-value"><?php echo substr($sessionId, -8); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Product:</span>
                <span class="detail-value"><?php echo getProduct($productId)['name'] ?? 'Custom Print'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount:</span>
                <span class="detail-value"><?php echo formatPrice($session->amount_total); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($session->customer_details->email); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 32px;">
            <a href="index.html" class="btn-primary">Create Another Memory</a>
            <?php if (isset($session) && isset($session->metadata->user_id)): ?>
                <a href="orders.php?user_id=<?php echo urlencode($session->metadata->user_id); ?>" class="btn-secondary">View Orders</a>
            <?php else: ?>
                <a href="index.html" class="btn-secondary">Back to MemoWindow</a>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 24px; font-size: 14px; color: #6b7280;">
            <p>Your print will be professionally produced and shipped within 3-5 business days.</p>
        </div>
    </div>
</body>
</html>
