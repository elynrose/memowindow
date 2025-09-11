<?php
// navigation.php - Reusable navigation component
?>
<!-- Modern Header -->
<header class="header">
    <nav class="nav">
        <a href="index.html" class="logo">
            <img src="images/logo.png" alt="MemoWindow" style="height: 40px; width: auto;">
        </a>
        <div id="userInfo" class="user-info hidden">
            <div id="subscriptionStatus" class="subscription-status">
                <!-- Subscription status will be populated by JavaScript -->
            </div>
            <a href="memories.html" class="header-link">My Memories</a>
            <a id="ordersLink" href="#" class="header-link">My Orders</a>
            <a id="btnLogout" href="#" class="header-link">Sign Out</a>
            <div class="user-profile">
                <img id="userAvatar" class="user-avatar" src="" alt="User avatar">
                <span id="userName">Loading...</span>
            </div>
        </div>
    </nav>
</header>
