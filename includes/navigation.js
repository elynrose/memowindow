// navigation.js - Reusable navigation functionality
import unifiedAuth from '../src/unified-auth.js';

// Get DOM elements for navigation
const getElements = () => ({
    userInfo: document.getElementById('userInfo'),
    btnLogout: document.getElementById('btnLogout'),
    userName: document.getElementById('userName'),
    userAvatar: document.getElementById('userAvatar'),
    ordersLink: document.getElementById('ordersLink'),
    subscriptionStatus: document.getElementById('subscriptionStatus'),
    mobileMenuToggle: document.getElementById('mobileMenuToggle'),
});

// Initialize navigation
export function initNavigation() {
    const els = getElements();
    
    if (!els.userInfo || !els.btnLogout) {
        console.error('❌ Navigation elements not found');
        return;
    }
    
    // Set up logout functionality
    els.btnLogout.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await unifiedAuth.logout();
        } catch (error) {
            console.error('❌ Error signing out:', error);
        }
    });
    
    // Orders link is handled by unified auth - no need to set up click handler here
    
    // Initialize custom mobile menu (simpler approach)
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        // Set up mobile menu toggle
        mobileMenuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            mobileMenuToggle.classList.toggle('active');
            mobileMenu.classList.toggle('mobile-open');
            document.body.classList.toggle('menu-open');
            console.log('✅ Mobile menu toggled');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenuToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                mobileMenu.classList.remove('mobile-open');
                document.body.classList.remove('menu-open');
            }
        });
        
        // Close mobile menu when clicking on a link
        mobileMenu.addEventListener('click', (e) => {
            if (e.target.tagName === 'A') {
                mobileMenuToggle.classList.remove('active');
                mobileMenu.classList.remove('mobile-open');
                document.body.classList.remove('menu-open');
            }
        });
        
        console.log('✅ Custom mobile menu initialized successfully');
    }
    
    // Set up logout button for mobile menu
    const mobileLogoutBtn = document.getElementById('mobile-logout');
    if (mobileLogoutBtn) {
        mobileLogoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                console.log('🚪 Logging out from mobile menu...');
                const response = await fetch('logout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
                const result = await response.json();
                console.log('🔍 Server logout response:', result);
                const { auth } = await import('../firebase-config.php');
                await auth.signOut();
                console.log('✅ Firebase sign out successful');
                sessionStorage.removeItem('currentUser');
                localStorage.removeItem('currentUser');
                window.location.href = 'login.php';
            } catch (error) {
                console.error('❌ Logout failed:', error);
                window.location.href = 'login.php';
            }
        });
    }
}

// Show user info in navigation
export function showUserInfo(user) {
    const els = getElements();
    if (!els.userInfo) return;
    
    els.userInfo.classList.remove('hidden');
    
    // Show hamburger menu button when user is logged in
    if (els.mobileMenuToggle) {
        els.mobileMenuToggle.classList.remove('hidden');
    }
    
    if (els.userName) {
        els.userName.textContent = user.displayName || user.email || 'User';
    }
    
    if (els.userAvatar) {
        els.userAvatar.src = user.photoURL || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgiIGhlaWdodD0iMjgiIHZpZXdCb3g9IjAgMCAyOCAyOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTQiIGN5PSIxNCIgcj0iMTQiIGZpbGw9IiM2NjdlZWEiLz4KPHN2ZyB4PSI4IiB5PSI4IiB3aWR0aD0iMTIiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIgMTRDOC42ODYyOSAxNCA2IDE2LjY4NjMgNiAyMEgyMEMyMCAxNi42ODYzIDE3LjMxMzcgMTQgMTQgMTRIOloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo8L3N2Zz4K';
        els.userAvatar.alt = user.displayName || 'User avatar';
    }
    
    // Update mobile menu user name
    const mobileUserName = document.getElementById('mobile-user-name');
    if (mobileUserName) {
        mobileUserName.textContent = user.displayName || user.email || 'User';
    }
    
    // Load and display subscription status
    loadSubscriptionStatus(user.uid);
}

// Hide user info in navigation
export function hideUserInfo() {
    const els = getElements();
    if (els.userInfo) {
        els.userInfo.classList.add('hidden');
    }
    
    // Hide hamburger menu button when user is logged out
    if (els.mobileMenuToggle) {
        els.mobileMenuToggle.classList.add('hidden');
    }
}

// Load and display subscription status
async function loadSubscriptionStatus(userId) {
    const els = getElements();
    if (!els.subscriptionStatus) return;
    
    try {
        const response = await fetch(`get_user_subscription.php?user_id=${encodeURIComponent(userId)}`);
        const data = await response.json();
        
        if (data.success) {
            const subscription = data.subscription;
            const isFree = subscription.package_slug === 'free' || !data.has_subscription;
            
            els.subscriptionStatus.innerHTML = `
                <div class="subscription-info">
                    <div class="subscription-plan">${subscription.package_name} Plan</div>
                    <div class="subscription-status-text">${isFree ? 'Free Tier' : 'Active'}</div>
                </div>
                ${isFree ? 
                    '<a href="index.php#pricing" class="upgrade-button">Upgrade</a>' :
                    '<a href="subscription_checkout.php?user_id=' + encodeURIComponent(userId) + '" class="upgrade-button">Manage</a>'
                }
            `;
        } else {
            // Fallback if subscription data is not available
            els.subscriptionStatus.innerHTML = `
                <div class="subscription-info">
                    <div class="subscription-plan">Free Plan</div>
                    <div class="subscription-status-text">Free Tier</div>
                </div>
                <a href="index.php#pricing" class="upgrade-button">Upgrade</a>
            `;
        }
    } catch (error) {
        console.error('Error loading subscription status:', error);
        // Fallback on error
        els.subscriptionStatus.innerHTML = `
            <div class="subscription-info">
                <div class="subscription-plan">Free Plan</div>
                <div class="subscription-status-text">Free Tier</div>
            </div>
            <a href="index.php#pricing" class="upgrade-button">Upgrade</a>
        `;
    }
}
