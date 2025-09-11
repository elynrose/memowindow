<?php
/**
 * Firebase Login API Endpoint
 * Handles Firebase authentication and creates secure sessions
 */

header('Content-Type: application/json');
require_once 'config.php';
require_once 'secure_auth.php';

// Skip auto CORS headers
define('SKIP_AUTO_CORS', true);

try {
    // Get the Firebase ID token from the request
    $input = json_decode(file_get_contents('php://input'), true);
    $idToken = $input['idToken'] ?? $_POST['idToken'] ?? $_GET['idToken'] ?? null;
    
    if (empty($idToken)) {
        throw new Exception('Firebase ID token is required');
    }
    
    // Verify the Firebase token
    $userInfo = verifyFirebaseToken($idToken);
    
    if (!$userInfo) {
        throw new Exception('Invalid Firebase token');
    }
    
    // Start session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set session data
    $_SESSION['current_user_id'] = $userInfo['uid'];
    $_SESSION['user_email'] = $userInfo['email'];
    $_SESSION['user_name'] = $userInfo['name'];
    $_SESSION['auth_method'] = 'firebase';
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();
    
    // Check if user exists in admin_users table and get admin status
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute([$userInfo['uid']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // User exists - update last login
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            
            $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE firebase_uid = ?");
            $updateStmt->execute([$userInfo['uid']]);
        } else {
            // User doesn't exist - create them
            $insertStmt = $pdo->prepare("
                INSERT INTO admin_users (firebase_uid, email, name, is_admin, created_at) 
                VALUES (?, ?, ?, 0, NOW())
            ");
            $insertStmt->execute([$userInfo['uid'], $userInfo['email'], $userInfo['name']]);
            
            $_SESSION['is_admin'] = false;
        }
        
    } catch (PDOException $e) {
        error_log("Database error in Firebase login: " . $e->getMessage());
        $_SESSION['is_admin'] = false;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'user' => [
            'uid' => $userInfo['uid'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'],
            'is_admin' => $_SESSION['is_admin'],
            'email_verified' => $userInfo['email_verified']
        ],
        'session_id' => session_id(),
        'redirect_url' => $_SESSION['is_admin'] ? '/admin.php' : '/orders.php'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
