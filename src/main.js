// Main application entry point
import './styles.css';
import './pwa.js';
import './globals.js'; // Global function definitions for HTML compatibility
import unifiedAuth from './unified-auth.js';
import { uploadWaveformFiles, deleteMemoryFiles } from './storage.js';
import { showToast, showLoading, hideLoading, showConfirmDialog, handleError, formatFileSize, validateFileType, validateFileSize } from './utils.js';

// Initialize authentication when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  // DOM loaded, initializing app
  
  try {
    // Unified auth is auto-initialized, no need to call initAuth
    console.log('✅ App initialization started');
  } catch (error) {
    console.error('❌ Auth initialization failed:', error);
    
    // Show error to user
    setTimeout(() => {
      const errorDiv = document.createElement('div');
      errorDiv.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; 
        background: #dc2626; color: white; padding: 16px; 
        text-align: center; z-index: 10000;
      `;
      errorDiv.textContent = '⚠️ App initialization failed: ' + error.message;
      document.body.appendChild(errorDiv);
    }, 1000);
  }
});

// Also try to initialize when window loads (backup)
window.addEventListener('load', () => {
  // Window loaded, checking if auth is initialized
  
  // Unified auth is auto-initialized, no need for retry logic
  console.log('✅ App fully loaded');
});

// Make functions available globally for the existing waveform code
window.getCurrentUser = () => unifiedAuth.getCurrentUser();
window.uploadWaveformFiles = uploadWaveformFiles;
window.deleteMemoryFiles = deleteMemoryFiles;

// Make utility functions available globally
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.showConfirmDialog = showConfirmDialog;
window.handleError = handleError;
window.formatFileSize = formatFileSize;
window.validateFileType = validateFileType;
window.validateFileSize = validateFileSize;
