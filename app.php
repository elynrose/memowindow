<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Memory - MemoWindow</title>
    
    <!-- Cache busting -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }
        
        /* Modern Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1000;
            padding: 1rem 0;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.5rem;
            color: #667eea;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #667eea;
        }
        
        .header-link {
            color: #000;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .header-link:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
        }
        
        /* Main Content */
        .wrap {
            padding: 8rem 1rem!important;
            max-width: 1200px;
            margin: 0 auto;
            margin-top: 50px;
        }
        
        /* Utility Classes */
        .hidden {
            display: none !important;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Card System */
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card h2 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        /* Button System */
        button {
            min-height: 44px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .btn-full {
            width: 100%;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Mobile hamburger menu */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            gap: 4px;
            text-decoration: none;
            color: #333;
        }

        .mobile-menu-toggle.hidden {
            display: none !important;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: #333;
            transition: all 0.3s ease;
            border-radius: 2px;
            display: block;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }

        /* Mobile menu styles */
        #mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 280px;
            height: 100vh;
            background: white;
            z-index: 1001;
            transition: left 0.3s ease;
            overflow-y: auto;
            padding: 2rem 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        #mobile-menu.mobile-open {
            left: 0;
        }

        #mobile-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        #mobile-menu li {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        #mobile-menu a,
        #mobile-menu span {
            display: block;
            padding: 1rem 2rem;
            color: #333;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        #mobile-menu a:hover,
        #mobile-menu span:hover {
            background-color: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        #mobile-menu ul ul {
            background-color: rgba(102, 126, 234, 0.05);
        }

        #mobile-menu ul ul a {
            padding-left: 3rem;
            font-size: 1rem;
        }

        /* Page overlay when menu is open */
        body.menu-open::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        /* Responsive Design */
        @media (max-width: 767px) {
            .header {
                padding: 0.75rem 0;
            }
            
            .nav {
                padding: 0 0.75rem;
                position: relative;
            }
            
            /* Show hamburger menu on mobile */
            .mobile-menu-toggle {
                display: flex !important;
                order: -1; /* Move to the left */
            }
            
            /* Center the logo on mobile */
            .logo {
                order: 0;
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }
            
            .wrap {
                padding: 2rem 0.75rem;
            }
            
            .user-info {
                display: none !important;
            }
            
            .header-link {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
            }
        }
        
        /* Page-specific styles will be injected here */
        
        /* App-specific styles */
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f9fafb;
        }
        
        .file-upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .file-upload-area.has-files {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .record-button {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .record-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }
        
        .record-button:active {
            transform: translateY(0);
        }
        
        .record-button.recording {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .waveform-list {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .waveform-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f9fafb;
        }
        
        .waveform-item:last-child {
            margin-bottom: 0;
        }
        
        .waveform-info {
            flex: 1;
        }
        
        .waveform-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .waveform-date {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .waveform-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-link {
            color: #374151;
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .action-link:hover {
            background: #e5e7eb;
        }
        
        .action-link.delete {
            color: #dc2626;
        }
        
        .action-link.delete:hover {
            background: #fef2f2;
        }
        
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="wrap">
        
        <!-- Memory Title Card -->
        <div class="card">
            <h2>Memory Title</h2>
            <div class="form-group">
                <input id="titleInput" type="text" class="form-input" placeholder="Memory title (e.g., 'Mom's Laughter', 'Dad's Bedtime Story')" required>
            </div>
        </div>

        <!-- Upload Audio Files Card -->
        <div class="card">
            <h2>Upload Audio Files</h2>
            <div class="file-upload-area" id="fileUploadArea">
                <div style="margin-bottom: 1rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #6b7280; margin: 0 auto;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7,10 12,15 17,10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                </div>
                <p style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Drop audio files here or click to browse</p>
                <p style="color: #6b7280; font-size: 0.875rem;">Supports MP3, WAV, M4A, and other audio formats</p>
                <input type="file" id="fileInput" multiple accept="audio/*" style="display: none;">
            </div>
            
            <!-- Audio Limit Info -->
            <div id="audioLimitInfo" style="margin-bottom: 1rem; padding: 12px; background: #f3f4f6; border-radius: 8px; display: block;">
                <p style="margin: 0; color: #374151; font-size: 14px;">
                    <span id="packageName">Your Plan</span> allows up to <span id="maxLength">100</span> seconds
                </p>
            </div>
            
            <!-- Countdown Timer -->
            <div id="countdownTimer" style="display: none; margin-bottom: 1rem;">
                <div style="font-size: 24px; font-weight: bold; color: #ef4444; margin-bottom: 8px;">
                    <span id="timeRemaining">00:00</span>
                </div>
                <div style="width: 200px; height: 4px; background: #e5e7eb; border-radius: 2px; margin: 0 auto;">
                    <div id="progressBar" style="height: 100%; background: linear-gradient(90deg, #22c55e, #ef4444); border-radius: 2px; width: 100%; transition: width 0.1s ease;"></div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <p style="color: #6b7280; margin-bottom: 1rem;">Or record your voice</p>
                <button id="btnRecord" type="button" class="record-button" style="display: flex; align-items: center; justify-content: center;">
                    <svg id="recordIcon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                </button>
            </div>
            <div style="margin-top: 1.5rem; text-align: center;">
                <button id="btnCreate" class="btn btn-primary btn-full" disabled>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    Create Memory
                </button>
            </div>
        </div>

        <!-- Hidden Memory Preview -->
        <div id="memoryPreview" class="card hidden" style="display: none;">
            <h2>Memory Preview</h2>
            <div style="text-align: center;">
                <canvas id="previewCanvas" width="400" height="200" style="border: 1px solid #e5e7eb; border-radius: 8px; max-width: 100%;"></canvas>
                <p id="previewStatus" style="margin-top: 1rem; color: #6b7280;"></p>
            </div>
        </div>

        <!-- Your MemoWindows Section -->
        <div class="waveform-list">
            <h2>Your MemoWindows</h2>
            <div id="waveformList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Loading your memories...
                </div>
            </div>
        </div>
        
    </div>

    <!-- Firebase SDK -->
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-storage-compat.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- App Scripts -->
    <script type="module" src="src/app-auth.js"></script>
    <script type="module" src="src/storage.js"></script>
    <script type="module" src="src/globals.js"></script>
    <script type="module" src="src/utils.js"></script>
    <script type="module" src="includes/navigation.js"></script>
    
    <!-- Template initialization -->
    <script type="module">
        import unifiedAuth from './src/unified-auth.js';
        import { initNavigation } from './includes/navigation.js';
        
        // Initialize unified authentication
        unifiedAuth.addAuthListener((user, isAdmin) => {
            if (user) {
                console.log('✅ User authenticated in app:', user.email);
                // User info will be shown by unified auth system
            } else {
                console.log('❌ User not authenticated, redirecting to login...');
                window.location.href = 'login.php';
            }
        });
        
        // Initialize navigation for all pages
        initNavigation();
        
        // Page-specific initialization will be injected here
        
        // App-specific initialization
        console.log("🎵 App page loaded");
        
        // Import and initialize app functionality
        import("./src/app.js").then(module => {
            module.initApp();
        }).catch(error => {
            console.error("Failed to load app module:", error);
        });
        
    </script>
</body>
</html>
