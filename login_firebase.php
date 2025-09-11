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
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        
        .btn-google {
            background: #4285f4;
            color: white;
        }
        
        .btn-google:hover {
            background: #357ae8;
        }
        
        .btn-email {
            background: #667eea;
            color: white;
        }
        
        .btn-email:hover {
            background: #5a6fd8;
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .success {
            background: #efe;
            color: #363;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #cfc;
        }
        
        .loading {
            text-align: center;
            color: #666;
            margin: 1rem 0;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .user-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .user-info p {
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>MemoWindow</h1>
            <p>Firebase Authentication</p>
        </div>
        
        <div id="error-message" class="error" style="display: none;"></div>
        <div id="success-message" class="success" style="display: none;"></div>
        <div id="loading" class="loading" style="display: none;">Loading...</div>
        
        <div id="login-section">
            <button id="google-login" class="btn btn-google">
                Sign in with Google
            </button>
            
            <button id="email-login" class="btn btn-email">
                Sign in with Email
            </button>
        </div>
        
        <div id="user-section" style="display: none;">
            <div class="user-info">
                <h3>Welcome!</h3>
                <p><strong>Name:</strong> <span id="user-name"></span></p>
                <p><strong>Email:</strong> <span id="user-email"></span></p>
                <p><strong>Admin:</strong> <span id="user-admin"></span></p>
            </div>
            
            <button id="logout-btn" class="logout-btn">Logout</button>
        </div>
        
        <div class="footer">
            <p>MemoWindow Admin Panel</p>
            <p>Firebase-powered authentication</p>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, signInWithPopup, GoogleAuthProvider, signInWithEmailAndPassword, signOut, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
        
        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyBvQvQvQvQvQvQvQvQvQvQvQvQvQvQvQvQ", // Replace with your actual API key
            authDomain: "memowindow-8b8b8.firebaseapp.com",
            projectId: "memowindow-8b8b8",
            storageBucket: "memowindow-8b8b8.appspot.com",
            messagingSenderId: "123456789012",
            appId: "1:123456789012:web:abcdefghijklmnop"
        };
        
        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const googleProvider = new GoogleAuthProvider();
        
        // DOM elements
        const googleLoginBtn = document.getElementById('google-login');
        const emailLoginBtn = document.getElementById('email-login');
        const logoutBtn = document.getElementById('logout-btn');
        const loginSection = document.getElementById('login-section');
        const userSection = document.getElementById('user-section');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');
        const loading = document.getElementById('loading');
        
        // Show error message
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            successMessage.style.display = 'none';
        }
        
        // Show success message
        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';
        }
        
        // Hide messages
        function hideMessages() {
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
        }
        
        // Show loading
        function showLoading() {
            loading.style.display = 'block';
            googleLoginBtn.disabled = true;
            emailLoginBtn.disabled = true;
        }
        
        // Hide loading
        function hideLoading() {
            loading.style.display = 'none';
            googleLoginBtn.disabled = false;
            emailLoginBtn.disabled = false;
        }
        
        // Send Firebase token to server
        async function sendTokenToServer(idToken) {
            try {
                const response = await fetch('/firebase_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ idToken: idToken })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Login successful! Redirecting...');
                    
                    // Redirect to appropriate page
                    setTimeout(() => {
                        window.location.href = result.redirect_url;
                    }, 1000);
                } else {
                    showError(result.error || 'Login failed');
                }
            } catch (error) {
                showError('Network error: ' + error.message);
            }
        }
        
        // Google sign in
        googleLoginBtn.addEventListener('click', async () => {
            try {
                hideMessages();
                showLoading();
                
                const result = await signInWithPopup(auth, googleProvider);
                const idToken = await result.user.getIdToken();
                
                await sendTokenToServer(idToken);
            } catch (error) {
                hideLoading();
                showError('Google sign-in failed: ' + error.message);
            }
        });
        
        // Email sign in (simplified - you can enhance this)
        emailLoginBtn.addEventListener('click', async () => {
            const email = prompt('Enter your email:');
            const password = prompt('Enter your password:');
            
            if (!email || !password) {
                showError('Email and password are required');
                return;
            }
            
            try {
                hideMessages();
                showLoading();
                
                const result = await signInWithEmailAndPassword(auth, email, password);
                const idToken = await result.user.getIdToken();
                
                await sendTokenToServer(idToken);
            } catch (error) {
                hideLoading();
                showError('Email sign-in failed: ' + error.message);
            }
        });
        
        // Logout
        logoutBtn.addEventListener('click', async () => {
            try {
                await signOut(auth);
                window.location.href = '/login_firebase.php?logout=1';
            } catch (error) {
                showError('Logout failed: ' + error.message);
            }
        });
        
        // Check authentication state
        onAuthStateChanged(auth, (user) => {
            if (user) {
                // User is signed in
                document.getElementById('user-name').textContent = user.displayName || 'Unknown';
                document.getElementById('user-email').textContent = user.email || 'Unknown';
                document.getElementById('user-admin').textContent = 'Checking...';
                
                loginSection.style.display = 'none';
                userSection.style.display = 'block';
                
                // Get admin status
                user.getIdToken().then(idToken => {
                    sendTokenToServer(idToken);
                });
            } else {
                // User is signed out
                loginSection.style.display = 'block';
                userSection.style.display = 'none';
                hideMessages();
            }
        });
        
        // Handle logout parameter
        if (window.location.search.includes('logout=1')) {
            showSuccess('Logged out successfully');
        }
    </script>
</body>
</html>
