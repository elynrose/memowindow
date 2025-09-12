<?php
// orders.php - User Orders Page
require_once 'unified_auth.php';

// Require authentication
$currentUser = requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - MemoWindow</title>
    
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
            
            .user-profile {
                flex-direction: column;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 0;
            }
            
            .user-details {
                align-items: center;
            }
            
            .user-submenu {
                position: static;
                display: block;
                background: none;
                box-shadow: none;
                padding: 0;
                min-width: auto;
            }
            
            .submenu-link {
                padding: 0.5rem 0;
                text-align: center;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
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
        
        /* Orders-specific styles */
        .order-card {
            background: white;
            border: 1px solid #e6e9f2;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .order-title {
            font-size: 18px;
            font-weight: 600;
            color: #0b0d12;
            margin: 0;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-shipped {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 16px;
            align-items: center;
        }
        
        .order-image {
            width: 80px;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e6e9f2;
            object-fit: cover;
            background: white;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-info h4 {
            margin: 0 0 4px 0;
            color: #0b0d12;
            font-size: 14px;
        }
        
        .order-info p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
        }
        
        .order-price {
            font-size: 16px;
            font-weight: 600;
            color: #0b0d12;
            text-align: right;
        }
        
        .order-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
            font-size: 12px;
            color: #6b7280;
        }
        
        .cancel-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
            transition: background-color 0.2s;
        }
        
        .cancel-btn:hover {
            background: #b91c1c;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .empty-state h3 {
            color: #0b0d12;
            margin-bottom: 8px;
        }
        
        @media (max-width: 640px) {
            .order-details {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .order-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .order-meta {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
        
    </style>
    
</head>
<body>
    <!-- Modern Header -->
    <header class="header">
        <nav class="nav">
            <a href="index.php" class="logo">
                <img src="images/logo.png" alt="MemoWindow" style="height: 40px; width: auto;">
            </a>
            
            <!-- Mobile hamburger menu -->
            <a href="#mobile-menu" class="mobile-menu-toggle hidden">
                <span></span>
                <span></span>
                <span></span>
            </a>
            
            <div id="userInfo" class="user-info hidden">
                <a href="memories.php" class="header-link">My Memories</a>
                <a id="ordersLink" href="#" class="header-link">My Orders</a>
                <a href="subscription_management.php" class="header-link">Manage Subscription</a>
                <a id="btnLogout" href="#" class="header-link">Sign Out</a>
                <div class="user-profile">
                    <img id="userAvatar" class="user-avatar" src="" alt="User avatar">
                    <span id="userName">Loading...</span>
                </div>
            </div>
        </nav>
    </header>

    <!-- mmenu mobile menu -->
    <nav id="mobile-menu">
        <ul>
            <li><a href="memories.php">My Memories</a></li>
            <li><a href="orders.php">My Orders</a></li>
            <li>
                <span>Subscription</span>
                <ul id="subscription-menu">
                    <li><a href="index.php#pricing">Upgrade Plan</a></li>
                    <li><a href="subscription_management.php">Manage Subscription</a></li>
                </ul>
            </li>
            <li>
                <span id="mobile-user-name">User</span>
                <ul>
                    <li><a href="#" id="mobile-logout">Sign Out</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="wrap">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title" style="color:#FFFFFF;">My Orders</h1>
            <p class="page-subtitle" style="margin-bottom: 50px; color:#FFFFFF;">Track your MemoryWave print orders</p>
        </div>

        <!-- Orders Container -->
        <div id="ordersContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                Loading your orders...
            </div>
        </div>
        
    </div>

    <!-- Firebase SDK -->
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-storage-compat.js"></script>
    
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
        
        // Initialize navigation
        initNavigation();
        
        // Unified auth is automatically initialized when imported
        
        // Page-specific initialization will be injected here
        
        // Orders-specific initialization
        console.log("📦 Orders page loaded");
        
        // Import and initialize orders functionality
        import("./src/orders.js").then(module => {
            console.log("✅ Orders module loaded successfully");
            module.initOrders();
        }).catch(error => {
            console.error("❌ Failed to load orders module:", error);
            // Fallback: show error message
            const container = document.getElementById("ordersContainer");
            if (container) {
                container.innerHTML = `
                    <div class="order-card" style="text-align: center; color: #dc2626;">
                        <h3>Error Loading Orders</h3>
                        <p>Failed to load orders functionality. Please refresh the page.</p>
                    </div>
                `;
            }
        });
        
    </script>
    
</body>
</html>
