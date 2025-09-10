<?php
// Test Stripe webhook with proper signature
require_once 'config.php';
require_once 'vendor/autoload.php';

echo "🧪 Testing Stripe Webhook with Proper Signature\n";
echo "==============================================\n\n";

// Set up Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Create a test webhook payload (checkout.session.completed event)
$testPayload = [
    'id' => 'evt_test_webhook',
    'object' => 'event',
    'api_version' => '2020-08-27',
    'created' => time(),
    'data' => [
        'object' => [
            'id' => 'cs_test_session',
            'object' => 'checkout.session',
            'amount_total' => 2650, // $26.50
            'customer_details' => [
                'email' => 'test@example.com',
                'name' => 'Test Customer'
            ],
            'shipping_details' => [
                'address' => [
                    'line1' => '123 Test St',
                    'line2' => '',
                    'city' => 'Test City',
                    'state' => 'CA',
                    'country' => 'US',
                    'postal_code' => '12345'
                ]
            ],
            'metadata' => [
                'memory_id' => '1',
                'product_id' => '8x10',
                'user_id' => 'test_user_123',
                'image_url' => 'https://example.com/test-image.png',
                'printful_product_id' => '12345'
            ]
        ]
    ],
    'livemode' => false,
    'pending_webhooks' => 1,
    'request' => [
        'id' => 'req_test_webhook',
        'idempotency_key' => null
    ],
    'type' => 'checkout.session.completed'
];

// Convert to JSON
$payload = json_encode($testPayload);
$timestamp = time();

// Create signature
$signedPayload = $timestamp . '.' . $payload;
$signature = hash_hmac('sha256', $signedPayload, STRIPE_WEBHOOK_SECRET);

// Set up cURL request
$webhook_url = 'https://localhost/memowindow/stripe_webhook.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Stripe-Signature: t=' . $timestamp . ',v1=' . $signature
]);

echo "📤 Sending test webhook...\n";
echo "URL: $webhook_url\n";
echo "Payload size: " . strlen($payload) . " bytes\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "📥 Response:\n";
echo "HTTP Code: $http_code\n";

if ($curl_error) {
    echo "❌ cURL Error: $curl_error\n";
} else {
    echo "Response Body: $response\n";
    
    if ($http_code == 200) {
        echo "✅ Webhook processed successfully!\n";
    } else {
        echo "❌ Webhook failed with HTTP $http_code\n";
    }
}

echo "\n==============================================\n";
echo "Test complete\n";
?>
