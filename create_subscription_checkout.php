<?php
require_once 'config.php';
require_once 'SubscriptionManager.php';

header('Content-Type: application/json');

try {
    $userId = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
    $packageId = $_POST['package_id'] ?? $_GET['package_id'] ?? '';
    $billing = $_POST['billing'] ?? $_GET['billing'] ?? 'monthly';
    
    if (!$userId || !$packageId) {
        throw new Exception('User ID and Package ID required');
    }
    
    $subscriptionManager = new SubscriptionManager();
    $package = $subscriptionManager->getPackageBySlug($packageId);
    
    if (!$package) {
        // Try by ID
        $stmt = $subscriptionManager->pdo->prepare("SELECT * FROM subscription_packages WHERE id = ? AND is_active = 1");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$package) {
        throw new Exception('Package not found');
    }
    
    // Get user details (you might want to fetch from Firebase or your user system)
    $userEmail = $_POST['user_email'] ?? 'user@example.com'; // You'll need to get this from your auth system
    $userName = $_POST['user_name'] ?? 'User'; // You'll need to get this from your auth system
    
    // Set up Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Determine price based on billing cycle
    $priceId = $billing === 'yearly' ? $package['stripe_price_id_yearly'] : $package['stripe_price_id_monthly'];
    $price = $billing === 'yearly' ? $package['price_yearly'] : $package['price_monthly'];
    
    if (!$priceId) {
        throw new Exception('Stripe price ID not configured for this package and billing cycle');
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
        'cancel_url' => BASE_URL . '/subscription_checkout.php?user_id=' . urlencode($userId),
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
