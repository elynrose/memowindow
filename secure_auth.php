<?php
/**
 * Secure Authentication System for MemoWindow
 * Provides secure authentication while maintaining backward compatibility
 */

require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verify Firebase ID token (for future implementation)
 * Currently returns the user_id for backward compatibility
 */
function verifyFirebaseToken($idToken) {
    // TODO: Implement proper Firebase token verification
    // For now, return the user_id for backward compatibility
    return $_GET['user_id'] ?? $_POST['user_id'] ?? null;
}

/**
 * Secure authentication function with backward compatibility
 * Checks both new token-based auth and legacy URL-based auth
 */
function requireSecureAuth() {
    // Check for Authorization header (new secure method)
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $userId = verifyFirebaseToken($token);
        
        if ($userId) {
            $_SESSION['current_user_id'] = $userId;
            $_SESSION['auth_method'] = 'token';
            return $userId;
        }
    }
    
    // Fallback to legacy URL-based auth for backward compatibility
    $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    
    if (!$userId) {
        // Redirect to login page if no user_id provided
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    
    // Validate user_id format (basic security check)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $userId)) {
        header('Location: ' . BASE_URL . '/login.php?error=invalid_user_id');
        exit;
    }
    
    // Store user_id in session for this request
    $_SESSION['current_user_id'] = $userId;
    $_SESSION['auth_method'] = 'legacy';
    
    return $userId;
}

/**
 * Secure admin authentication with enhanced validation
 */
function requireSecureAdmin() {
    $userId = requireSecureAuth();
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Check if user is admin with prepared statement
        $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['is_admin']) {
            // Log unauthorized admin access attempt
            error_log("Unauthorized admin access attempt by user: $userId");
            header('Location: ' . BASE_URL . '/login.php?error=access_denied');
            exit;
        }
        
        return $userId;
        
    } catch (PDOException $e) {
        // Log database error but don't expose details
        error_log("Database error in admin auth: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/login.php?error=database_error');
        exit;
    }
}

/**
 * Get current user ID from session
 * Note: This function is already defined in auth_check.php
 */

/**
 * Check if current user is admin
 */
function isCurrentUserAdmin() {
    $userId = getCurrentUserId();
    if (!$userId) return false;
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        return $user && $user['is_admin'];
        
    } catch (PDOException $e) {
        error_log("Database error checking admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'url':
            return filter_var(trim($data), FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate input data
 */
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        if (!isset($data[$field]) && $rule['required']) {
            $errors[] = "Missing required field: $field";
            continue;
        }
        
        if (isset($data[$field])) {
            $value = $data[$field];
            
            // Check required
            if ($rule['required'] && empty($value)) {
                $errors[] = "Field is required: $field";
                continue;
            }
            
            // Check type
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Invalid email format: $field";
                        }
                        break;
                    case 'int':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[] = "Invalid integer: $field";
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[] = "Invalid URL: $field";
                        }
                        break;
                }
            }
            
            // Check length
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[] = "Field too long: $field (max {$rule['max_length']} characters)";
            }
            
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[] = "Field too short: $field (min {$rule['min_length']} characters)";
            }
        }
    }
    
    return $errors;
}

/**
 * Add security headers
 */
function addSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Only add HSTS in production with HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // CSP header (adjust as needed for your app)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:;");
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => getCurrentUserId(),
        'details' => $details
    ];
    
    error_log("SECURITY: " . json_encode($logEntry));
}

// Add security headers by default
addSecurityHeaders();
?>
