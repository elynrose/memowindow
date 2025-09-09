<?php
// config.php - Configuration for Stripe and Printful integration
require_once __DIR__ . '/PriceManager.php';

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return [];
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
    
    return $env;
}

$env = loadEnv(__DIR__ . '/.env');

// Stripe Configuration
define('STRIPE_PUBLISHABLE_KEY', $env['STRIPE_PUBLISHABLE_KEY'] ?? 'pk_test_your_stripe_publishable_key_here');
define('STRIPE_SECRET_KEY', $env['STRIPE_SECRET_KEY'] ?? 'sk_test_your_stripe_secret_key_here');
define('STRIPE_WEBHOOK_SECRET', $env['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_your_webhook_secret_here');

// Printful Configuration  
define('PRINTFUL_API_KEY', $env['PRINTFUL_API_KEY'] ?? 'your_printful_api_key_here');
define('PRINTFUL_API_URL', 'https://api.printful.com/');
define('PRINTFUL_STORE_ID', $env['PRINTFUL_STORE_ID'] ?? '12587389');

// Database Configuration
define('DB_HOST', $env['DB_HOST'] ?? '127.0.0.1');
define('DB_NAME', $env['DB_NAME'] ?? 'wavemy');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? 'password');
/*
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'elyayertey_memowindow');
define('DB_USER', 'elyayertey_memowindow');
define('DB_PASS', 'Sugarose227');
*/
// Application URLs - Dynamic based on current domain and path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptDir === '/' ? '' : $scriptDir;
define('BASE_URL', $protocol . $host . $basePath);
define('SUCCESS_URL', BASE_URL . '/order_success.php');
define('CANCEL_URL', BASE_URL . '/order_cancelled.php');

// Product Configuration - Now managed through admin interface
// Products are stored in the print_products database table

// Helper function to get product by ID from database
function getProduct($productId) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // First try to find by product_key (if it exists)
        try {
            $stmt = $pdo->prepare("SELECT * FROM print_products WHERE product_key = :product_key AND is_active = 1");
            $stmt->execute([':product_key' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If product_key column doesn't exist, try by id
            $stmt = $pdo->prepare("SELECT * FROM print_products WHERE id = :id AND is_active = 1");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($product) {
            return [
                'printful_id' => $product['printful_id'] ?? '12345', // Default fallback
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => PriceManager::fromDatabase($product['price'] ?? 2499),
                'size' => $product['size'] ?? 'Standard Size',
                'material' => $product['material'] ?? 'Premium Wood'
            ];
        }
        
        // If no product found in database, return fallback product
        return [
            'printful_id' => '12345',
            'name' => 'Memory Frame',
            'description' => 'Beautiful memory frame for your waveform',
            'price' => 2499, // $24.99 in cents
            'size' => 'Standard Size',
            'material' => 'Premium Wood'
        ];
        
    } catch (Exception $e) {
        // Return fallback product if database fails
        return [
            'printful_id' => '12345',
            'name' => 'Memory Frame',
            'description' => 'Beautiful memory frame for your waveform',
            'price' => 2499, // $24.99 in cents
            'size' => 'Standard Size',
            'material' => 'Premium Wood'
        ];
    }
}

// Helper function to format price
function formatPrice($priceInCents) {
    return '$' . number_format($priceInCents / 100, 2);
}
?>
