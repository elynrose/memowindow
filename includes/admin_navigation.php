<?php
// Admin Navigation Component
// This file provides consistent navigation for all admin pages

$currentPage = basename($_SERVER['PHP_SELF']);

// Get user ID from unified auth system instead of URL parameter
require_once 'unified_auth.php';
$currentUser = getCurrentUser();
$userFirebaseUID = $currentUser ? $currentUser['uid'] : '';
?>

<nav class="admin-nav">
    <div class="admin-nav-container">
        <div class="admin-nav-brand" style="margin-top: 20px;">
            <a href="admin.php" class="admin-nav-logo">
                🏠 Admin Dashboard
            </a>
        </div>
        
        <div class="admin-nav-links">
            <a href="admin.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
            <a href="admin_users.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin_users.php' ? 'active' : ''; ?>">
                👥 Users
            </a>
            <a href="admin_orders.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin_orders.php' ? 'active' : ''; ?>">
                📦 Orders
            </a>
            <a href="admin_products.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin_products.php' ? 'active' : ''; ?>">
                🛒 Products
            </a>
            <a href="admin_subscriptions.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin_subscriptions.php' ? 'active' : ''; ?>">
                💳 Subscriptions
            </a>
            <a href="admin_voice_clone.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin_voice_clone.php' ? 'active' : ''; ?>">
                🎤 Voice Clone
            </a>
            <a href="admin_backups.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin_backups.php' ? 'active' : ''; ?>">
                🔒 Backups
            </a>
            <a href="admin_email_templates.php" 
               class="admin-nav-link <?php echo $currentPage === 'admin_email_templates.php' ? 'active' : ''; ?>">
                📧 Email Templates
            </a>
        </div>
        
        <div class="admin-nav-actions">
            <a href="login.php" class="admin-nav-action">
                ← Back to MemoWindow
            </a>
          
        </div>
    </div>
</nav>
