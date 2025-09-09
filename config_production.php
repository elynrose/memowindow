<?php
// Production Configuration for MemoWindow
// Copy this to config.php and fill in your actual values

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'memowindow');
define('DB_USER', 'memowindow_user');
define('DB_PASS', 'your_secure_password_here');

// Stripe Configuration
define('STRIPE_SECRET_KEY', 'sk_live_your_stripe_secret_key_here');
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_your_stripe_publishable_key_here');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret_here');

// Printful Configuration
define('PRINTFUL_API_KEY', 'your_printful_api_key_here');
define('PRINTFUL_STORE_ID', '12587389'); // Your MySticker Store ID

// Site Configuration
define('SITE_URL', 'https://www.memorywindow.com');
define('SITE_NAME', 'MemoWindow');

// Security
define('JWT_SECRET', 'your_jwt_secret_key_here');

// Print Products Configuration
function getProduct($variantId) {
    // This function fetches product details from the database
    // It will be used by the order system
    return [
        'variant_id' => $variantId,
        'name' => 'Custom Print',
        'price' => 15.99,
        'description' => 'High-quality custom print'
    ];
}

// Error Reporting (disable in production)
error_reporting(0);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('UTC');
?>
