<?php
// test_unified_auth.php - Test page for unified authentication system
require_once 'unified_auth.php';

// Test authentication
$currentUser = getCurrentUser();
$isAdmin = isCurrentUserAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Auth Test - MemoWindow</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔐 Unified Authentication Test</h1>
    
    <div class="status <?php echo $currentUser ? 'success' : 'error'; ?>">
        <strong>Authentication Status:</strong> 
        <?php echo $currentUser ? '✅ Authenticated' : '❌ Not Authenticated'; ?>
    </div>
    
    <?php if ($currentUser): ?>
        <div class="status info">
            <strong>User Information:</strong>
            <pre><?php echo json_encode($currentUser, JSON_PRETTY_PRINT); ?></pre>
        </div>
        
        <div class="status <?php echo $isAdmin ? 'success' : 'info'; ?>">
            <strong>Admin Status:</strong> 
            <?php echo $isAdmin ? '✅ Admin User' : '👤 Regular User'; ?>
        </div>
        
        <div class="status info">
            <strong>Session Data:</strong>
            <pre><?php echo json_encode($_SESSION, JSON_PRETTY_PRINT); ?></pre>
        </div>
        
        <h2>Test Links</h2>
        <ul>
            <li><a href="orders.php">📦 Orders Page</a></li>
            <?php if ($isAdmin): ?>
                <li><a href="admin.php">🔧 Admin Dashboard</a></li>
            <?php endif; ?>
            <li><a href="unified_auth.php" onclick="logout()">🚪 Logout</a></li>
        </ul>
        
    <?php else: ?>
        <div class="status error">
            <strong>Not Authenticated</strong>
            <p>Please <a href="login.php">login</a> to test the authentication system.</p>
        </div>
    <?php endif; ?>
    
    <h2>API Test</h2>
    <button onclick="testAuthAPI()">Test Auth API</button>
    <div id="apiResult"></div>
    
    <script>
        async function testAuthAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = 'Testing...';
            
            try {
                const response = await fetch('unified_auth.php', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="status ${data.success ? 'success' : 'error'}">
                        <strong>API Response:</strong>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="status error">
                        <strong>API Error:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        async function logout() {
            try {
                await fetch('unified_auth.php', {
                    method: 'DELETE',
                    credentials: 'include'
                });
                window.location.href = 'login.php';
            } catch (error) {
                console.error('Logout error:', error);
            }
        }
    </script>
</body>
</html>
