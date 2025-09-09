<?php
// config.example.php - Configuration template for MemoWindow
// Copy this file to config.php and update with your actual API keys

// Stripe Configuration
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_stripe_publishable_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key_here');

// Printful Configuration  
define('PRINTFUL_API_KEY', 'your_printful_api_key_here');
define('PRINTFUL_API_URL', 'https://api.printful.com/');
define('PRINTFUL_STORE_ID', 'your_printful_store_id_here');

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'wavemy');
define('DB_USER', 'root');
define('DB_PASS', 'your_database_password');

// Application URLs (update with your domain)
// Dynamic BASE_URL - automatically detects current path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptDir === '/' ? '' : $scriptDir;
define('BASE_URL', $protocol . $host . $basePath);
define('SUCCESS_URL', BASE_URL . '/order_success.php');
define('CANCEL_URL', BASE_URL . '/order_cancelled.php');

// Product Configuration (using actual Printful variant IDs)
$PRINT_PRODUCTS = [
    'poster_18x24' => [
        'printful_id' => 6880, // Enhanced Matte Paper Poster 18"×24"
        'name' => '18" × 24" Memory Frame',
        'description' => 'Premium matte paper poster perfect for framing your MemoryWave',
        'price' => 2500, // Price in cents ($25.00)
        'size' => '18" × 24"',
        'material' => 'Enhanced matte paper'
    ],
    'poster_12x16' => [
        'printful_id' => 6875, // Enhanced Matte Paper Poster 12"×16"
        'name' => '12" × 16" Memory Frame',
        'description' => 'Perfect size for desk or shelf display',
        'price' => 1800, // Price in cents ($18.00)
        'size' => '12" × 16"',
        'material' => 'Enhanced matte paper'
    ],
    'framed_18x24' => [
        'printful_id' => 10749, // Enhanced Matte Paper Framed Poster (White/18″×24″)
        'name' => '18" × 24" Framed Memory',
        'description' => 'Ready-to-hang framed poster with white frame',
        'price' => 6500, // Price in cents ($65.00)
        'size' => '18" × 24"',
        'material' => 'Enhanced matte paper with white frame'
    ]
];

// Make products globally available
$GLOBALS['PRINT_PRODUCTS'] = $PRINT_PRODUCTS;

// Helper function to get product by ID
function getProduct($productId) {
    global $PRINT_PRODUCTS;
    return isset($PRINT_PRODUCTS[$productId]) ? $PRINT_PRODUCTS[$productId] : null;
}

// Helper function to format price
function formatPrice($priceInCents) {
    return '$' . number_format($priceInCents / 100, 2);
}
?>
