// App-specific authentication functionality
import { signOut, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { auth } from '../firebase-config.php';

let currentUser = null;

// Create server-side session from Firebase user
async function createServerSession(user) {
  try {
    console.log('🔍 Creating server session for user:', user.uid);
    
    // Get Firebase ID token for server-side verification
    const idToken = await user.getIdToken();
    console.log('🔍 Got Firebase ID token');
    
    // Create server-side session
    const response = await fetch('firebase_login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        idToken: idToken,
        uid: user.uid,
        email: user.email,
        displayName: user.displayName,
        photoURL: user.photoURL
      })
    });
    
    console.log('🔍 Firebase login response status:', response.status);
    
    if (!response.ok) {
      console.error('Failed to create server session:', response.status);
      return false;
    }
    
    const result = await response.json();
    console.log('🔍 Firebase login response:', result);
    
    if (result.success) {
      console.log('✅ Server session created successfully');
      return true;
    } else {
      console.error('Server session creation failed:', result.error);
      return false;
    }
  } catch (error) {
    console.error('Error creating server session:', error);
    return false;
  }
}

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
  els.userName.textContent = user.displayName || user.email;
  
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
  
  // Update orders link with user ID
  if (els.ordersLink) {
    els.ordersLink.href = `orders.php`;
  }
  
  // Create server-side session
  await createServerSession(user);
  
  // Check if user is admin and add admin link
  checkAdminStatus(user.uid);
  
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
  // Initializing App Auth
  const els = getElements();
  
  // Check if we have user data from sessionStorage
  const storedUser = sessionStorage.getItem('currentUser');
  if (storedUser) {
    try {
      const userData = JSON.parse(storedUser);
      // Found stored user data
      // Create a mock user object for the app
      currentUser = userData;
      showUserInfo(userData);
    } catch (error) {
      console.error('Error parsing stored user data:', error);
      sessionStorage.removeItem('currentUser');
      redirectToLogin();
      return;
    }
  } else {
    // No stored user data found, waiting for Firebase auth
    // Set a timeout to redirect if Firebase auth doesn't respond
    // But only redirect if we're not on the orders page (which handles its own auth)
    setTimeout(() => {
      if (!currentUser && !window.location.pathname.includes('orders.php')) {
        // Firebase auth timeout, redirecting to login
        redirectToLogin();
      }
    }, 5000); // 5 second timeout, increased from 3
  }
  
  // Set up auth state listener
  onAuthStateChanged(auth, (user) => {
    // App auth state changed
    if (user) {
      // Update stored user data
      const userData = {
        uid: user.uid,
        displayName: user.displayName,
        email: user.email,
        photoURL: user.photoURL
      };
      sessionStorage.setItem('currentUser', JSON.stringify(userData));
      showUserInfo(user); // Pass the actual Firebase user object
    } else {
      // User is logged out - redirect to login
      sessionStorage.removeItem('currentUser');
      redirectToLogin();
    }
  });

  // Logout button
  if (els.btnLogout) {
    els.btnLogout.addEventListener('click', async () => {
      try {
        await signOut(auth);
        sessionStorage.removeItem('currentUser');
        redirectToLogin();
      } catch (error) {
        console.error('Logout failed:', error);
      }
    });
  }
}

// Function to check if user is admin and add admin link
async function checkAdminStatus(userUID) {
  try {
    const response = await fetch(`check_admin.php`);
    const data = await response.json();
    
    if (data.is_admin) {
      // Add admin link to user info area
      const userInfo = document.getElementById('userInfo');
      if (userInfo && !userInfo.querySelector('.admin-link')) {
        const adminLink = document.createElement('a');
        adminLink.href = `admin.php`;
        adminLink.className = 'admin-link';
        adminLink.style.cssText = 'background: #dc2626; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; margin-right: 8px;';
        adminLink.textContent = 'Admin';
        
        // Insert before user profile to make it more visible
        const userProfile = userInfo.querySelector('.user-profile');
        if (userProfile) {
          userInfo.insertBefore(adminLink, userProfile);
        } else {
          userInfo.appendChild(adminLink);
        }
      }
    }
  } catch (error) {
    // Admin check failed
    // Silently fail - not critical functionality
    // Admin check failed
  }
}

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
    const response = await fetch(`get_user_subscription.php`);
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
          '<a href="subscription_checkout.php" class="upgrade-button">Manage</a>'
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
