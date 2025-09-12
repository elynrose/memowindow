/**
 * Unified Authentication Client for MemoWindow
 * Handles authentication for both frontend and admin pages
 */

class UnifiedAuth {
    constructor() {
        this.currentUser = null;
        this.isAdmin = false;
        this.authListeners = [];
        this.init();
    }

    /**
     * Initialize the authentication system
     */
    async init() {
        console.log('🔐 Initializing Unified Authentication...');
        
        // Check if user is already authenticated
        await this.checkAuthStatus();
        
        // Set up Firebase auth state listener
        this.setupFirebaseAuthListener();
        
        // Set up navigation based on auth status
        this.updateNavigation();
    }

    /**
     * Check current authentication status from server
     */
    async checkAuthStatus() {
        try {
            const response = await fetch('unified_auth.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentUser = data.user;
                this.isAdmin = data.isAdmin;
                this.notifyListeners();
                console.log('✅ User authenticated:', this.currentUser.email);
            } else {
                this.currentUser = null;
                this.isAdmin = false;
                this.notifyListeners();
                console.log('❌ User not authenticated');
            }
        } catch (error) {
            console.error('Error checking auth status:', error);
            this.currentUser = null;
            this.isAdmin = false;
            this.notifyListeners();
        }
    }

    /**
     * Set up Firebase authentication state listener
     */
    setupFirebaseAuthListener() {
        // Import Firebase auth dynamically
        import('https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js').then(({ onAuthStateChanged }) => {
            // Get auth instance from firebase-config.php
            import('../firebase-config.php').then(({ auth }) => {
                onAuthStateChanged(auth, async (user) => {
                    if (user) {
                        // User signed in, get ID token and authenticate with server
                        const idToken = await user.getIdToken();
                        await this.authenticateWithServer(idToken);
                    } else {
                        // User signed out
                        await this.logout();
                    }
                });
            });
        }).catch(error => {
            console.error('Error setting up Firebase auth listener:', error);
        });
    }

    /**
     * Authenticate with server using Firebase ID token
     */
    async authenticateWithServer(idToken) {
        try {
            const response = await fetch('unified_auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ idToken })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentUser = data.user;
                this.isAdmin = data.isAdmin;
                this.notifyListeners();
                this.updateNavigation();
                console.log('✅ Server authentication successful');
            } else {
                console.error('❌ Server authentication failed:', data.error);
                await this.logout();
            }
        } catch (error) {
            console.error('Error authenticating with server:', error);
            await this.logout();
        }
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            // Logout from server
            await fetch('unified_auth.php', {
                method: 'DELETE',
                credentials: 'include'
            });
            
            // Logout from Firebase
            const { auth } = await import('../firebase-config.php');
            const { signOut } = await import('https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js');
            await signOut(auth);
            
            // Clear local state
            this.currentUser = null;
            this.isAdmin = false;
            this.notifyListeners();
            this.updateNavigation();
            
            // Clear session storage
            sessionStorage.removeItem('currentUser');
            localStorage.removeItem('currentUser');
            
            console.log('✅ User logged out successfully');
            
            // Redirect to login page
            window.location.href = 'login.php';
            
        } catch (error) {
            console.error('Error during logout:', error);
            // Force redirect even if logout fails
            window.location.href = 'login.php';
        }
    }

    /**
     * Update navigation based on authentication status
     */
    updateNavigation() {
        const userInfo = document.getElementById('userInfo');
        const ordersLink = document.getElementById('ordersLink');
        const adminLink = document.querySelector('.admin-link');
        
        if (this.currentUser) {
            // Show user info
            if (userInfo) {
                userInfo.classList.remove('hidden');
            }
            
            // Update user name and avatar
            const userName = document.getElementById('userName');
            const userAvatar = document.getElementById('userAvatar');
            
            if (userName) {
                userName.textContent = this.currentUser.display_name || this.currentUser.email || 'User';
            }
            
            if (userAvatar) {
                userAvatar.src = this.currentUser.photo_url || this.getDefaultAvatar();
                userAvatar.alt = this.currentUser.display_name || 'User avatar';
            }
            
            // Update mobile menu user name
            const mobileUserName = document.getElementById('mobile-user-name');
            if (mobileUserName) {
                mobileUserName.textContent = this.currentUser.display_name || this.currentUser.email || 'User';
            }
            
            // Set orders link (no user_id parameter needed)
            if (ordersLink) {
                ordersLink.href = 'orders.php';
            }
            
            // Add admin link if user is admin
            if (this.isAdmin && !adminLink) {
                this.addAdminLink();
            } else if (!this.isAdmin && adminLink) {
                adminLink.remove();
            }
            
            // Show hamburger menu button
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            if (mobileMenuToggle) {
                mobileMenuToggle.classList.remove('hidden');
            }
            
        } else {
            // Hide user info
            if (userInfo) {
                userInfo.classList.add('hidden');
            }
            
            // Hide hamburger menu button
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            if (mobileMenuToggle) {
                mobileMenuToggle.classList.add('hidden');
            }
            
            // Remove admin link
            if (adminLink) {
                adminLink.remove();
            }
        }
    }

    /**
     * Add admin link to navigation
     */
    addAdminLink() {
        const userInfo = document.getElementById('userInfo');
        if (!userInfo) return;
        
        const adminLink = document.createElement('a');
        adminLink.href = 'admin.php'; // No user_id parameter needed
        adminLink.className = 'admin-link';
        adminLink.style.cssText = 'background: #dc2626; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; margin-right: 8px;';
        adminLink.textContent = 'Admin';
        
        // Insert before user profile
        const userProfile = userInfo.querySelector('.user-profile');
        if (userProfile) {
            userInfo.insertBefore(adminLink, userProfile);
        } else {
            userInfo.appendChild(adminLink);
        }
    }

    /**
     * Get default avatar SVG
     */
    getDefaultAvatar() {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgiIGhlaWdodD0iMjgiIHZpZXdCb3g9IjAgMCAyOCAyOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTQiIGN5PSIxNCIgcj0iMTQiIGZpbGw9IiM2NjdlZWEiLz4KPHN2ZyB4PSI4IiB5PSI4IiB3aWR0aD0iMTIiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIgMTRDOC42ODYyOSAxNCA2IDE2LjY4NjMgNiAyMEgyMEMyMCAxNi42ODYzIDE3LjMxMzcgMTQgMTQgMTRIOloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPgo8L3N2Zz4K';
    }

    /**
     * Add authentication state listener
     */
    addAuthListener(callback) {
        this.authListeners.push(callback);
    }

    /**
     * Notify all authentication listeners
     */
    notifyListeners() {
        this.authListeners.forEach(callback => {
            try {
                callback(this.currentUser, this.isAdmin);
            } catch (error) {
                console.error('Error in auth listener:', error);
            }
        });
    }

    /**
     * Get current user
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
}

// Create global instance
window.unifiedAuth = new UnifiedAuth();

// Export for module usage
export default window.unifiedAuth;
