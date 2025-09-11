<?php
/**
 * CORS Configuration for MemoWindow
 * Provides secure CORS headers and configuration
 */

/**
 * Set secure CORS headers
 */
function setSecureCORSHeaders() {
    // Get the origin from the request
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Define allowed origins
    $allowedOrigins = [
        'http://localhost',
        'http://localhost:8080',
        'http://127.0.0.1',
        'http://127.0.0.1:8080',
        'https://memowindow.com',
        'https://www.memowindow.com'
    ];
    
    // Check if origin is allowed
    $isAllowed = false;
    foreach ($allowedOrigins as $allowedOrigin) {
        if (strpos($origin, $allowedOrigin) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    // Set CORS headers
    if ($isAllowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        // For production, be more restrictive
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Access-Control-Allow-Origin: https://memowindow.com');
        } else {
            header('Access-Control-Allow-Origin: http://localhost');
        }
    }
    
    // Set other CORS headers
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Set CORS headers for API endpoints
 */
function setAPICORSHeaders() {
    // More restrictive for API endpoints
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Only allow specific origins for API
    $allowedAPIDomains = [
        'memowindow.com',
        'localhost'
    ];
    
    $isAllowed = false;
    foreach ($allowedAPIDomains as $domain) {
        if (strpos($origin, $domain) !== false) {
            $isAllowed = true;
            break;
        }
    }
    
    if ($isAllowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        // Default to same origin
        header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    }
    
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 3600'); // 1 hour
}

/**
 * Set CORS headers for file uploads
 */
function setUploadCORSHeaders() {
    // Very restrictive for file uploads
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Only allow same origin for uploads
    $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    if (strpos($origin, $currentHost) !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: ' . $currentHost);
    }
    
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 1800'); // 30 minutes
}

/**
 * Validate CORS request
 */
function validateCORSRequest() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Check if request is from allowed origin
    $allowedOrigins = [
        'http://localhost',
        'http://127.0.0.1',
        'https://memowindow.com',
        'https://www.memowindow.com'
    ];
    
    $isValid = false;
    foreach ($allowedOrigins as $allowedOrigin) {
        if (strpos($origin, $allowedOrigin) === 0 || strpos($referer, $allowedOrigin) === 0) {
            $isValid = true;
            break;
        }
    }
    
    // Allow same-origin requests
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($origin, $currentHost) !== false) {
        $isValid = true;
    }
    
    return $isValid;
}

/**
 * Log CORS violations
 */
function logCORSViolation($origin, $referer) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'origin' => $origin,
        'referer' => $referer,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    error_log("CORS_VIOLATION: " . json_encode($logEntry));
}

// Auto-set CORS headers if this file is included and no output has been sent
if (!headers_sent() && !defined('SKIP_AUTO_CORS')) {
    setSecureCORSHeaders();
}
?>
