<?php
/**
 * Session-based Login Page
 * Handles user login and creates secure sessions
 */

require_once 'config.php';
require_once 'secure_auth.php';

// Skip auto CORS headers
define('SKIP_AUTO_CORS', true);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firebaseUID = $_POST['firebase_uid'] ?? '';
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    
    if (!empty($firebaseUID)) {
        if (loginUser($firebaseUID, $email, $name)) {
            // Login successful - redirect to appropriate page
            if (isAdmin()) {
                header('Location: ' . BASE_URL . '/admin.php');
            } else {
                header('Location: ' . BASE_URL . '/orders.php');
            }
            exit;
        } else {
            $error = 'Login failed. Please check your credentials.';
        }
    } else {
        $error = 'Firebase UID is required.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: ' . BASE_URL . '/login_session.php');
    exit;
}

// Check if user is already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . '/admin.php');
    } else {
        header('Location: ' . BASE_URL . '/orders.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MemoWindow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .logo p {
            color: #666;
            margin-top: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .info {
            background: #e7f3ff;
            color: #0066cc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #b3d9ff;
        }
        
        .quick-login {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e1e5e9;
        }
        
        .quick-login h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .quick-login a {
            display: block;
            padding: 8px 12px;
            background: #f8f9fa;
            color: #667eea;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s;
        }
        
        .quick-login a:hover {
            background: #e9ecef;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>MemoWindow</h1>
            <p>Secure Admin Login</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info">
            <strong>Session-based Authentication</strong><br>
            Your login will be remembered for 30 minutes.
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="firebase_uid">Firebase UID</label>
                <input type="text" id="firebase_uid" name="firebase_uid" required 
                       placeholder="Enter your Firebase UID" 
                       value="<?php echo htmlspecialchars($_GET['user_id'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email (Optional)</label>
                <input type="email" id="email" name="email" 
                       placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label for="name">Name (Optional)</label>
                <input type="text" id="name" name="name" 
                       placeholder="Your Name">
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="quick-login">
            <h3>Quick Admin Login</h3>
            <a href="?firebase_uid=FG8w39qVEySCnzotJDYBWQ30g5J2&email=elyayertey@gmail.com&name=Admin">Login as Admin</a>
            <a href="?firebase_uid=test_user&email=test@example.com&name=Test User">Login as Test User</a>
        </div>
        
        <div class="footer">
            <p>MemoWindow Admin Panel</p>
            <p>Session-based authentication for enhanced security</p>
        </div>
    </div>
    
    <script>
        // Auto-fill form if parameters are provided
        const urlParams = new URLSearchParams(window.location.search);
        const firebaseUID = urlParams.get('firebase_uid');
        const email = urlParams.get('email');
        const name = urlParams.get('name');
        
        if (firebaseUID) {
            document.getElementById('firebase_uid').value = firebaseUID;
        }
        if (email) {
            document.getElementById('email').value = email;
        }
        if (name) {
            document.getElementById('name').value = name;
        }
        
        // Auto-submit if all parameters are provided
        if (firebaseUID && email && name) {
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
