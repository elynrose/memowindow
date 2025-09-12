<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscription - MemoWindow</title>
    
    <!-- Cache busting -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Navigation Styles -->
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
            display: none; /* Hide until authenticated */
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
            color: #333;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .logo img {
            height: 40px;
            width: auto;
            margin-right: 0.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        
        /* Main Content */
        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 1rem;
            margin-top: 80px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 4rem;
            color: white;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Subscription Cards */
        .subscription-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            margin-bottom: 4rem;
            padding: 0 1rem;
        }
        
        .subscription-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .subscription-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .subscription-card.current {
            border: 2px solid #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .subscription-card.current .card-title {
            color: white;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .card-price {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .card-description {
            color: #666;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1rem;
        }
        
        .subscription-card.current .card-price {
            color: white;
        }
        
        .card-features {
            list-style: none;
            margin-bottom: 2.5rem;
        }
        
        .card-features li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
        }
        
        .card-features li::before {
            content: '✓';
            color: #10b981;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .subscription-card.current .card-features li::before {
            color: white;
        }
        
        .card-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .subscription-card.current .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .subscription-card.current .btn-primary:hover {
            background: #f7fafc;
        }
        
        /* Current Usage */
        .usage-section {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .usage-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .usage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .usage-item {
            text-align: center;
            padding: 1.5rem;
            background: #f7fafc;
            border-radius: 12px;
        }
        
        .usage-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .usage-label {
            color: #718096;
            font-size: 0.9rem;
        }
        
        /* Subscription History */
        .history-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .history-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .history-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-cancelled {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .status-expired {
            background: #fef5e7;
            color: #744210;
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
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .subscription-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .card-actions {
                flex-direction: column;
            }
            
            .usage-grid {
                grid-template-columns: 1fr;
            }
            
            .history-table {
                font-size: 0.9rem;
            }
            
            .history-table th,
            .history-table td {
                padding: 0.75rem 0.5rem;
            }
        }
        
        /* Utility Classes */
        .hidden {
            display: none !important;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mb-2 {
            margin-bottom: 1rem;
        }
        
        .mt-2 {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="wrap">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Manage Subscription</h1>
            <p>View and manage your MemoWindow subscription</p>
        </div>

        <!-- Current Usage -->
        <div class="usage-section">
            <h2 class="usage-title">Current Usage</h2>
            <div class="usage-grid">
                <div class="usage-item">
                    <div class="usage-number" id="memories-used">0</div>
                    <div class="usage-label">Memories Created</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number" id="memories-limit">5</div>
                    <div class="usage-label">Memory Limit</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number" id="audio-minutes-used">0</div>
                    <div class="usage-label">Audio Minutes Used</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number" id="audio-minutes-limit">60</div>
                    <div class="usage-label">Audio Minutes Limit</div>
                </div>
            </div>
        </div>

        <!-- Current Subscription -->
        <div id="current-subscription-container">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Available Plans -->
        <div id="available-plans-container">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Subscription History -->
        <div id="subscription-history-container">
            <!-- Will be populated by JavaScript -->
        </div>
    </div>

    <!-- Firebase Authentication -->
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js"></script>
    <script type="module" src="firebase-config.php"></script>
    
    <!-- App Scripts -->
    <script type="module" src="src/storage.js"></script>
    <script type="module" src="src/globals.js"></script>
    <script type="module" src="src/utils.js"></script>
    <script type="module" src="includes/navigation.js"></script>
    
    <!-- Template initialization -->
    <script type="module">
        import unifiedAuth from './src/unified-auth.js';
        import { initNavigation } from './includes/navigation.js';
        
        // Initialize unified authentication
        unifiedAuth.addAuthListener(async (user, isAdmin) => {
            if (user) {
                // User is authenticated, show the page content
                document.body.style.display = 'block';
                
                // Fetch subscription data
                await loadSubscriptionData();
            } else {
                // User is not authenticated, redirect to login
                window.location.href = 'login.php';
            }
        });
        
        // Initialize navigation for all pages
        initNavigation();
        
        // Load subscription data from API
        async function loadSubscriptionData() {
            try {
                const response = await fetch('get_subscription_data.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    populateSubscriptionData(result.data);
                } else {
                    showError('Failed to load subscription data: ' + result.error);
                }
            } catch (error) {
                showError('Error loading subscription data. Please try again.');
            }
        }
        
        // Populate the page with subscription data
        function populateSubscriptionData(data) {
            const { currentSubscription, hasActiveSubscription, subscriptionHistory, availablePackages, userLimits } = data;
            
            // Update usage statistics
            document.getElementById('memories-used').textContent = userLimits.memories_used;
            document.getElementById('memories-limit').textContent = userLimits.memories_limit;
            document.getElementById('audio-minutes-used').textContent = userLimits.audio_minutes_used;
            document.getElementById('audio-minutes-limit').textContent = userLimits.audio_minutes_limit;
            
            // Populate current subscription
            populateCurrentSubscription(currentSubscription, hasActiveSubscription);
            
            // Populate available plans
            populateAvailablePlans(availablePackages, hasActiveSubscription, currentSubscription);
            
            // Populate subscription history
            populateSubscriptionHistory(subscriptionHistory);
        }
        
        // Populate current subscription section
        function populateCurrentSubscription(currentSubscription, hasActiveSubscription) {
            const container = document.getElementById('current-subscription-container');
            
            if (hasActiveSubscription && currentSubscription) {
                const features = JSON.parse(currentSubscription.features || '[]');
                const featuresHtml = features.map(feature => `<li>${escapeHtml(feature)}</li>`).join('');
                
                container.innerHTML = `
                    <div class="subscription-card current">
                        <h3 class="card-title">Current Plan: ${escapeHtml(currentSubscription.package_name)}</h3>
                        <div class="card-price">$${parseFloat(currentSubscription.price_monthly).toFixed(2)}/month</div>
                        <ul class="card-features">
                            ${featuresHtml}
                        </ul>
                        <div class="card-actions">
                            <a href="subscription_checkout.php" class="btn btn-primary">Change Plan</a>
                            <button onclick="cancelSubscription()" class="btn btn-danger">Cancel Subscription</button>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="subscription-card">
                        <h3 class="card-title">Free Plan</h3>
                        <div class="card-price">$0/month</div>
                        <ul class="card-features">
                            <li>Limited memories</li>
                            <li>Basic features</li>
                            <li>Standard support</li>
                        </ul>
                        <div class="card-actions">
                            <a href="subscription_checkout.php" class="btn btn-primary">Upgrade Plan</a>
                        </div>
                    </div>
                `;
            }
        }
        
        // Populate available plans section
        function populateAvailablePlans(availablePackages, hasActiveSubscription, currentSubscription) {
            const container = document.getElementById('available-plans-container');
            
            container.innerHTML = `
                <div class="subscription-card">
                    <h3 class="card-title">View Available Plans</h3>
                    <div class="card-description">
                        Browse and compare all available subscription packages
                    </div>
                    <div class="card-actions">
                        <a href="subscription_checkout.php" class="btn btn-primary">
                            View All Packages
                        </a>
                    </div>
                </div>
            `;
        }
        
        // Populate subscription history section
        function populateSubscriptionHistory(subscriptionHistory) {
            const container = document.getElementById('subscription-history-container');
            
            if (!subscriptionHistory || subscriptionHistory.length === 0) {
                container.innerHTML = '<p>No subscription history available.</p>';
                return;
            }
            
            const historyRows = subscriptionHistory.map(history => {
                const endDate = history.end_date ? 
                    new Date(history.end_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 
                    'N/A';
                
                return `
                    <tr>
                        <td>${escapeHtml(history.package_name)}</td>
                        <td>
                            <span class="status-badge status-${history.status}">
                                ${history.status.charAt(0).toUpperCase() + history.status.slice(1)}
                            </span>
                        </td>
                        <td>${new Date(history.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>${endDate}</td>
                        <td>$${parseFloat(history.amount).toFixed(2)}</td>
                    </tr>
                `;
            }).join('');
            
            container.innerHTML = `
                <div class="history-section">
                    <h2 class="history-title">Subscription History</h2>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${historyRows}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Show error message
        function showError(message) {
            const container = document.querySelector('.wrap');
            container.innerHTML = `
                <div class="page-header">
                    <h1>Error</h1>
                    <p>${escapeHtml(message)}</p>
                    <button onclick="location.reload()" class="btn btn-primary">Try Again</button>
                </div>
            `;
        }
        
        // Cancel subscription function
        window.cancelSubscription = async function() {
            if (confirm('Are you sure you want to cancel your subscription? You will lose access to premium features at the end of your billing period.')) {
                try {
                    const response = await fetch('cancel_subscription.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({})
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Subscription cancelled successfully. You will retain access until the end of your billing period.');
                        // Reload the subscription data
                        await loadSubscriptionData();
                    } else {
                        alert('Error cancelling subscription: ' + result.error);
                    }
                } catch (error) {
                    alert('Error cancelling subscription. Please try again.');
                }
            }
        };
    </script>
</body>
</html>
