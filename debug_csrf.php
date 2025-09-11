<?php
/**
 * Debug CSRF Token Generation
 */

// Skip auto CORS headers
define('SKIP_AUTO_CORS', true);

echo "🔍 DEBUGGING CSRF TOKEN GENERATION\n";
echo "==================================\n\n";

echo "1. Checking session status...\n";
echo "Session status: " . session_status() . "\n";
echo "Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n\n";

echo "2. Starting session...\n";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "Session started successfully\n";
} else {
    echo "Session already active\n";
}

echo "3. Testing CSRF token generation...\n";
try {
    require_once 'secure_auth.php';
    
    $token1 = generateCSRFToken();
    echo "Token 1: " . substr($token1, 0, 20) . "...\n";
    
    $token2 = generateCSRFToken();
    echo "Token 2: " . substr($token2, 0, 20) . "...\n";
    
    if ($token1 === $token2) {
        echo "❌ Tokens are the same (this is expected for same session)\n";
    } else {
        echo "✅ Tokens are different\n";
    }
    
    echo "4. Testing CSRF token verification...\n";
    $valid = verifyCSRFToken($token1);
    echo "Token 1 valid: " . ($valid ? 'Yes' : 'No') . "\n";
    
    $invalid = verifyCSRFToken('invalid_token');
    echo "Invalid token valid: " . ($invalid ? 'Yes' : 'No') . "\n";
    
    if ($valid && !$invalid) {
        echo "✅ CSRF token generation and verification working correctly\n";
    } else {
        echo "❌ CSRF token generation or verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n5. Session data:\n";
print_r($_SESSION);
?>
