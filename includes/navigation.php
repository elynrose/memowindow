<?php
// navigation.php - Reusable navigation component
?>
<!-- Modern Header -->
<header class="header">
    <nav class="nav">
        <!-- mmenu hamburger button -->
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
            <a id="ordersLink" href="#" class="header-link">My Orders</a>
            <a href="invitation_manager.php" class="header-link">Invitations</a>
            <a href="subscription_management.php" class="header-link">Manage Subscription</a>
            <a id="adminButton" href="admin.php" class="header-link admin-button" style="display: none;">Admin</a>
            <div id="subscriptionStatus" class="subscription-status">
                <!-- Subscription status will be populated by JavaScript -->
            </div>
            <div class="user-profile">
                <img id="userAvatar" class="user-avatar" src="" alt="User avatar">
                <div class="user-details">
                    <span id="userName" class="user-name">Loading...</span>
                    <div class="user-dropdown">
                        <a id="btnLogout" href="#" class="dropdown-link">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- mmenu mobile menu -->
<nav id="mobile-menu">
    <ul>
        <li><a href="memories.php">My Memories</a></li>
        <li><a href="orders.php">My Orders</a></li>
        <li><a href="invitation_manager.php">Invitations</a></li>
        <li>
            <span>Subscription</span>
            <ul id="subscription-menu">
                <li><a href="#pricing">Upgrade Plan</a></li>
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
