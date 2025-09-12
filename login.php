<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <title>MemoWindow - Sign In</title>
  <meta name="description" content="Sign in to MemoWindow to create beautiful waveform art from your voice recordings">
  <meta name="theme-color" content="#667eea">
  
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  
  <style>
    /* Modern Clean Design System */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      line-height: 1.6;
      color: #333;
      background: #f8fafc;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .login-container {
      background: white;
      border-radius: 16px;
      padding: 3rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(0, 0, 0, 0.05);
      max-width: 400px;
      width: 100%;
      margin: 1rem;
    }
    
    .logo {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .logo img {
      height: 60px;
      width: auto;
    }
    
    .login-title {
      font-size: 1.75rem;
      font-weight: 600;
      color: #1f2937;
      text-align: center;
      margin-bottom: 0.5rem;
    }
    
    .login-subtitle {
      color: #6b7280;
      text-align: center;
      margin-bottom: 2rem;
      line-height: 1.5;
    }
    
    .auth-button {
      width: 100%;
      padding: 1rem;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }
    
    .btn-google {
      background: #4285f4;
      color: white;
    }
    
    .btn-google:hover {
      background: #3367d6;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
    }
    
    .btn-google:disabled {
      background: #9ca3af;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .divider {
      text-align: center;
      margin: 1.5rem 0;
      position: relative;
    }
    
    .divider::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: #e5e7eb;
    }
    
    .divider span {
      background: white;
      padding: 0 1rem;
      color: #6b7280;
      font-size: 0.875rem;
    }
    
    .form-group {
      margin-bottom: 1rem;
    }
    
    .form-input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s, box-shadow 0.3s;
      background: white;
      color: #000;
    }
    
    .form-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-input::placeholder {
      color: #9ca3af;
    }
    
    .btn-email {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }
    
    .btn-email:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .btn-email:disabled {
      background: #9ca3af;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .btn-secondary {
      background: #6b7280;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #4b5563;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(107, 114, 128, 0.3);
    }
    
    .btn-secondary:disabled {
      background: #9ca3af;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .back-link {
      text-align: center;
      margin-top: 2rem;
    }
    
    .back-link a {
      color: #667eea;
      text-decoration: none;
      font-size: 0.875rem;
      transition: color 0.3s ease;
    }
    
    .back-link a:hover {
      color: #5a67d8;
    }
    
    .hidden {
      display: none !important;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Responsive */
    @media (max-width: 480px) {
      .login-container {
        padding: 2rem;
        margin: 0.5rem;
      }
      
      .login-title {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  
  <div class="login-container">
    <div class="logo">
      <img src="images/logo.png" alt="MemoWindow">
    </div>
    
    <h1 class="login-title">Welcome to MemoWindow</h1>
    <p class="login-subtitle">Transform precious voice recordings into beautiful waveform art</p>
    
    <!-- Google Sign In -->
    <button id="btnLogin" class="auth-button btn-google">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
      Sign in with Google
    </button>
    
    <div class="divider">
      <span>or</span>
    </div>
    
    <!-- Email Sign In -->
    <div class="form-group">
      <input id="emailInput" type="email" class="form-input" placeholder="Email address" required>
                </div>
    <div class="form-group">
      <input id="passwordInput" type="password" class="form-input" placeholder="Password" required>
              </div>
    
    <button id="btnEmailLogin" class="auth-button btn-email">
      Sign In
    </button>
    
    <button id="btnEmailRegister" class="auth-button btn-secondary">
      Create Account
    </button>
    
    <div class="back-link">
      <a href="index.php">← Back to Home</a>
      </div>
    </div>

  <!-- Firebase SDK -->
  <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
  <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Navigation Script -->
  <script type="module" src="includes/navigation.js"></script>
  
  <script type="module">
    // Initialize unified authentication
    import unifiedAuth from './src/unified-auth.js';
    import { initNavigation } from './includes/navigation.js';
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', () => {
      
      // Initialize navigation
      initNavigation();
      
      // Debug: Check if button exists
      const btnLogin = document.getElementById('btnLogin');
      
      // Set up Google Sign-In button
      if (btnLogin) {
        btnLogin.addEventListener('click', async () => {
          try {
            
            // Show loading state
            const originalText = btnLogin.innerHTML;
            btnLogin.innerHTML = '<div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px;"></div>Signing in...';
            btnLogin.disabled = true;
            
            await unifiedAuth.signInWithGoogle();
            // Redirect will be handled by auth state listener
          } catch (error) {
            
            // Restore button state
            btnLogin.innerHTML = originalText;
            btnLogin.disabled = false;
            
            // Handle specific Firebase errors with user-friendly messages
            if (error.code === 'auth/popup-closed-by-user') {
              // User closed the popup - don't show an error, this is normal behavior
              return;
            } else if (error.code === 'auth/popup-blocked') {
              alert('Please allow popups for this site and try again.');
            } else if (error.code === 'auth/network-request-failed') {
              alert('Network error. Please check your internet connection and try again.');
            } else if (error.code === 'auth/too-many-requests') {
              alert('Too many failed attempts. Please try again later.');
            } else {
              alert('Sign-in failed. Please try again.');
            }
          }
        });
      }
      
      // Set up email/password login
      const btnEmailLogin = document.getElementById('btnEmailLogin');
      const btnEmailRegister = document.getElementById('btnEmailRegister');
      const emailInput = document.getElementById('emailInput');
      const passwordInput = document.getElementById('passwordInput');
      
      if (btnEmailLogin) {
        btnEmailLogin.addEventListener('click', async () => {
          const email = emailInput.value.trim();
          const password = passwordInput.value;
          
          if (!email || !password) {
            alert('Please enter both email and password');
            return;
          }
          
          try {
            await unifiedAuth.signInWithEmail(email, password);
            // Redirect will be handled by auth state listener
          } catch (error) {
            alert('Sign-in failed: ' + error.message);
          }
        });
      }
      
      if (btnEmailRegister) {
        btnEmailRegister.addEventListener('click', async () => {
          const email = emailInput.value.trim();
          const password = passwordInput.value;
          
          if (!email || !password) {
            alert('Please enter both email and password');
            return;
          }
          
          if (password.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
          }
          
          try {
            await unifiedAuth.createAccount(email, password);
            // Redirect will be handled by auth state listener
          } catch (error) {
            alert('Account creation failed: ' + error.message);
          }
        });
      }
      
      // Set up authentication state listener
      unifiedAuth.addAuthListener((user, isAdmin) => {
        if (user) {
          window.location.href = 'app.php';
        }
      });
    });
</script>
</body>
</html>
