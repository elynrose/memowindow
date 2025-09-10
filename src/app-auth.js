// App-specific authentication functionality
import { signOut, onAuthStateChanged } from 'firebase/auth';
import { auth } from '../firebase-config.php';

let currentUser = null;

// Get DOM elements for app page
const getElements = () => ({
  userInfo: document.getElementById('userInfo'),
  btnLogout: document.getElementById('btnLogout'),
  userName: document.getElementById('userName'),
  userAvatar: document.getElementById('userAvatar'),
  ordersLink: document.getElementById('ordersLink'),
  mainContent: document.getElementById('mainContent'),
});

// Show user info in app
async function showUserInfo(user) {
  const els = getElements();
  
  if (!els.userInfo) return;
  
  els.userInfo.classList.remove('hidden');
  els.userName.textContent = user.displayName || user.email;
  
  // Set user avatar with better fallback
  if (user.photoURL) {
    console.log('Setting user avatar:', user.photoURL);
    els.userAvatar.src = user.photoURL;
    els.userAvatar.onerror = () => {
      console.log('Avatar failed to load, using fallback');
      els.userAvatar.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%23667eea"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
    };
  } else {
    console.log('No photoURL available, using fallback');
    els.userAvatar.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%23667eea"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
  }
  
  // Update orders link with user ID
  if (els.ordersLink) {
    els.ordersLink.href = `orders.php?user_id=${encodeURIComponent(user.uid)}`;
  }
  
  // Check if user is admin and add admin link
  checkAdminStatus(user.uid);
  
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
  console.log('🔐 User not authenticated, redirecting to login...');
  window.location.href = 'login.html';
}

// Initialize app authentication
export function initAppAuth() {
  console.log('🔥 Initializing App Auth...');
  const els = getElements();
  
  // Check if we have user data from sessionStorage
  const storedUser = sessionStorage.getItem('currentUser');
  if (storedUser) {
    try {
      const userData = JSON.parse(storedUser);
      console.log('📱 Found stored user data:', userData);
      // Create a mock user object for the app
      currentUser = userData;
      showUserInfo(userData);
    } catch (error) {
      console.error('Error parsing stored user data:', error);
      sessionStorage.removeItem('currentUser');
      redirectToLogin();
      return;
    }
  }
  
  // Set up auth state listener
  onAuthStateChanged(auth, (user) => {
    console.log('🔥 App auth state changed:', user ? 'Logged in' : 'Logged out');
    if (user) {
      // Update stored user data
      const userData = {
        uid: user.uid,
        displayName: user.displayName,
        email: user.email,
        photoURL: user.photoURL
      };
      sessionStorage.setItem('currentUser', JSON.stringify(userData));
      showUserInfo(userData);
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
    const response = await fetch(`check_admin.php?user_id=${encodeURIComponent(userUID)}`);
    const data = await response.json();
    
    if (data.is_admin) {
      // Add admin link to user info area
      const userInfo = document.getElementById('userInfo');
      if (userInfo && !userInfo.querySelector('.admin-link')) {
        const adminLink = document.createElement('a');
        adminLink.href = `admin.php?user_id=${encodeURIComponent(userUID)}`;
        adminLink.className = 'admin-link';
        adminLink.style.cssText = 'background: #dc2626; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; margin-right: 8px;';
        adminLink.textContent = 'Admin';
        
        // Insert before orders link
        const ordersLink = userInfo.querySelector('#ordersLink');
        if (ordersLink) {
          userInfo.insertBefore(adminLink, ordersLink);
        } else {
          userInfo.appendChild(adminLink);
        }
      }
    }
  } catch (error) {
    // Silently fail - not critical functionality
    console.log('Admin check failed:', error);
  }
}

// Get current user
export function getCurrentUser() {
  return currentUser;
}

// Make getCurrentUser available globally
window.getCurrentUser = getCurrentUser;
