<?php
/**
 * Unified Authentication System for MemoWindow
 * Secure, session-based authentication for both frontend and admin
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set error handling for JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    if (basename($_SERVER['PHP_SELF']) === 'unified_auth.php') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $message
        ]);
        exit;
    }
});

require_once 'config.php';

// Initialize session with custom directory
if (session_status() === PHP_SESSION_NONE) {
    // Use a custom session directory to avoid permission issues
    $sessionDir = __DIR__ . '/sessions';
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0755, true);
    }
    session_save_path($sessionDir);
    
    // Configure session cookie for cross-origin requests
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie (expires when browser closes)
        'path' => '/',
        'domain' => '', // Empty domain allows subdomain sharing
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => false, // Allow JavaScript access for fetch requests
        'samesite' => 'Lax' // Allow cross-site requests
    ]);
    
    session_start();
}

/**
 * Verify Firebase ID token on the server side
 * This is the secure way to authenticate users
 */
function verifyFirebaseIdToken($idToken) {
    if (!$idToken) {
        return false;
    }
    
        // For testing purposes, if the token is "test_token", return a test user
        if ($idToken === 'test_token') {
            return [
                'uid' => 'FG8w39qVEySCnzotJDYBWQ30g5J2',
                'email' => 'elyayertey@gmail.com',
                'displayName' => 'Test User',
                'emailVerified' => true,
                'photoURL' => ''
            ];
        }
    
    try {
        // Use Google's API to verify the token
        $url = 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/getAccountInfo?key=' . FIREBASE_API_KEY;
        
        $data = json_encode(['idToken' => $idToken]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $data
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['users']) && count($result['users']) > 0) {
            $user = $result['users'][0];
            return [
                'uid' => $user['localId'],
                'email' => $user['email'] ?? '',
                'displayName' => $user['displayName'] ?? '',
                'emailVerified' => $user['emailVerified'] ?? false,
                'photoURL' => $user['photoUrl'] ?? ''
            ];
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Store user in database for admin checks
 */
function storeUserInDatabase($userData) {
    try {
        global $pdo;
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE firebase_uid = ?");
        $stmt->execute([$userData['uid']]);
        
        if ($stmt->fetch()) {
            // User exists, update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE firebase_uid = ?");
            $stmt->execute([$userData['uid']]);
        } else {
            // User doesn't exist, create new record
            $stmt = $pdo->prepare("INSERT INTO users (firebase_uid, email, display_name, email_verified, photo_url, created_at, last_login) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $userData['uid'],
                $userData['email'],
                $userData['displayName'],
                $userData['emailVerified'] ? 1 : 0,
                $userData['photoURL']
            ]);
        }
        
    } catch (Exception $e) {
    }
}

/**
 * Authenticate user with Firebase token
 */
function authenticateUser($idToken = null) {
    // If no token provided, try to get from Authorization header
    if (!$idToken) {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            $idToken = substr($authHeader, 7);
        }
    }
    
    // If still no token, check if user is already authenticated in session
    if (!$idToken && isset($_SESSION['authenticated_user'])) {
        return $_SESSION['authenticated_user'];
    }
    
    // Verify the Firebase token
    if ($idToken) {
        $userData = verifyFirebaseIdToken($idToken);
        
        if ($userData) {
            // Store user data in session
            $_SESSION['authenticated_user'] = $userData;
            $_SESSION['auth_timestamp'] = time();
            $_SESSION['auth_method'] = 'firebase_token';
            
            // Also store in database for admin checks
            storeUserInDatabase($userData);
            
            return $userData;
        }
    }
    
    return false;
}

/**
 * Check if current user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['authenticated_user']) && 
           isset($_SESSION['auth_timestamp']) &&
           (time() - $_SESSION['auth_timestamp']) < 3600; // 1 hour session
}

/**
 * Get current authenticated user
 */
function getCurrentUser() {
    if (isAuthenticated()) {
        return $_SESSION['authenticated_user'];
    }
    return null;
}

/**
 * Check if current user is admin
 */
function isCurrentUserAdmin() {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute([$user['uid']]);
        $adminUser = $stmt->fetch();
        
        return $adminUser && $adminUser['is_admin'] == 1;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    $user = getCurrentUser();
    
    if (!$user) {
        // Redirect to login page
        $loginUrl = BASE_URL . '/login.php?error=login_required';
        header('Location: ' . $loginUrl);
        exit;
    }
    
    return $user;
}

/**
 * Require admin privileges - redirect if not admin
 */
function requireAdmin() {
    $user = requireAuth(); // This will redirect if not authenticated
    
    if (!isCurrentUserAdmin()) {
        // Log unauthorized admin access attempt
        
        // Redirect to login with error
        $loginUrl = BASE_URL . '/login.php?error=admin_required';
        header('Location: ' . $loginUrl);
        exit;
    }
    
    return $user;
}

/**
 * Logout user
 */
function logoutUser() {
    // Clear session data
    unset($_SESSION['authenticated_user']);
    unset($_SESSION['auth_timestamp']);
    unset($_SESSION['auth_method']);
    
    // Destroy session
    session_destroy();
    
    // Check if this is an API call
    if (basename($_SERVER['PHP_SELF']) === 'unified_auth.php') {
        // Return JSON response for API calls
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
        exit;
    } else {
        // Redirect to login for regular page requests
        header('Location: ' . BASE_URL . '/login.php?message=logged_out');
        exit;
    }
}

/**
 * API endpoint for authentication
 * This can be called from JavaScript to authenticate users
 */
function handleAuthAPI() {
    header('Content-Type: application/json');
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $idToken = $input['idToken'] ?? null;
        
        $user = authenticateUser($idToken);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user,
                'isAdmin' => isCurrentUserAdmin()
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Authentication failed'
            ]);
        }
    } elseif ($method === 'GET') {
        // Check current authentication status
        $user = getCurrentUser();
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user,
                'isAdmin' => isCurrentUserAdmin()
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Not authenticated'
            ]);
        }
    } elseif ($method === 'DELETE') {
        // Logout
        logoutUser();
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }
}

// If this file is called directly as an API endpoint
if (basename($_SERVER['PHP_SELF']) === 'unified_auth.php') {
    try {
        handleAuthAPI();
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
        ]);
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
?>
