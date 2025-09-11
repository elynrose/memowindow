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
 * Verify Firebase ID token
 * Validates the Firebase token and returns user information
 */
function verifyFirebaseToken($idToken) {
    if (empty($idToken)) {
        return null;
    }
    
    // Firebase project configuration
    $firebaseProjectId = 'memowindow-8b8b8'; // Replace with your actual project ID
    
    // Verify token with Firebase Admin SDK
    $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=" . FIREBASE_API_KEY;
    
    $data = [
        'idToken' => $idToken
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return null;
    }
    
    $response = json_decode($result, true);
    
    if (isset($response['users']) && count($response['users']) > 0) {
        $user = $response['users'][0];
        return [
            'uid' => $user['localId'],
            'email' => $user['email'] ?? '',
            'name' => $user['displayName'] ?? '',
            'email_verified' => $user['emailVerified'] ?? false
        ];
    }
    
    return null;
}

/**
 * Secure authentication function with session-based auth
 * Checks session first, then falls back to URL parameters for backward compatibility
 */
function requireSecureAuth() {
    // Check if user is already authenticated in session
    if (isset($_SESSION['current_user_id']) && !empty($_SESSION['current_user_id'])) {
        return $_SESSION['current_user_id'];
    }
    
    // Check for Authorization header (new secure method)
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $userInfo = verifyFirebaseToken($token);
        
        if ($userInfo) {
            $_SESSION['current_user_id'] = $userInfo['uid'];
            $_SESSION['user_email'] = $userInfo['email'];
            $_SESSION['user_name'] = $userInfo['name'];
            $_SESSION['auth_method'] = 'firebase_token';
            $_SESSION['last_activity'] = time();
            
            // Check if user is admin
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                
                $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
                $stmt->execute([$userInfo['uid']]);
                $user = $stmt->fetch();
                
                $_SESSION['is_admin'] = $user && $user['is_admin'];
            } catch (PDOException $e) {
                $_SESSION['is_admin'] = false;
            }
            
            return $userInfo['uid'];
        }
    }
    
    // Fallback to legacy URL-based auth for backward compatibility
    $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    
    if ($userId) {
        // Validate user_id format (basic security check)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $userId)) {
            header('Location: ' . BASE_URL . '/login.php?error=invalid_user_id');
            exit;
        }
        
        // Store user_id in session for future requests
        $_SESSION['current_user_id'] = $userId;
        $_SESSION['auth_method'] = 'legacy';
        $_SESSION['last_activity'] = time();
        
        return $userId;
    }
    
    // No authentication found - redirect to login
    header('Location: ' . BASE_URL . '/login.php?error=login_required');
    exit;
}

/**
 * Secure admin authentication with enhanced validation
 */
function requireSecureAdmin() {
    $userId = requireSecureAuth();
    
    // Check if admin status is cached in session
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return $userId;
    }
    
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
        
        // Cache admin status in session
        $_SESSION['is_admin'] = true;
        $_SESSION['last_activity'] = time();
        
        return $userId;
        
    } catch (PDOException $e) {
        // Log database error but don't expose details
        error_log("Database error in admin auth: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/login.php?error=database_error');
        exit;
    }
}

/**
 * Login user and create session
 */
function loginUser($firebaseUID, $email = null, $name = null) {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validate Firebase UID format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $firebaseUID)) {
        return false;
    }
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Check if user exists in admin_users table
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute([$firebaseUID]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // User exists - set session data
            $_SESSION['current_user_id'] = $firebaseUID;
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['auth_method'] = 'session';
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE firebase_uid = ?");
            $updateStmt->execute([$firebaseUID]);
            
            return true;
        } else {
            // User doesn't exist - create them
            $insertStmt = $pdo->prepare("
                INSERT INTO admin_users (firebase_uid, email, name, is_admin, created_at) 
                VALUES (?, ?, ?, 0, NOW())
            ");
            $insertStmt->execute([$firebaseUID, $email ?: 'user@example.com', $name ?: 'User']);
            
            // Set session data for new user
            $_SESSION['current_user_id'] = $firebaseUID;
            $_SESSION['user_email'] = $email ?: 'user@example.com';
            $_SESSION['user_name'] = $name ?: 'User';
            $_SESSION['is_admin'] = false;
            $_SESSION['auth_method'] = 'session';
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();
            
            return true;
        }
        
    } catch (PDOException $e) {
        error_log("Database error in login: " . $e->getMessage());
        return false;
    }
}

/**
 * Logout user and destroy session
 */
function logoutUser() {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['current_user_id']) && !empty($_SESSION['current_user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Get current user info from session
 */
function getCurrentUser() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['current_user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'is_admin' => $_SESSION['is_admin'] ?? false,
        'login_time' => $_SESSION['login_time'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null
    ];
}

/**
 * Check session timeout (30 minutes)
 */
function checkSessionTimeout() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $timeout = 30 * 60; // 30 minutes
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        logoutUser();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
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
