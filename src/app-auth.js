// App-specific authentication functionality
import unifiedAuth from './unified-auth.js';

let currentUser = null;

// Get DOM elements for app page
const getElements = () => ({
  userInfo: document.getElementById('userInfo'),
  btnLogout: document.getElementById('btnLogout'),
  userName: document.getElementById('userName'),
  userAvatar: document.getElementById('userAvatar'),
  ordersLink: document.getElementById('ordersLink'),
  mainContent: document.getElementById('mainContent'),
  subscriptionStatus: document.getElementById('subscriptionStatus'),
});

// Show user info in app
async function showUserInfo(user) {
  const els = getElements();
  
  if (!els.userInfo) return;
  
  els.userInfo.classList.remove('hidden');
  
  // Show hamburger menu button when user is logged in
  const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
  if (mobileMenuToggle) {
    mobileMenuToggle.classList.remove('hidden');
    console.log('✅ Hamburger menu button shown for logged-in user');
  }
  els.userName.textContent = user.displayName || user.email;
  
  // Update mobile menu user name
  const mobileUserName = document.getElementById('mobile-user-name');
  if (mobileUserName) {
    mobileUserName.textContent = user.displayName || user.email || 'User';
  }
  
  // Set user avatar with better fallback
  if (user.photoURL) {
    // Setting user avatar
    els.userAvatar.src = user.photoURL;
    els.userAvatar.onerror = () => {
      // Avatar failed to load, using fallback
      els.userAvatar.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%23667eea"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
    };
  } else {
    // No photoURL available, using fallback
    els.userAvatar.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%23667eea"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
  }
  
  // Update orders link (no user ID needed)
  if (els.ordersLink) {
    els.ordersLink.href = `orders.php`;
  }
  
  // Admin link is now handled by unified authentication system
  
  // Load and display subscription status
  loadSubscriptionStatus(user.uid);
  
  currentUser = user;
  
  // Update create button state based on all requirements
  if (window.updateCreateButtonState) {
    window.updateCreateButtonState();
  }
  
  // Load user's waveforms automatically when they sign in
  if (window.loadUserWaveforms) {
    await window.loadUserWaveforms();
  }
}

// Redirect to login if not authenticated
function redirectToLogin() {
  // User not authenticated, redirecting to login
  window.location.href = 'login.php';
}

// Initialize app authentication
export function initAppAuth() {
  console.log('🔐 Initializing App Auth with Unified Authentication');
  
  // Set up authentication listener
  unifiedAuth.addAuthListener((user, isAdmin) => {
    currentUser = user;
    if (user) {
      showUserInfo(user);
    } else {
      redirectToLogin();
    }
  });
  
  // Check if already authenticated
  if (unifiedAuth.isAuthenticated()) {
    currentUser = unifiedAuth.getCurrentUser();
    showUserInfo(currentUser);
  }
}

// Admin link creation is now handled by unified authentication system

// Get current user
export function getCurrentUser() {
  return currentUser;
}

// Load and display subscription status
async function loadSubscriptionStatus(userId) {
  const els = getElements();
  
  if (!els.subscriptionStatus) return;
  
  try {
    // Get user's subscription status
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
      // Fallback if subscription check fails
      els.subscriptionStatus.innerHTML = `
        <div class="subscription-info">
          <div class="subscription-plan">Free Plan</div>
          <div class="subscription-status-text">Free Tier</div>
        </div>
        <a href="index.php#pricing" class="upgrade-button">Upgrade</a>
      `;
    }
  } catch (error) {
    console.error('Error fetching subscription status:', error);
    // Fallback to upgrade button
    els.subscriptionStatus.innerHTML = `
      <div class="subscription-info">
        <div class="subscription-plan">Free Plan</div>
        <div class="subscription-status-text">Free Tier</div>
      </div>
      <a href="index.php#pricing" class="upgrade-button">Upgrade</a>
    `;
  }
}

// Make getCurrentUser available globally
window.getCurrentUser = getCurrentUser;
