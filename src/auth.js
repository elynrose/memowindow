// Authentication functionality
import { signInWithPopup, signInWithRedirect, getRedirectResult, signOut, onAuthStateChanged, createUserWithEmailAndPassword, signInWithEmailAndPassword } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { auth, googleProvider, emailProvider } from '../firebase-config.php';

let currentUser = null;

// Function to load user's waveforms with pagination
async function loadUserWaveforms(offset = 0, append = false) {
  if (!currentUser) {
    const waveformsList = document.getElementById('waveformsList');
    if (waveformsList) {
      waveformsList.classList.add('hidden');
    }
    return;
  }
  
  try {
    const response = await fetch(`get_waveforms.php?offset=${offset}&limit=5`);
    
    if (!response.ok) throw new Error('Failed to load waveforms');
    
    const data = await response.json();
    // Raw waveforms data received
    
    // Handle both new paginated format and old direct array format
    let waveforms, hasMore, total;
    
    if (Array.isArray(data)) {
      // Old format - direct array
      waveforms = data;
      hasMore = false;
      total = data.length;
    } else {
      // New format - paginated object
      waveforms = data.waveforms || [];
      hasMore = data.has_more || false;
      total = data.total || 0;
    }
    
    // Processed waveforms data
    
    // Get DOM elements
    const waveformsList = document.getElementById('waveformsList');
    const waveformsContainer = document.getElementById('waveformsContainer');
    
    if (!waveformsList || !waveformsContainer) {
      return;
    }
    
    // Display waveforms
    if (waveforms.length === 0 && offset === 0) {
      waveformsContainer.innerHTML = '<div class="muted" style="text-align: center; padding: 20px;">No MemoWindows yet. Create your first one above!</div>';
    } else if (waveforms.length > 0) {
      const waveformItems = waveforms.map(waveform => {
        const title = waveform.title || waveform.original_name || 'Untitled';
        const date = new Date(waveform.created_at).toLocaleDateString();
        const time = new Date(waveform.created_at).toLocaleTimeString();
        
        return `
          <div class="waveform-item" style="border: 1px solid #e6e9f2; border-radius: 8px; padding: 12px; margin-bottom: 8px; background: #fafbfc;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <strong style="color: #0b0d12;">${title}</strong>
                <div class="muted" style="font-size: 12px; margin-top: 2px;">
                  ${waveform.original_name} • ${date} ${time}
                </div>
              </div>
              <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button onclick="showMemoryModal('${waveform.image_url}', '${title.replace(/'/g, "\\'")}', '${waveform.qr_url}')" class="secondary" style="font-size: 12px; padding: 4px 8px; border: none; cursor: pointer;">View</button>
                <a href="${waveform.qr_url}" target="_blank" class="secondary" style="font-size: 12px; padding: 4px 8px;">QR</a>
                <button onclick="showOrderOptions(${waveform.id}, '${waveform.image_url}', '${title.replace(/'/g, "\\'")}', this)" style="background: #2a4df5; border: none; color: white; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">Order Print</button>
                <button onclick="deleteMemory(${waveform.id}, '${title.replace(/'/g, "\\'")}', this)" class="btn-delete" style="background: #dc3545; border: none; color: white; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">Delete</button>
              </div>
            </div>
          </div>
        `;
      }).join('');
      
      if (append) {
        // Append to existing content
        const loadMoreBtn = waveformsContainer.querySelector('#loadMoreBtn');
        if (loadMoreBtn) {
          loadMoreBtn.remove();
        }
        waveformsContainer.insertAdjacentHTML('beforeend', waveformItems);
      } else {
        // Replace content
        waveformsContainer.innerHTML = waveformItems;
      }
      
      // Add load more button if there are more memories
      if (hasMore) {
        const loadMoreBtn = document.createElement('div');
        loadMoreBtn.id = 'loadMoreBtn';
        loadMoreBtn.style.cssText = 'text-align: center; margin-top: 16px;';
        loadMoreBtn.innerHTML = `
          <button onclick="loadMoreMemories()" style="background: #6b7280; border: none; color: white; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 500;">
            Load More (${total - offset - waveforms.length} remaining)
          </button>
        `;
        waveformsContainer.appendChild(loadMoreBtn);
      }
    }
    
    waveformsList.classList.remove('hidden');
    waveformsList.style.display = 'block';
    
    // Store current offset for load more functionality
    window.currentWaveformsOffset = offset + waveforms.length;
    
  } catch (error) {
    console.error('Error loading waveforms:', error);
    const waveformsContainer = document.getElementById('waveformsContainer');
    const waveformsList = document.getElementById('waveformsList');
    
    if (waveformsContainer) {
      waveformsContainer.innerHTML = '<div class="muted" style="text-align: center; padding: 20px;">Error loading waveforms</div>';
    }
    if (waveformsList) {
      waveformsList.classList.remove('hidden');
    }
  }
}

// Get DOM elements
const getElements = () => ({
  // Login page elements
  btnLogin: document.getElementById('btnLogin'),
  emailInput: document.getElementById('emailInput'),
  passwordInput: document.getElementById('passwordInput'),
  btnEmailLogin: document.getElementById('btnEmailLogin'),
  btnEmailRegister: document.getElementById('btnEmailRegister'),
});

// Reset login button state
function resetLoginButton() {
  const els = getElements();
  if (els.btnLogin) {
    els.btnLogin.disabled = false;
    els.btnLogin.innerHTML = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
      Sign in with Google
    `;
  }
}

// Show user info - redirect to app page
async function showUserInfo(user) {
  // User authenticated, creating server-side session
  
  try {
    // Get Firebase ID token for server-side verification
    const idToken = await user.getIdToken();
    
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
    
    const result = await response.json();
    
    if (result.success) {
      console.log('✅ Server-side session created successfully');
      
      // Store user data in sessionStorage for the app page
      sessionStorage.setItem('currentUser', JSON.stringify({
        uid: user.uid,
        displayName: user.displayName,
        email: user.email,
        photoURL: user.photoURL
      }));
      
      // Redirect to the app page
      window.location.href = 'app.php';
    } else {
      console.error('❌ Failed to create server-side session:', result.error);
      // Still redirect to app, but session might not work
      window.location.href = 'app.php';
    }
  } catch (error) {
    console.error('❌ Error creating server-side session:', error);
    // Still redirect to app, but session might not work
    window.location.href = 'app.php';
  }
}

// Initialize authentication
export function initAuth() {
  // Initializing Firebase Auth
  const els = getElements();
  
  // Check if required elements exist
  if (!els.btnLogin) {
    // Retry after a short delay
    setTimeout(() => {
      // Retrying auth initialization
      initAuth();
    }, 1000);
    return;
  }
  
  // Auth elements found, setting up listeners
  
  // Mark button as initialized
  els.btnLogin.dataset.initialized = 'true';
  
  // Check for redirect result first
  getRedirectResult(auth).then((result) => {
    if (result) {
      // User just signed in via redirect
      console.log('✅ User signed in via redirect:', result.user);
      // The onAuthStateChanged listener will handle the UI update
    }
  }).catch((error) => {
    console.error('❌ Redirect result error:', error);
  });

  // Set up auth state listener
  onAuthStateChanged(auth, (user) => {
    // Auth state changed
    currentUser = user; // Update the currentUser variable
    if (user) {
      showUserInfo(user);
    } else {
      // User is logged out - stay on login page
      resetLoginButton();
    }
  });

  // Login button
  els.btnLogin.addEventListener('click', async () => {
    // Google login button clicked
    
    try {
      els.btnLogin.disabled = true;
      els.btnLogin.textContent = 'Signing in...';
      
      // Use redirect instead of popup for better reliability
      await signInWithRedirect(auth, googleProvider);
      // The page will redirect to Google, then back to our app
      
    } catch (error) {
      console.error('❌ Login failed:', error);
      console.error('❌ Error code:', error.code);
      console.error('❌ Error message:', error.message);
      Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: error.message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc2626'
      });
      resetLoginButton();
    }
  });


  // Email login button
  els.btnEmailLogin.addEventListener('click', async () => {
    try {
      const email = els.emailInput.value.trim();
      const password = els.passwordInput.value;
      
      if (!email || !password) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Information',
          text: 'Please enter both email and password',
          confirmButtonText: 'OK',
          confirmButtonColor: '#667eea'
        });
        return;
      }
      
      els.btnEmailLogin.disabled = true;
      els.btnEmailLogin.textContent = 'Signing in...';
      
      await signInWithEmailAndPassword(auth, email, password);
      
    } catch (error) {
      console.error('Email login failed:', error);
      let errorMessage = 'Login failed';
      
      if (error.code === 'auth/user-not-found') {
        errorMessage = 'No account found with this email';
      } else if (error.code === 'auth/wrong-password') {
        errorMessage = 'Incorrect password';
      } else if (error.code === 'auth/invalid-email') {
        errorMessage = 'Invalid email address';
      } else if (error.code === 'auth/too-many-requests') {
        errorMessage = 'Too many failed attempts. Please try again later';
      }
      
      Swal.fire({
        icon: 'error',
        title: 'Login Error',
        text: errorMessage,
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc2626'
      });
      
      els.btnEmailLogin.disabled = false;
      els.btnEmailLogin.textContent = 'Sign In';
    }
  });

  // Email register button
  els.btnEmailRegister.addEventListener('click', async () => {
    try {
      const email = els.emailInput.value.trim();
      const password = els.passwordInput.value;
      
      if (!email || !password) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Information',
          text: 'Please enter both email and password',
          confirmButtonText: 'OK',
          confirmButtonColor: '#667eea'
        });
        return;
      }
      
      if (password.length < 6) {
        Swal.fire({
          icon: 'warning',
          title: 'Password Too Short',
          text: 'Password must be at least 6 characters long',
          confirmButtonText: 'OK',
          confirmButtonColor: '#667eea'
        });
        return;
      }
      
      els.btnEmailRegister.disabled = true;
      els.btnEmailRegister.textContent = 'Creating account...';
      
      await createUserWithEmailAndPassword(auth, email, password);
      
    } catch (error) {
      console.error('Email registration failed:', error);
      let errorMessage = 'Registration failed';
      
      if (error.code === 'auth/email-already-in-use') {
        errorMessage = 'An account with this email already exists';
      } else if (error.code === 'auth/invalid-email') {
        errorMessage = 'Invalid email address';
      } else if (error.code === 'auth/weak-password') {
        errorMessage = 'Password is too weak. Please choose a stronger password';
      }
      
      Swal.fire({
        icon: 'error',
        title: 'Login Error',
        text: errorMessage,
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc2626'
      });
      
      els.btnEmailRegister.disabled = false;
      els.btnEmailRegister.textContent = 'Register';
    }
  });

  // Add Enter key support for email login
  els.passwordInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      els.btnEmailLogin.click();
    }
  });
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
    // Admin check failed
  }
}

// Get current user
export function getCurrentUser() {
  // Return the current user from auth state or the stored currentUser
  return currentUser || auth.currentUser;
}

// Export loadUserWaveforms function
export { loadUserWaveforms };
