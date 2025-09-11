<?php
// create_direct_checkout.php - Create Stripe checkout session directly from home page
header('Content-Type: application/json');
require_once 'config.php';
require_once 'SubscriptionManager.php';

try {
    // Get parameters
    $packageSlug = $_GET['package'] ?? $_POST['package'] ?? '';
    $billingCycle = $_GET['billing'] ?? $_POST['billing'] ?? 'monthly';
    $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
    
    if (!$packageSlug || !$userId) {
        throw new Exception('Package slug and user ID are required');
    }
    
    // Get package details
    $subscriptionManager = new SubscriptionManager();
    $package = $subscriptionManager->getPackageBySlug($packageSlug);
    
    if (!$package) {
        throw new Exception('Package not found');
    }
    
    // Handle free plan
    if ($package['price_monthly'] == 0) {
        // For free plan, just redirect to app
        echo json_encode([
            'success' => true,
            'redirect_url' => 'app.html',
            'message' => 'Free plan activated'
        ]);
        exit;
    }
    
    // Get user details (you might want to fetch from Firebase or your user system)
    $userEmail = $_GET['user_email'] ?? $_POST['user_email'] ?? 'user@example.com';
    $userName = $_GET['user_name'] ?? $_POST['user_name'] ?? 'User';
    
    // Set up Stripe
    if (!file_exists('vendor/autoload.php')) {
        throw new Exception('Stripe SDK not installed');
    }
    
    require_once 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Determine price based on billing cycle
    $price = $billingCycle === 'yearly' ? $package['price_yearly'] : $package['price_monthly'];
    $priceInCents = intval($price * 100); // Convert to cents
    
    // Create Stripe checkout session
    $checkoutSession = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $package['name'] . ' Plan',
                    'description' => $package['description']
                ],
                'unit_amount' => $priceInCents,
                'recurring' => [
                    'interval' => $billingCycle === 'yearly' ? 'year' : 'month'
                ]
            ],
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => BASE_URL . '/subscription_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BASE_URL . '/index.html',
        'customer_email' => $userEmail,
        'metadata' => [
            'user_id' => $userId,
            'package_id' => $package['id'],
            'package_name' => $package['name'],
            'billing_cycle' => $billingCycle
        ],
        'subscription_data' => [
            'metadata' => [
                'user_id' => $userId,
                'package_id' => $package['id'],
                'package_name' => $package['name']
            ]
        ]
    ]);
    
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkoutSession->url,
        'session_id' => $checkoutSession->id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
