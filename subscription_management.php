<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'SubscriptionManager.php';

// Check if user is authenticated
$userId = requireAuth();
if (!$userId) {
    header('Location: login.php');
    exit;
}

$subscriptionManager = new SubscriptionManager();
$userLimits = $subscriptionManager->getUserLimits($userId);
$availablePackages = $subscriptionManager->getAvailablePackages();

// Get current subscription details
$currentSubscription = null;
$hasActiveSubscription = false;

try {
    $stmt = $pdo->prepare("
        SELECT s.*, p.package_name, p.package_slug, p.price_monthly, p.price_yearly, p.features
        FROM subscriptions s 
        JOIN packages p ON s.package_id = p.id 
        WHERE s.user_id = ? AND s.status = 'active'
        ORDER BY s.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $currentSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasActiveSubscription = !empty($currentSubscription);
} catch (PDOException $e) {
    error_log("Error fetching subscription: " . $e->getMessage());
}

// Get subscription history
$subscriptionHistory = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, p.package_name, p.package_slug
        FROM subscriptions s 
        JOIN packages p ON s.package_id = p.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $subscriptionHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching subscription history: " . $e->getMessage());
}
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
    <link rel="stylesheet" href="includes/navigation.css">
    
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
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
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
            padding: 2rem 1rem;
            margin-top: 80px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
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
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .subscription-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
        
        .subscription-card.current .card-price {
            color: white;
        }
        
        .card-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .card-features li {
            padding: 0.5rem 0;
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
            gap: 1rem;
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
            padding: 2rem;
            margin-bottom: 2rem;
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
            gap: 1.5rem;
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
    <!-- Modern Header -->
    <header class="header">
        <nav class="nav">
            <!-- Mobile hamburger menu -->
            <a href="#mobile-menu" class="mobile-menu-toggle hidden">
                <span></span>
                <span></span>
                <span></span>
            </a>
            
            <a href="index.php" class="logo">
                <img src="images/logo.png" alt="MemoWindow" style="height: 40px; width: auto;">
            </a>
            
            <div id="userInfo" class="user-info hidden">
                <a href="memories.php" class="header-link">My Memories</a>
                <a href="orders.php" class="header-link">My Orders</a>
                <a href="subscription_management.php" class="header-link">Manage Subscription</a>
                <a id="btnLogout" href="#" class="header-link">Sign Out</a>
                <div class="user-profile">
                    <img id="userAvatar" class="user-avatar" src="" alt="User avatar">
                    <span id="userName">Loading...</span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Mobile menu -->
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
            <h1>Manage Subscription</h1>
            <p>View and manage your MemoWindow subscription</p>
        </div>

        <!-- Current Usage -->
        <div class="usage-section">
            <h2 class="usage-title">Current Usage</h2>
            <div class="usage-grid">
                <div class="usage-item">
                    <div class="usage-number"><?php echo $userLimits['memories_used'] ?? 0; ?></div>
                    <div class="usage-label">Memories Created</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number"><?php echo $userLimits['memories_limit'] ?? 0; ?></div>
                    <div class="usage-label">Memory Limit</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number"><?php echo $userLimits['audio_minutes_used'] ?? 0; ?></div>
                    <div class="usage-label">Audio Minutes Used</div>
                </div>
                <div class="usage-item">
                    <div class="usage-number"><?php echo $userLimits['audio_minutes_limit'] ?? 0; ?></div>
                    <div class="usage-label">Audio Minutes Limit</div>
                </div>
            </div>
        </div>

        <!-- Current Subscription -->
        <?php if ($hasActiveSubscription): ?>
        <div class="subscription-card current">
            <h3 class="card-title">Current Plan: <?php echo htmlspecialchars($currentSubscription['package_name']); ?></h3>
            <div class="card-price">
                $<?php echo number_format($currentSubscription['price_monthly'], 2); ?>/month
            </div>
            <ul class="card-features">
                <?php 
                $features = json_decode($currentSubscription['features'], true);
                if ($features) {
                    foreach ($features as $feature) {
                        echo '<li>' . htmlspecialchars($feature) . '</li>';
                    }
                }
                ?>
            </ul>
            <div class="card-actions">
                <a href="subscription_checkout.php?user_id=<?php echo urlencode($userId); ?>" class="btn btn-primary">Change Plan</a>
                <button onclick="cancelSubscription()" class="btn btn-danger">Cancel Subscription</button>
            </div>
        </div>
        <?php else: ?>
        <div class="subscription-card">
            <h3 class="card-title">Free Plan</h3>
            <div class="card-price">$0/month</div>
            <ul class="card-features">
                <li>Limited memories</li>
                <li>Basic features</li>
                <li>Standard support</li>
            </ul>
            <div class="card-actions">
                <a href="index.php#pricing" class="btn btn-primary">Upgrade Plan</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Available Plans -->
        <div class="subscription-grid">
            <?php foreach ($availablePackages as $package): ?>
                <?php if (!$hasActiveSubscription || $package['package_slug'] !== $currentSubscription['package_slug']): ?>
                <div class="subscription-card">
                    <h3 class="card-title"><?php echo htmlspecialchars($package['package_name']); ?></h3>
                    <div class="card-price">$<?php echo number_format($package['price_monthly'], 2); ?>/month</div>
                    <ul class="card-features">
                        <?php 
                        $features = json_decode($package['features'], true);
                        if ($features) {
                            foreach ($features as $feature) {
                                echo '<li>' . htmlspecialchars($feature) . '</li>';
                            }
                        }
                        ?>
                    </ul>
                    <div class="card-actions">
                        <a href="subscription_checkout.php?user_id=<?php echo urlencode($userId); ?>&package=<?php echo urlencode($package['package_slug']); ?>" class="btn btn-primary">
                            <?php echo $hasActiveSubscription ? 'Switch to This Plan' : 'Choose Plan'; ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Subscription History -->
        <?php if (!empty($subscriptionHistory)): ?>
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
                    <?php foreach ($subscriptionHistory as $history): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($history['package_name']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $history['status']; ?>">
                                <?php echo ucfirst($history['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($history['created_at'])); ?></td>
                        <td>
                            <?php 
                            if ($history['end_date']) {
                                echo date('M j, Y', strtotime($history['end_date']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>$<?php echo number_format($history['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- App Scripts -->
    <script type="module" src="src/app-auth.js"></script>
    <script type="module" src="src/storage.js"></script>
    <script type="module" src="src/globals.js"></script>
    <script type="module" src="src/utils.js"></script>
    <script type="module" src="includes/navigation.js"></script>
    
    <!-- Template initialization -->
    <script type="module">
        import { initAppAuth } from './src/app-auth.js';
        import { initNavigation } from './includes/navigation.js';
        
        // Initialize authentication for all pages
        initAppAuth();
        
        // Initialize navigation for all pages
        initNavigation();
        
        // Cancel subscription function
        window.cancelSubscription = async function() {
            if (confirm('Are you sure you want to cancel your subscription? You will lose access to premium features at the end of your billing period.')) {
                try {
                    const response = await fetch('cancel_subscription.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_id: '<?php echo $userId; ?>'
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Subscription cancelled successfully. You will retain access until the end of your billing period.');
                        location.reload();
                    } else {
                        alert('Error cancelling subscription: ' + result.error);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error cancelling subscription. Please try again.');
                }
            }
        };
    </script>
</body>
</html>
