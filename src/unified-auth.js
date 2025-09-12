/**
 * Unified Authentication System - Client Side
 * Handles all authentication for both frontend and admin
 */

import { signInWithPopup, signOut as firebaseSignOut, onAuthStateChanged, createUserWithEmailAndPassword, signInWithEmailAndPassword } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { auth, googleProvider, emailProvider } from '../firebase-config.php';

class UnifiedAuth {
    constructor() {
        this.currentUser = null;
        this.isAdmin = false;
        this.authListeners = [];
        this.isInitialized = false;
        
        console.log('🔐 Initializing Unified Authentication...');
        this.init();
    }

    /**
     * Initialize the unified authentication system
     */
    async init() {
        try {
            // Set up Firebase auth state listener
            this.setupFirebaseAuthListener();
            
            // Check current authentication status with server
            await this.checkAuthStatus();
            
            this.isInitialized = true;
            console.log('✅ Unified Authentication initialized successfully');
            
        } catch (error) {
            console.error('❌ Failed to initialize Unified Authentication:', error);
        }
    }

    /**
     * Set up Firebase authentication state listener
     */
    setupFirebaseAuthListener() {
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                console.log('✅ User authenticated:', user.email);
                await this.authenticateWithServer(user);
            } else {
                console.log('ℹ️ User not authenticated (normal for login page)');
                this.currentUser = null;
                this.isAdmin = false;
                this.notifyListeners();
            }
        });
    }

    /**
     * Authenticate user with server using Firebase ID token
     */
    async authenticateWithServer(user) {
        try {
            const idToken = await user.getIdToken();
            
            const response = await fetch('unified_auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include', // Include cookies for session management
                body: JSON.stringify({
                    idToken: idToken
                })
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.currentUser = data.user;
                this.isAdmin = data.isAdmin;
                console.log('✅ Server authentication successful');
                this.notifyListeners();
            } else {
                throw new Error(data.error || 'Authentication failed');
            }
            
        } catch (error) {
            console.error('❌ Server authentication failed:', error);
            this.currentUser = null;
            this.isAdmin = false;
            this.notifyListeners();
        }
    }

    /**
     * Check current authentication status with server
     */
    async checkAuthStatus() {
        try {
            const response = await fetch('unified_auth.php', {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.currentUser = data.user;
                    this.isAdmin = data.isAdmin;
                    console.log('✅ Current auth status retrieved');
                    this.notifyListeners();
                }
            }
        } catch (error) {
            console.log('ℹ️ No current authentication (normal for new users)');
        }
    }

    /**
     * Sign in with Google
     */
    async signInWithGoogle() {
        try {
            const result = await signInWithPopup(auth, googleProvider);
            return result.user;
        } catch (error) {
            console.error('❌ Google sign-in failed:', error);
            throw error;
        }
    }

    /**
     * Sign in with email and password
     */
    async signInWithEmail(email, password) {
        try {
            const result = await signInWithEmailAndPassword(auth, emailProvider, email, password);
            return result.user;
        } catch (error) {
            console.error('❌ Email sign-in failed:', error);
            throw error;
        }
    }

    /**
     * Create account with email and password
     */
    async createAccount(email, password) {
        try {
            const result = await createUserWithEmailAndPassword(auth, emailProvider, email, password);
            return result.user;
        } catch (error) {
            console.error('❌ Account creation failed:', error);
            throw error;
        }
    }

    /**
     * Sign out user
     */
    async signOut() {
        try {
            // Sign out from Firebase
            await firebaseSignOut(auth);
            
            // Also sign out from server
            await fetch('unified_auth.php', {
                method: 'DELETE',
                credentials: 'include'
            });
            
            this.currentUser = null;
            this.isAdmin = false;
            this.notifyListeners();
            
            console.log('✅ User signed out successfully');
            
        } catch (error) {
            console.error('❌ Sign out failed:', error);
            throw error;
        }
    }

    /**
     * Get current authenticated user
     */
    getCurrentUser() {
        return this.currentUser;
    }

    /**
     * Check if current user is admin
     */
    isCurrentUserAdmin() {
        return this.isAdmin;
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return this.currentUser !== null;
    }

    /**
     * Add authentication state listener
     */
    addAuthListener(callback) {
        this.authListeners.push(callback);
        
        // If user is already authenticated, call the callback immediately
        if (this.isAuthenticated()) {
            callback(this.currentUser, this.isAdmin);
        }
    }

    /**
     * Remove authentication state listener
     */
    removeAuthListener(callback) {
        const index = this.authListeners.indexOf(callback);
        if (index > -1) {
            this.authListeners.splice(index, 1);
        }
    }

    /**
     * Notify all listeners of authentication state change
     */
    notifyListeners() {
        // Automatically show/hide user info in navigation
        if (this.currentUser) {
            this.showUserInfo(this.currentUser);
            this.setupOrdersLink();
        } else {
            this.hideUserInfo();
        }
        
        // Call registered listeners
        this.authListeners.forEach(callback => {
            try {
                callback(this.currentUser, this.isAdmin);
            } catch (error) {
                console.error('❌ Error in auth listener:', error);
            }
        });
    }

    /**
     * Wait for authentication to be ready
     */
    async waitForAuth() {
        return new Promise((resolve) => {
            if (this.isInitialized) {
                resolve();
                return;
            }
            
            const checkReady = () => {
                if (this.isInitialized) {
                    resolve();
                } else {
                    setTimeout(checkReady, 100);
                }
            };
            checkReady();
        });
    }

    /**
     * Get user's waveforms (for memories page)
     */
    async loadUserWaveforms(offset = 0, append = false) {
        if (!this.isAuthenticated()) {
            throw new Error('User not authenticated');
        }

        try {
            const response = await fetch(`get_waveforms.php?offset=${offset}`, {
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`API request failed with status ${response.status}`);
            }

            const data = await response.json();
            return data;
            
        } catch (error) {
            console.error('❌ Error loading waveforms:', error);
            throw error;
        }
    }

    /**
     * Check admin status (for navigation)
     */
    async checkAdminStatus() {
        if (!this.isAuthenticated()) {
            console.log('🔍 User not authenticated, cannot check admin status');
            return false;
        }

        try {
            console.log('🔍 Checking admin status...');
            const response = await fetch('check_admin.php', {
                credentials: 'include'
            });

            console.log('🔍 Admin status response:', response.status);
            if (response.ok) {
                const data = await response.json();
                console.log('🔍 Admin status data:', data);
                this.isAdmin = data.is_admin;
                return data.is_admin;
            }
            
            console.log('🔍 Admin status response not ok:', response.status);
            return false;
            
        } catch (error) {
            console.error('❌ Error checking admin status:', error);
            return false;
        }
    }

    /**
     * Show user info in navigation (for app-auth functionality)
     */
    showUserInfo(user) {
        const els = this.getElements();
        console.log('🔍 showUserInfo called with user:', user);
        console.log('🔍 Found elements:', els);
        
        if (!els.userInfo) {
            console.error('❌ userInfo element not found');
            return;
        }
        
        console.log('✅ Showing user info in navigation');
        els.userInfo.classList.remove('hidden');
        
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        if (mobileMenuToggle) {
            mobileMenuToggle.classList.remove('hidden');
            console.log('✅ Hamburger menu button shown for logged-in user');
        }

        if (els.userName) {
            els.userName.textContent = user.displayName || user.email || 'User';
        }

        if (els.userEmail) {
            els.userEmail.textContent = user.email || '';
        }

        if (els.userAvatar) {
            if (user.photoURL) {
                els.userAvatar.src = user.photoURL;
                els.userAvatar.style.display = 'block';
            } else {
                els.userAvatar.style.display = 'none';
            }
        }

        // Check admin status
        this.checkAdminStatus().then(isAdmin => {
            console.log('🔍 Admin status check result:', isAdmin);
            console.log('🔍 Admin button element found:', els.adminButton);
            if (isAdmin && els.adminButton) {
                els.adminButton.style.display = 'block';
                console.log('✅ Admin button shown');
            } else if (isAdmin && !els.adminButton) {
                console.error('❌ Admin button element not found in DOM');
            } else if (!isAdmin) {
                console.log('ℹ️ User is not admin, admin button hidden');
            }
        });
    }

    /**
     * Hide user info in navigation
     */
    hideUserInfo() {
        const els = this.getElements();
        
        if (els.userInfo) {
            els.userInfo.classList.add('hidden');
        }

        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        if (mobileMenuToggle) {
            mobileMenuToggle.classList.add('hidden');
        }

        if (els.adminButton) {
            els.adminButton.style.display = 'none';
        }
    }

    /**
     * Get DOM elements for navigation
     */
    getElements() {
        return {
            userInfo: document.querySelector('#userInfo') || document.querySelector('.user-info'),
            userName: document.querySelector('#userName') || document.querySelector('.user-name'),
            userEmail: document.querySelector('#userEmail') || document.querySelector('.user-email'),
            userAvatar: document.querySelector('#userAvatar') || document.querySelector('.user-avatar'),
            adminButton: document.querySelector('#adminButton') || document.querySelector('.admin-button'),
            ordersLink: document.querySelector('#ordersLink') || document.querySelector('.orders-link')
        };
    }

    /**
     * Set up orders link (for navigation)
     */
    setupOrdersLink() {
        const els = this.getElements();
        if (els.ordersLink) {
            els.ordersLink.href = 'orders.php';
            console.log('✅ Orders link set up without user_id parameter');
        }
    }
}

// Create global instance
const unifiedAuth = new UnifiedAuth();

// Export for use in other modules
export default unifiedAuth;

// Also make available globally for inline scripts
window.unifiedAuth = unifiedAuth;

// Export individual functions for compatibility
export const {
    getCurrentUser,
    isCurrentUserAdmin,
    isAuthenticated,
    signInWithGoogle,
    signInWithEmail,
    createAccount,
    signOut,
    addAuthListener,
    removeAuthListener,
    waitForAuth,
    loadUserWaveforms,
    checkAdminStatus,
    showUserInfo,
    hideUserInfo,
    setupOrdersLink
} = unifiedAuth;
