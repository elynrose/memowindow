// navigation.js - Reusable navigation functionality

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
            // Import auth dynamically to avoid circular dependencies
            const { auth } = await import('../firebase-config.php');
            await auth.signOut();
            console.log('✅ User signed out successfully');
            window.location.href = 'index.php';
        } catch (error) {
            console.error('❌ Error signing out:', error);
        }
    });
    
    // Set up orders link
    if (els.ordersLink) {
        els.ordersLink.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Try multiple ways to get current user
            let currentUser = null;
            
            // Method 1: Check window.auth
            if (window.auth && window.auth.currentUser) {
                currentUser = window.auth.currentUser;
            }
            
            // Method 2: Check sessionStorage
            if (!currentUser) {
                const storedUser = sessionStorage.getItem('currentUser');
                if (storedUser) {
                    try {
                        const userData = JSON.parse(storedUser);
                        currentUser = userData;
                    } catch (error) {
                        console.error('Error parsing stored user:', error);
                    }
                }
            }
            
            // Method 3: Check global getCurrentUser function
            if (!currentUser && window.getCurrentUser) {
                currentUser = window.getCurrentUser();
            }
            
            if (currentUser && currentUser.uid) {
                console.log('✅ User found, redirecting to orders');
                window.location.href = `orders.php?user_id=${encodeURIComponent(currentUser.uid)}`;
            } else {
                console.log('❌ No user found, redirecting to login');
                window.location.href = 'login.php';
            }
        });
    }
    
    // Set up mobile menu toggle
    if (els.mobileMenuToggle) {
        els.mobileMenuToggle.addEventListener('click', () => {
            els.mobileMenuToggle.classList.toggle('active');
            els.userInfo.classList.toggle('mobile-open');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!els.mobileMenuToggle.contains(e.target) && !els.userInfo.contains(e.target)) {
                els.mobileMenuToggle.classList.remove('active');
                els.userInfo.classList.remove('mobile-open');
            }
        });
    }
}

// Show user info in navigation
export function showUserInfo(user) {
    const els = getElements();
    if (!els.userInfo) return;
    
    els.userInfo.classList.remove('hidden');
    
    if (els.userName) {
        els.userName.textContent = user.displayName || user.email || 'User';
    }
    
    if (els.userAvatar) {
        els.userAvatar.src = user.photoURL || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgiIGhlaWdodD0iMjgiIHZpZXdCb3g9IjAgMCAyOCAyOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTQiIGN5PSIxNCIgcj0iMTQiIGZpbGw9IiM2NjdlZWEiLz4KPHN2ZyB4PSI4IiB5PSI4IiB3aWR0aD0iMTIiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIgMTRDOC42ODYyOSAxNCA2IDE2LjY4NjMgNiAyMEgyMEMyMCAxNi42ODYzIDE3LjMxMzcgMTQgMTQgMTRIOloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo8L3N2Zz4K';
        els.userAvatar.alt = user.displayName || 'User avatar';
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
