<?php
require_once 'unified_auth.php';
require_once 'config.php';
require_once 'SubscriptionManager.php';

// Include Stripe library
require_once 'vendor/autoload.php';

// Check if this is an AJAX request or direct link
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!$isAjax) {
    // Direct link access - handle redirects directly
} else {
    // AJAX request - return JSON
    header('Content-Type: application/json');
}

try {
    // Get current authenticated user
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        if ($isAjax) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
        } else {
            header('Location: login.php?error=login_required');
        }
        exit;
    }
    
    $userId = $currentUser['uid'];
    $packageId = $_POST['package_id'] ?? $_GET['package_id'] ?? '';
    $billing = $_POST['billing'] ?? $_GET['billing'] ?? 'monthly';
    
    if (!$packageId) {
        throw new Exception('Package ID required');
    }
    
    $subscriptionManager = new SubscriptionManager();
    $package = $subscriptionManager->getPackageBySlug($packageId);
    
    if (!$package) {
        // Try by ID
        $stmt = $subscriptionManager->getPdo()->prepare("SELECT * FROM subscription_packages WHERE id = ? AND is_active = 1");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$package) {
        throw new Exception('Package not found');
    }
    
    // Get user details from authenticated user
    $userEmail = $currentUser['email'];
    $userName = $currentUser['displayName'] ?? $currentUser['email'];
    
    // Set up Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Determine price based on billing cycle
    $priceId = $billing === 'yearly' ? $package['stripe_price_id_yearly'] : $package['stripe_price_id_monthly'];
    $price = $billing === 'yearly' ? $package['price_yearly'] : $package['price_monthly'];
    
    if (!$priceId) {
        // For now, redirect to a simple success page since Stripe is not fully configured
        // In production, you would need to create Stripe products and prices first
        header('Location: subscription_success.php?package_id=' . urlencode($package['id']) . '&package_name=' . urlencode($package['name']) . '&billing=' . urlencode($billing));
        exit;
    }
    
    // Create Stripe checkout session
    $checkoutSession = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => BASE_URL . '/subscription_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BASE_URL . '/subscription_checkout.php',
        'customer_email' => $userEmail,
        'metadata' => [
            'user_id' => $userId,
            'package_id' => $package['id'],
            'package_name' => $package['name'],
            'billing_cycle' => $billing
        ],
        'subscription_data' => [
            'metadata' => [
                'user_id' => $userId,
                'package_id' => $package['id'],
                'package_name' => $package['name']
            ]
        ]
    ]);
    
    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'checkout_url' => $checkoutSession->url,
            'session_id' => $checkoutSession->id
        ]);
    } else {
        // Direct redirect to Stripe checkout
        header('Location: ' . $checkoutSession->url);
    }
    
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        // Redirect back to checkout page with error
        header('Location: subscription_checkout.php?error=' . urlencode($e->getMessage()));
    }
}
?>
