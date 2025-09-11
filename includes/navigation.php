<?php
// navigation.php - Reusable navigation component
?>
<!-- Modern Header -->
<header class="header">
    <nav class="nav">
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="MemoWindow" style="height: 40px; width: auto;">
        </a>
        <!-- Mobile hamburger menu -->
        <button id="mobileMenuToggle" class="mobile-menu-toggle hidden">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <div id="userInfo" class="user-info hidden">
            <a href="memories.php" class="header-link">My Memories</a>
            <a id="ordersLink" href="#" class="header-link">My Orders</a>
            <div id="subscriptionStatus" class="subscription-status">
                <!-- Subscription status will be populated by JavaScript -->
            </div>
            <div class="user-profile">
                <img id="userAvatar" class="user-avatar" src="" alt="User avatar">
                <div class="user-details">
                    <span id="userName">Loading...</span>
                    <div class="user-submenu">
                        <a id="btnLogout" href="#" class="header-link submenu-link">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
