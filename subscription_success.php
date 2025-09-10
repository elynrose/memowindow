<?php
require_once 'config.php';
require_once 'SubscriptionManager.php';

$sessionId = $_GET['session_id'] ?? '';

if (!$sessionId) {
    die('Session ID required');
}

try {
    // Set up Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Retrieve the checkout session
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    
    if ($session->payment_status === 'paid') {
        // Get subscription details
        $subscription = \Stripe\Subscription::retrieve($session->subscription);
        
        $userId = $session->metadata['user_id'];
        $packageId = $session->metadata['package_id'];
        $stripeSubscriptionId = $subscription->id;
        $stripeCustomerId = $subscription->customer;
        
        // Update user subscription in database
        $subscriptionManager = new SubscriptionManager();
        $subscriptionManager->createOrUpdateSubscription(
            $userId,
            $packageId,
            $stripeSubscriptionId,
            $stripeCustomerId,
            'active'
        );
        
        $success = true;
        $packageName = $session->metadata['package_name'];
    } else {
        $success = false;
        $error = 'Payment was not successful';
    }
    
} catch (Exception $e) {
    $success = false;
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Success - MemoWindow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }
        
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 2rem;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .package-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .package-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="success-icon">✓</div>
            <h1>Welcome to <?= htmlspecialchars($packageName) ?>!</h1>
            <p>Your subscription has been activated successfully. You can now enjoy all the features of your new plan.</p>
            
            <div class="package-info">
                <div class="package-name"><?= htmlspecialchars($packageName) ?> Plan</div>
                <p>Your subscription is now active and ready to use.</p>
            </div>
            
            <a href="app.html" class="btn">Start Creating Memories</a>
        <?php else: ?>
            <div class="error-icon">✗</div>
            <h1>Subscription Failed</h1>
            <p><?= htmlspecialchars($error ?? 'An error occurred while processing your subscription.') ?></p>
            
            <a href="subscription_checkout.php" class="btn">Try Again</a>
        <?php endif; ?>
    </div>
</body>
</html>
