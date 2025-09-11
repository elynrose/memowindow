<?php
// Admin Navigation Component
// This file provides consistent navigation for all admin pages

$currentPage = basename($_SERVER['PHP_SELF']);
$userFirebaseUID = $_GET['user_id'] ?? '';
?>

<nav class="admin-nav">
    <div class="admin-nav-container">
        <div class="admin-nav-brand" style="margin-top: 20px;">
            <a href="admin.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" class="admin-nav-logo">
                🏠 Admin Dashboard
            </a>
        </div>
        
        <div class="admin-nav-links">
            <a href="admin.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" 
               class="admin-nav-link <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
            <a href="admin_users.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" 
               class="admin-nav-link <?php echo $currentPage === 'admin_users.php' ? 'active' : ''; ?>">
                👥 Users
            </a>
            <a href="admin_orders.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" 
               class="admin-nav-link <?php echo $currentPage === 'admin_orders.php' ? 'active' : ''; ?>">
                📦 Orders
            </a>
            <a href="admin_products.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" 
               class="admin-nav-link <?php echo $currentPage === 'admin_products.php' ? 'active' : ''; ?>">
                🛒 Products
            </a>
            <a href="admin_subscriptions.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" 
               class="admin-nav-link <?php echo $currentPage === 'admin_subscriptions.php' ? 'active' : ''; ?>">
                💳 Subscriptions
            </a>
            <a href="admin_voice_clone.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" 
               class="admin-nav-link <?php echo $currentPage === 'admin_voice_clone.php' ? 'active' : ''; ?>">
                🎤 Voice Clone
            </a>
            <a href="admin_backups.php?user_id=<?php echo urlencode($userFirebaseUID); ?>" 
               class="admin-nav-link <?php echo $currentPage === 'admin_backups.php' ? 'active' : ''; ?>">
                🔒 Backups
            </a>
        </div>
        
        <div class="admin-nav-actions">
            <a href="login.php" class="admin-nav-action">
                ← Back to MemoWindow
            </a>
          
        </div>
    </div>
</nav>
