/**
 * Utility functions for error handling, user feedback, and UI improvements
 */

/**
 * Show toast notification to user
 * @param {string} message - The message to display
 * @param {string} type - Type of toast ('success', 'error', 'warning', 'info')
 * @param {number} duration - Duration in milliseconds (default: 5000)
 */
export function showToast(message, type = 'info', duration = 5000) {
  const existingToasts = document.querySelectorAll('.toast');
  const toastContainer = getToastContainer();
  
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  const colors = {
    success: { bg: '#10b981', icon: '✓' },
    error: { bg: '#ef4444', icon: '✕' },
    warning: { bg: '#f59e0b', icon: '⚠' },
    info: { bg: '#3b82f6', icon: 'ℹ' }
  };
  
  const color = colors[type] || colors.info;
  
  toast.innerHTML = `
    <div class="toast-content">
      <span class="toast-icon">${color.icon}</span>
      <span class="toast-message">${message}</span>
      <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
    </div>
  `;
  
  toast.style.cssText = `
    background: ${color.bg};
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 400px;
    word-wrap: break-word;
  `;
  
  const contentStyle = `
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
  `;
  
  const closeStyle = `
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 18px;
    padding: 0;
    margin-left: 12px;
    opacity: 0.8;
  `;
  
  toast.querySelector('.toast-content').style.cssText = contentStyle;
  toast.querySelector('.toast-close').style.cssText = closeStyle;
  
  // Add hover effect for close button
  toast.querySelector('.toast-close').addEventListener('mouseenter', (e) => {
    e.target.style.opacity = '1';
  });
  
  toast.querySelector('.toast-close').addEventListener('mouseleave', (e) => {
    e.target.style.opacity = '0.8';
  });
  
  toastContainer.appendChild(toast);
  
  // Animate in
  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(0)';
  });
  
  // Auto remove
  if (duration > 0) {
    setTimeout(() => {
      if (toast && toast.parentNode) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
          if (toast && toast.parentNode) {
            toast.remove();
          }
        }, 300);
      }
    }, duration);
  }
  
  return toast;
}

/**
 * Get or create toast container
 */
function getToastContainer() {
  let container = document.getElementById('toast-container');
  
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 10000;
      pointer-events: none;
    `;
    
    // Make toast clickable
    container.addEventListener('click', (e) => {
      if (e.target.closest('.toast')) {
        e.target.closest('.toast').style.pointerEvents = 'auto';
      }
    });
    
    container.style.pointerEvents = 'none';
    container.querySelectorAll('.toast').forEach(toast => {
      toast.style.pointerEvents = 'auto';
    });
    
    document.body.appendChild(container);
  }
  
  return container;
}

/**
 * Show loading state on element
 * @param {HTMLElement} element - Element to show loading on
 * @param {string} text - Loading text (optional)
 */
export function showLoading(element, text = 'Loading...') {
  if (!element) return;
  
  element.classList.add('loading');
  element.dataset.originalText = element.textContent;
  element.textContent = text;
  element.disabled = true;
  
  // Add loading spinner
  if (!element.querySelector('.loading-spinner')) {
    const spinner = document.createElement('span');
    spinner.className = 'loading-spinner';
    spinner.style.cssText = `
      display: inline-block;
      width: 14px;
      height: 14px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top: 2px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-right: 8px;
    `;
    
    element.prepend(spinner);
  }
}

/**
 * Hide loading state from element
 * @param {HTMLElement} element - Element to hide loading from
 */
export function hideLoading(element) {
  if (!element) return;
  
  element.classList.remove('loading');
  element.disabled = false;
  
  const spinner = element.querySelector('.loading-spinner');
  if (spinner) {
    spinner.remove();
  }
  
  if (element.dataset.originalText) {
    element.textContent = element.dataset.originalText;
    delete element.dataset.originalText;
  }
}

/**
 * Show confirmation dialog
 * @param {string} message - Confirmation message
 * @param {string} title - Dialog title (optional)
 * @returns {Promise<boolean>} - True if confirmed, false if cancelled
 */
export function showConfirmDialog(message, title = 'Confirm Action') {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10001;
      opacity: 0;
      transition: opacity 0.3s ease;
    `;
    
    const dialog = document.createElement('div');
    dialog.className = 'confirm-dialog';
    dialog.style.cssText = `
      background: white;
      padding: 24px;
      border-radius: 12px;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      transform: scale(0.9);
      transition: transform 0.3s ease;
    `;
    
    dialog.innerHTML = `
      <h3 style="margin: 0 0 16px 0; color: #0b0d12; font-size: 18px;">${title}</h3>
      <p style="margin: 0 0 24px 0; color: #6b7280; line-height: 1.5;">${message}</p>
      <div style="display: flex; gap: 12px; justify-content: center;">
        <button id="confirm-cancel" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
          Cancel
        </button>
        <button id="confirm-ok" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
          Confirm
        </button>
      </div>
    `;
    
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    
    // Animate in
    requestAnimationFrame(() => {
      overlay.style.opacity = '1';
      dialog.style.transform = 'scale(1)';
    });
    
    const cleanup = () => {
      overlay.style.opacity = '0';
      dialog.style.transform = 'scale(0.9)';
      setTimeout(() => overlay.remove(), 300);
    };
    
    // Handle buttons
    dialog.querySelector('#confirm-cancel').addEventListener('click', () => {
      cleanup();
      resolve(false);
    });
    
    dialog.querySelector('#confirm-ok').addEventListener('click', () => {
      cleanup();
      resolve(true);
    });
    
    // Handle escape key
    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        cleanup();
        resolve(false);
        document.removeEventListener('keydown', handleEscape);
      }
    };
    
    document.addEventListener('keydown', handleEscape);
    
    // Handle overlay click
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        cleanup();
        resolve(false);
      }
    });
  });
}

/**
 * Handle and log errors consistently
 * @param {Error} error - The error object
 * @param {string} context - Context where error occurred
 * @param {boolean} showToUser - Whether to show error to user
 */
export function handleError(error, context = 'Unknown', showToUser = true) {
  console.error(`Error in ${context}:`, error);
  
  // Log to external service if configured
  if (typeof gtag !== 'undefined') {
    gtag('event', 'exception', {
      description: `${context}: ${error.message}`,
      fatal: false
    });
  }
  
  if (showToUser) {
    const userMessage = getFriendlyErrorMessage(error, context);
    showToast(userMessage, 'error');
  }
}

/**
 * Convert technical errors to user-friendly messages
 * @param {Error} error - The error object
 * @param {string} context - Context where error occurred
 * @returns {string} - User-friendly error message
 */
function getFriendlyErrorMessage(error, context) {
  const message = error.message?.toLowerCase() || '';
  
  // Network errors
  if (message.includes('network') || message.includes('fetch')) {
    return 'Unable to connect to the server. Please check your internet connection and try again.';
  }
  
  // Authentication errors
  if (message.includes('auth') || message.includes('permission')) {
    return 'Authentication failed. Please sign in again.';
  }
  
  // File upload errors
  if (context.includes('upload') || message.includes('file')) {
    return 'File upload failed. Please check the file format and try again.';
  }
  
  // Payment errors
  if (context.includes('payment') || message.includes('stripe')) {
    return 'Payment processing failed. Please check your payment details and try again.';
  }
  
  // Default error message
  return 'Something went wrong. Please try again later.';
}

/**
 * Debounce function to limit rapid function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
export function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Throttle function to limit function calls to once per interval
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} - Throttled function
 */
export function throttle(func, limit) {
  let inThrottle;
  return function(...args) {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

/**
 * Format file size for display
 * @param {number} bytes - File size in bytes
 * @returns {string} - Formatted file size
 */
export function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Validate file type
 * @param {File} file - File to validate
 * @param {string[]} allowedTypes - Array of allowed MIME types
 * @returns {boolean} - True if valid, false otherwise
 */
export function validateFileType(file, allowedTypes) {
  return allowedTypes.includes(file.type);
}

/**
 * Validate file size
 * @param {File} file - File to validate
 * @param {number} maxSizeBytes - Maximum size in bytes
 * @returns {boolean} - True if valid, false otherwise
 */
export function validateFileSize(file, maxSizeBytes) {
  return file.size <= maxSizeBytes;
}

/**
 * Add CSS animation keyframes if not already added
 */
function addAnimationStyles() {
  if (!document.getElementById('utils-styles')) {
    const style = document.createElement('style');
    style.id = 'utils-styles';
    style.textContent = `
      @keyframes spin {
        to { transform: rotate(360deg); }
      }
      
      .loading {
        position: relative;
        opacity: 0.7;
        pointer-events: none;
      }
    `;
    document.head.appendChild(style);
  }
}

// Initialize styles when module loads
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', addAnimationStyles);
} else {
  addAnimationStyles();
}
