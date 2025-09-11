<?php
// auth_check.php - Centralized authentication check for all pages
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is authenticated
function requireAuth() {
    // Check if user_id is provided in URL parameters
    $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    
    if (!$userId) {
        // Redirect to login page if no user_id provided
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    
    // Store user_id in session for this request
    $_SESSION['current_user_id'] = $userId;
    
    return $userId;
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['current_user_id'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? null;
}

// Function to check if user is admin
function requireAdmin() {
    $userId = requireAuth();
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Check if user is admin
        $stmt = $pdo->prepare("SELECT is_admin FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['is_admin']) {
            // Redirect to login page if not admin
            header('Location: ' . BASE_URL . '/login.php?error=access_denied');
            exit;
        }
        
        return $userId;
        
    } catch (PDOException $e) {
        // Redirect to login page on database error
        header('Location: ' . BASE_URL . '/login.php?error=database_error');
        exit;
    }
}

// Function to redirect to login if not authenticated
function redirectToLogin() {
    header('Location: ' . BASE_URL . '/login.php?error=login_required');
    exit;
}

// Function to show authentication error page
function showAuthError($message = 'Authentication required') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Authentication Required - MemoWindow</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .auth-container {
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 400px;
                width: 90%;
            }
            .logo {
                font-size: 2rem;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 1rem;
            }
            .error-message {
                color: #e74c3c;
                margin-bottom: 1.5rem;
                font-size: 1.1rem;
            }
            .login-btn {
                background: #667eea;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-size: 1rem;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: background 0.3s;
            }
            .login-btn:hover {
                background: #5a6fd8;
            }
        </style>
    </head>
    <body>
        <div class="auth-container">
            <div class="logo">MemoWindow</div>
            <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
            <a href="<?php echo BASE_URL; ?>/login.php" class="login-btn">Go to Login</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
