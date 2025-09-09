// Progressive Web App functionality
export class PWAManager {
  constructor() {
    this.installPrompt = null;
    this.isOnline = navigator.onLine;
    this.init();
  }

  async init() {
    await this.registerServiceWorker();
    this.setupInstallPrompt();
    this.setupOnlineOfflineHandlers();
    this.setupPerformanceMonitoring();
  }

  /**
   * Register service worker for PWA functionality
   */
  async registerServiceWorker() {
    if ('serviceWorker' in navigator) {
      try {
        const registration = await navigator.serviceWorker.register('/sw.js');
        
        console.log('Service Worker registered successfully:', registration);

        // Handle service worker updates
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          
          if (newWorker) {
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New version available
                this.showUpdateAvailable();
              }
            });
          }
        });

        // Listen for messages from service worker
        navigator.serviceWorker.addEventListener('message', event => {
          if (event.data && event.data.type === 'BACK_ONLINE') {
            this.handleBackOnline();
          }
        });

      } catch (error) {
        console.error('Service Worker registration failed:', error);
      }
    }
  }

  /**
   * Setup PWA install prompt
   */
  setupInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (e) => {
      console.log('PWA: Install prompt available');
      e.preventDefault();
      this.installPrompt = e;
      this.showInstallButton();
    });

    window.addEventListener('appinstalled', () => {
      console.log('PWA: App was installed');
      this.hideInstallButton();
      this.trackEvent('pwa_installed');
    });
  }

  /**
   * Show install button when PWA can be installed
   */
  showInstallButton() {
    const installBtn = document.createElement('button');
    installBtn.id = 'pwa-install-btn';
    installBtn.innerHTML = `
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
        <path d="M19,18H6V6H19M21,4H3A2,2 0 0,0 1,6V18A2,2 0 0,0 3,20H21A2,2 0 0,0 23,18V6A2,2 0 0,0 21,4Z"/>
        <path d="M12,8L8,12H11V16H13V12H16L12,8Z"/>
      </svg>
      Install App
    `;
    installBtn.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #2a4df5;
      color: white;
      border: none;
      padding: 12px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(42, 77, 245, 0.3);
      z-index: 1000;
      transition: transform 0.2s ease;
    `;

    installBtn.addEventListener('mouseenter', () => {
      installBtn.style.transform = 'scale(1.05)';
    });

    installBtn.addEventListener('mouseleave', () => {
      installBtn.style.transform = 'scale(1)';
    });

    installBtn.addEventListener('click', () => this.installPWA());

    // Only show if not already installed
    if (!this.isPWAInstalled()) {
      document.body.appendChild(installBtn);
    }
  }

  /**
   * Hide install button
   */
  hideInstallButton() {
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
      installBtn.remove();
    }
  }

  /**
   * Install PWA when user clicks install button
   */
  async installPWA() {
    if (!this.installPrompt) return;

    try {
      const result = await this.installPrompt.prompt();
      console.log('PWA: Install prompt result:', result.outcome);
      
      if (result.outcome === 'accepted') {
        this.trackEvent('pwa_install_accepted');
      } else {
        this.trackEvent('pwa_install_dismissed');
      }
      
      this.installPrompt = null;
      this.hideInstallButton();
    } catch (error) {
      console.error('PWA: Install failed:', error);
    }
  }

  /**
   * Check if PWA is already installed
   */
  isPWAInstalled() {
    // Check if running in standalone mode
    if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
      return true;
    }
    
    // Check if launched from home screen on iOS
    if (window.navigator.standalone === true) {
      return true;
    }

    return false;
  }

  /**
   * Setup online/offline status handlers
   */
  setupOnlineOfflineHandlers() {
    window.addEventListener('online', () => {
      console.log('PWA: Back online');
      this.isOnline = true;
      this.showOnlineStatus();
      this.handleBackOnline();
    });

    window.addEventListener('offline', () => {
      console.log('PWA: Gone offline');
      this.isOnline = false;
      this.showOfflineStatus();
    });

    // Initial status
    if (!this.isOnline) {
      this.showOfflineStatus();
    }
  }

  /**
   * Show online status indicator
   */
  showOnlineStatus() {
    const statusEl = document.getElementById('connection-status');
    if (statusEl) {
      statusEl.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #10b981;
        color: white;
        text-align: center;
        padding: 8px;
        font-size: 14px;
        z-index: 1001;
        transform: translateY(-100%);
        transition: transform 0.3s ease;
      `;
      statusEl.textContent = '✓ Back online';
      statusEl.style.transform = 'translateY(0)';
      
      setTimeout(() => {
        if (statusEl) {
          statusEl.style.transform = 'translateY(-100%)';
          setTimeout(() => statusEl.remove(), 300);
        }
      }, 3000);
    }
  }

  /**
   * Show offline status indicator
   */
  showOfflineStatus() {
    let statusEl = document.getElementById('connection-status');
    if (!statusEl) {
      statusEl = document.createElement('div');
      statusEl.id = 'connection-status';
      document.body.appendChild(statusEl);
    }

    statusEl.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: #dc2626;
      color: white;
      text-align: center;
      padding: 8px;
      font-size: 14px;
      z-index: 1001;
    `;
    statusEl.textContent = '⚠️ You are offline. Some features may not work.';
  }

  /**
   * Handle returning online
   */
  handleBackOnline() {
    // Refresh data if needed
    if (window.loadUserWaveforms && typeof window.loadUserWaveforms === 'function') {
      window.loadUserWaveforms();
    }

    // Show success message
    this.showOnlineStatus();
  }

  /**
   * Show update available notification
   */
  showUpdateAvailable() {
    const updateBar = document.createElement('div');
    updateBar.style.cssText = `
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #2a4df5;
      color: white;
      padding: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 1001;
    `;
    
    updateBar.innerHTML = `
      <span>🚀 A new version is available!</span>
      <div>
        <button id="update-dismiss" style="background: transparent; color: white; border: 1px solid white; padding: 8px 12px; border-radius: 4px; margin-right: 8px; cursor: pointer;">
          Later
        </button>
        <button id="update-reload" style="background: white; color: #2a4df5; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 500; cursor: pointer;">
          Update Now
        </button>
      </div>
    `;

    document.body.appendChild(updateBar);

    // Handle update actions
    document.getElementById('update-dismiss').addEventListener('click', () => {
      updateBar.remove();
    });

    document.getElementById('update-reload').addEventListener('click', () => {
      window.location.reload();
    });
  }

  /**
   * Setup basic performance monitoring
   */
  setupPerformanceMonitoring() {
    // Monitor page load performance
    window.addEventListener('load', () => {
      if ('performance' in window) {
        const perfData = performance.getEntriesByType('navigation')[0];
        if (perfData) {
          const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
          console.log(`PWA: Page load time: ${loadTime}ms`);
          this.trackPerformance('page_load_time', loadTime);
        }
      }
    });

    // Monitor resource loading
    const observer = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      entries.forEach(entry => {
        if (entry.duration > 1000) { // Resources taking over 1 second
          console.warn(`PWA: Slow resource: ${entry.name} (${entry.duration}ms)`);
        }
      });
    });

    try {
      observer.observe({ entryTypes: ['resource'] });
    } catch (error) {
      console.log('PWA: Performance Observer not supported');
    }
  }

  /**
   * Track PWA events
   */
  trackEvent(eventName, data = {}) {
    console.log(`PWA Event: ${eventName}`, data);
    
    // You can integrate with analytics services here
    if (typeof gtag !== 'undefined') {
      gtag('event', eventName, data);
    }
  }

  /**
   * Track performance metrics
   */
  trackPerformance(metric, value) {
    console.log(`PWA Performance: ${metric} = ${value}`);
    
    // You can send to analytics or monitoring service
    if (typeof gtag !== 'undefined') {
      gtag('event', 'performance', {
        metric_name: metric,
        metric_value: value
      });
    }
  }
}

// Initialize PWA functionality when DOM is loaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    new PWAManager();
  });
} else {
  new PWAManager();
}
