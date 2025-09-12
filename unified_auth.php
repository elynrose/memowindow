<?php
/**
 * Unified Authentication System for MemoWindow
 * Secure, session-based authentication for both frontend and admin
 */

require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verify Firebase ID token on the server side
 * This is the secure way to authenticate users
 */
function verifyFirebaseIdToken($idToken) {
    if (empty($idToken)) {
        return null;
    }
    
    try {
        // Firebase Admin SDK would be used here in production
        // For now, we'll implement a basic verification
        $url = 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/getAccountInfo?key=' . FIREBASE_API_KEY;
        
        $data = json_encode(['idToken' => $idToken]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $data
            ]
        ]);
        
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            return null;
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['users']) && count($response['users']) > 0) {
            $user = $response['users'][0];
            return [
                'uid' => $user['localId'],
                'email' => $user['email'] ?? '',
                'email_verified' => $user['emailVerified'] ?? false,
                'display_name' => $user['displayName'] ?? '',
                'photo_url' => $user['photoUrl'] ?? ''
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log('Firebase token verification error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Authenticate user and store in session
 * This is the main authentication function
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
    
    return null;
}

/**
 * Store user data in database for admin checks
 */
function storeUserInDatabase($userData) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Insert or update user data
        $stmt = $pdo->prepare("
            INSERT INTO users (firebase_uid, email, name, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            email = VALUES(email), 
            name = VALUES(name), 
            updated_at = NOW()
        ");
        
        $stmt->execute([
            $userData['uid'],
            $userData['email'],
            $userData['display_name']
        ]);
        
    } catch (PDOException $e) {
        error_log('Database error storing user: ' . $e->getMessage());
    }
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
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute([$user['uid']]);
        $result = $stmt->fetch();
        
        return $result && $result['is_admin'];
        
    } catch (PDOException $e) {
        error_log('Database error checking admin status: ' . $e->getMessage());
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
        error_log("Unauthorized admin access attempt by user: " . $user['uid']);
        
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
    
    // Redirect to login
    header('Location: ' . BASE_URL . '/login.php?message=logged_out');
    exit;
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
                'error' => 'Invalid token'
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
    handleAuthAPI();
}
?>
