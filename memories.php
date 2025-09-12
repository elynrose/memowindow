<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Memories - MemoWindow</title>
    
    <!-- Cache busting -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Unified Styles -->
    <link rel="stylesheet" href="includes/unified.css?v=<?php echo time(); ?>">
    
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
            
            /* Hide user info on mobile by default */
            .user-info {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                gap: 1rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .user-info.mobile-open {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            
            .header-link {
                padding: 0.75rem 0;
                font-size: 0.9rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                text-align: center;
            }
            
            .header-link:last-child {
                border-bottom: none;
            }
            
            .subscription-status {
                flex-direction: column;
                gap: 0.75rem;
                align-items: center;
                margin-right: 0;
                padding: 0.75rem 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            }
            
            .subscription-info {
                align-items: center;
                text-align: center;
            }
            
            .upgrade-button {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
                width: 100%;
                text-align: center;
            }
            
            
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
        
        /* Responsive Design */
        @media (max-width: 767px) {
            .header {
                padding: 0.75rem 0;
            }
            
            .nav {
                padding: 0 0.75rem;
            }
            
            .wrap {
                padding: 2rem 0.75rem;
            }
            
            .user-info {
                gap: 0.5rem;
            }
            
            .header-link {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
            }
        }
        
        /* Page-specific styles will be injected here */
        
        /* Memories-specific styles */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .page-subtitle {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }
        
        .create-memory-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .create-memory-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
        
        .memories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .memory-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .memory-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .memory-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .memory-content {
            padding: 1.5rem;
        }
        
        .memory-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .memory-date {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .memory-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .memory-action {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #374151;
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        
        .memory-action:hover {
            background: #f3f4f6;
        }
        
        .memory-action.order {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .memory-action.order:hover {
            background: #bfdbfe;
        }
        
        .memory-action.delete {
            color: #dc2626;
        }
        
        .memory-action.delete:hover {
            background: #fef2f2;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        
    </style>
    
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="wrap">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">My Memories</h1>
            <p class="page-subtitle">Your beautiful waveform memories, ready to share and print</p>
            <a href="app.php" class="create-memory-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                </svg>
                Create New Memory
            </a>
        </div>

        <!-- Memories Container -->
        <div id="memoriesContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                Loading your memories...
            </div>
        </div>
        
    </div>

    <!-- Firebase SDK -->
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-storage-compat.js"></script>
    
    <!-- App Scripts -->
    <script type="module" src="src/storage.js"></script>
    <script type="module" src="src/utils.js"></script>
    <script type="module" src="includes/navigation.js"></script>
    
    <!-- Template initialization -->
    <script type="module">
        import unifiedAuth from './src/unified-auth.js';
        import { initNavigation } from './includes/navigation.js';
        
        // Initialize unified authentication
        unifiedAuth.addAuthListener((user, isAdmin) => {
            if (user) {
            } else {
                window.location.href = 'login.php';
            }
        });
        
        // Initialize navigation for all pages
        initNavigation();
        
        // Page-specific initialization will be injected here
        
        // Memories-specific initialization
        
        // Import and initialize memories functionality
        import("./src/memories.js").then(module => {
            if (module.initMemories) {
                module.initMemories();
            } else {
                throw new Error("initMemories function not found");
            }
        }).catch(error => {
            // Fallback: show error message
            const container = document.getElementById("memoriesContainer");
            if (container) {
                container.innerHTML = "<div class=\"empty-state\">" +
                    "<div class=\"empty-state-icon\">⚠️</div>" +
                    "<h3>Error Loading Memories</h3>" +
                    "<p>There was a problem loading the memories module. Please refresh the page.</p>" +
                    "<p style=\"color: #dc2626; font-size: 0.875rem; margin-top: 0.5rem;\">Error: " + error.message + "</p>" +
                    "<button onclick=\"location.reload()\" class=\"create-memory-btn\" style=\"margin-top: 1rem;\">" +
                        "Refresh Page" +
                    "</button>" +
                "</div>";
            }
        });
        
    </script>
    
</body>
</html>
