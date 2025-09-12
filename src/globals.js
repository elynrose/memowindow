/**
 * Global function exports for inline HTML JavaScript compatibility
 * This file ensures all functions called by onclick handlers are available globally
 */

// Import necessary functions from auth module
import unifiedAuth from './unified-auth.js';

// Re-export functions that need to be globally available
// These functions are defined in the main HTML file but need to be accessible

// Function to delete a memory (this needs to be properly implemented)
window.deleteMemory = async function(memoryId, title, buttonElement) {
  if (!window.showConfirmDialog) {
    alert('Utility functions not loaded');
    return;
  }
  
  const confirmed = await window.showConfirmDialog(
    `Are you sure you want to delete "${title}"? This action cannot be undone.`,
    'Delete Memory'
  );
  
  if (!confirmed) return;
  
  const originalText = buttonElement.textContent;
  buttonElement.textContent = 'Deleting...';
  buttonElement.disabled = true;
  
  try {
    const currentUser = window.unifiedAuth ? window.unifiedAuth.getCurrentUser() : null;
    if (!currentUser) {
      throw new Error('Not authenticated');
    }
    
    // Call the server to delete the memory
    const response = await fetch('delete_memory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `memory_id=${memoryId}`,
      credentials: 'include' // Include session cookies for authentication
    });
    
    if (!response.ok) {
      throw new Error('Delete request failed');
    }
    
    const result = await response.json();
    
    if (result.success) {
      // Remove the memory from the UI
      const memoryItem = buttonElement.closest('.waveform-item');
      if (memoryItem) {
        memoryItem.remove();
      }
      
      if (window.showToast) {
        window.showToast(`"${title}" has been deleted.`, 'success');
      }
      
      // Also delete the files from storage if available
      if (window.deleteMemoryFiles && result.files) {
        try {
          await window.deleteMemoryFiles(result.files);
        } catch (storageError) {
        }
      }
    } else {
      throw new Error(result.error || 'Delete failed');
    }
    
  } catch (error) {
    if (window.handleError) {
      window.handleError(error, 'Delete Memory');
    } else {
      alert('Error deleting memory: ' + error.message);
    }
  } finally {
    buttonElement.textContent = originalText;
    buttonElement.disabled = false;
  }
};

// Load user waveforms wrapper - function defined in memories.js
// window.loadUserWaveforms = authLoadUserWaveforms; // Removed - function not available

// Function to show memory modal (defined in main HTML script)
window.showMemoryModal = function(imageUrl, title, qrUrl) {
  // Create modal overlay
  const modal = document.createElement('div');
  modal.className = 'image-modal';
  modal.onclick = (e) => {
    if (e.target === modal) window.closeImageModal();
  };
  
  modal.innerHTML = `
    <div class="image-modal-content">
      <button class="image-modal-close" onclick="closeImageModal()" title="Close">&times;</button>
      <div style="text-align: center; margin-bottom: 16px;">
        <h3 style="color: white; margin: 0 0 8px 0;">${title}</h3>
        <a href="${qrUrl}" target="_blank" style="color: #60a5fa; text-decoration: none; font-size: 14px;">🎵 Play Audio</a>
      </div>
      <img src="${imageUrl}" alt="Complete Memory Frame" style="max-width: 100%; max-height: 100%;">
    </div>
  `;
  
  document.body.appendChild(modal);
  window.currentImageModal = modal;
  
  // Add keyboard support
  document.addEventListener('keydown', window.handleImageModalKeydown);
};

// Function to close image modal
window.closeImageModal = function() {
  if (window.currentImageModal) {
    window.currentImageModal.remove();
    window.currentImageModal = null;
    document.removeEventListener('keydown', window.handleImageModalKeydown);
  }
};

// Handle keyboard events for image modal
window.handleImageModalKeydown = function(e) {
  if (e.key === 'Escape') {
    window.closeImageModal();
  }
};

// Function to show image modal (from main HTML)
window.showImageModal = function() {
  const imageLink = document.getElementById('imageLink');
  const imageUrl = imageLink ? imageLink.href : null;
  
  if (!imageUrl || imageUrl === '#') {
    if (window.showToast) {
      window.showToast('No image available to display', 'warning');
    } else {
      alert('No image available to display');
    }
    return;
  }
  
  // Create modal overlay
  const modal = document.createElement('div');
  modal.className = 'image-modal';
  modal.onclick = (e) => {
    if (e.target === modal) window.closeImageModal();
  };
  
  modal.innerHTML = `
    <div class="image-modal-content">
      <button class="image-modal-close" onclick="closeImageModal()" title="Close">&times;</button>
      <img src="${imageUrl}" alt="Complete Memory Frame" style="max-width: 100%; max-height: 100%;">
    </div>
  `;
  
  document.body.appendChild(modal);
  window.currentImageModal = modal;
  
  // Add keyboard support
  document.addEventListener('keydown', window.handleImageModalKeydown);
};

// Load more memories function
window.loadMoreMemories = function() {
  const offset = window.currentWaveformsOffset || 0;
  // Load more clicked
  
  const currentUser = window.unifiedAuth ? window.unifiedAuth.getCurrentUser() : null;
  if (!currentUser) {
    // No user for load more
    return;
  }
  
  window.loadMoreWaveforms(offset);
};

// Function to load additional waveforms (defined in HTML script)
window.loadMoreWaveforms = async function(offset) {
  try {
    // Loading more waveforms
    const currentUser = window.unifiedAuth ? window.unifiedAuth.getCurrentUser() : null;
    const response = await fetch(`get_waveforms.php?offset=${offset}&limit=5`, {
      credentials: 'include' // Include session cookies for authentication
    });
    
    if (!response.ok) throw new Error('Failed to load more waveforms');
    
    const data = await response.json();
    // Load more response received
    
    const waveforms = Array.isArray(data) ? data : (data.waveforms || []);
    const hasMore = data.has_more || false;
    const total = data.total || 0;
    
    // Additional waveforms loaded
    
    if (waveforms.length > 0) {
      const waveformsContainer = document.getElementById('waveformsContainer');
      if (!waveformsContainer) return;
      
      // Generate HTML for additional waveforms
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
      
      // Remove existing load more button
      const existingLoadMore = waveformsContainer.querySelector('#loadMoreBtn');
      if (existingLoadMore) {
        existingLoadMore.remove();
      }
      
      // Append new items
      waveformsContainer.insertAdjacentHTML('beforeend', waveformItems);
      
      // Add new load more button if there are more items
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
      
      // Update offset for next load more
      window.currentWaveformsOffset = offset + waveforms.length;
      // Updated offset
    }
    
  } catch (error) {
    if (window.handleError) {
      window.handleError(error, 'Load More Memories');
    } else {
      alert('Error loading more memories: ' + error.message);
    }
  }
};

// Order functionality (implemented from HTML)
window.orderProduct = async function(productId, memoryId, imageUrl) {
  try {
    
    const currentUser = window.unifiedAuth ? window.unifiedAuth.getCurrentUser() : null;
    if (!currentUser) {
      alert('Please log in to place an order');
      return;
    }
    
    
    // Create Stripe checkout session
    const orderData = {
      product_id: productId,
      memory_id: memoryId,
      image_url: imageUrl
    };
    
    const response = await fetch('create_checkout.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(orderData),
      credentials: 'include' // Include session cookies for authentication
    });
    
    
    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`HTTP ${response.status}: ${errorText}`);
    }
    
    const result = await response.json();
    
    if (result.success && result.checkout_url) {
      // Redirect to Stripe checkout
      window.location.href = result.checkout_url;
    } else {
      alert('Error creating order: ' + (result.error || 'Unknown error'));
    }
    
  } catch (error) {
    alert('Error placing order: ' + error.message);
  }
};

window.showOrderOptions = async function(memoryId, imageUrl, title, buttonElement) {
  try {
    
    // Change button state to show loading
    const originalText = buttonElement.textContent;
    buttonElement.textContent = 'Loading...';
    buttonElement.disabled = true;
    
    // Create a modal-like overlay for product selection
    const orderModal = document.createElement('div');
    orderModal.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(4px);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    `;
    
    // Get products
    const response = await fetch('get_products.php');
    const products = await response.json();
    
    const modalContent = `
      <div style="background: white; border-radius: 20px; padding: 32px; max-width: 600px; width: 100%; max-height: 80vh; overflow-y: auto;">
        <div style="text-align: center; margin-bottom: 24px;">
          <h2 style="margin: 0 0 8px 0; color: #0b0d12;">Order Print for "${title}"</h2>
          <p style="margin: 0; color: #6b7280;">Choose your preferred print size and material</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
          ${products.map(product => `
            <div class="product-card" style="border: 2px solid #e6e9f2; border-radius: 12px; padding: 16px; background: #fafbfc; text-align: center; transition: all 0.2s ease; cursor: pointer;" 
                 onclick="selectProduct('${product.id}', ${memoryId}, '${imageUrl}', this)">
              <div style="width: 100%; height: 120px; margin-bottom: 12px; border-radius: 8px; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                ${product.image_url ? 
                  `<img src="${product.image_url}" alt="${product.name}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                   <div style="display: none; font-size: 32px; color: #9ca3af;">🖼️</div>` :
                  `<div style="font-size: 32px; color: #9ca3af;">🖼️</div>`
                }
              </div>
              <div style="font-weight: 600; color: #0b0d12; margin-bottom: 4px; font-size: 14px;">${product.name}</div>
              <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">${product.size}</div>
              <div style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">${product.material}</div>
              <div style="font-size: 18px; font-weight: 600; color: #2a4df5;">${product.price_formatted}</div>
              <div style="font-size: 11px; color: #6b7280; margin-top: 8px;">${product.description}</div>
            </div>
          `).join('')}
        </div>
        
        <div style="text-align: center;">
          <button onclick="closeOrderModal()" style="background: #6b7280; border: none; color: white; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500;">
            Cancel
          </button>
        </div>
      </div>
    `;
    
    orderModal.innerHTML = modalContent;
    document.body.appendChild(orderModal);
    
    // Store reference for cleanup
    window.currentOrderModal = orderModal;
    
    // Reset button state
    buttonElement.textContent = originalText;
    buttonElement.disabled = false;
    
  } catch (error) {
    alert('Error loading order options: ' + error.message);
    
    // Reset button state
    buttonElement.textContent = originalText;
    buttonElement.disabled = false;
  }
};

window.selectProduct = async function(productId, memoryId, imageUrl, cardElement) {
  
  // Visual feedback
  cardElement.style.borderColor = '#2a4df5';
  cardElement.style.background = '#f0f4ff';
  
  // Close modal and proceed to checkout
  window.closeOrderModal();
  
  // Start order process
  await window.orderProduct(productId, memoryId, imageUrl);
};

window.closeOrderModal = function() {
  if (window.currentOrderModal) {
    window.currentOrderModal.remove();
    window.currentOrderModal = null;
  }
};

// Ensure functions are available globally for inline onclick handlers
window.showOrderOptions = window.showOrderOptions;
window.selectProduct = window.selectProduct;
window.orderProduct = window.orderProduct;
window.closeOrderModal = window.closeOrderModal;

// Mark globals module as ready
window.globalsModuleReady = true;

// Log globals module status for debugging
console.log("Globals module loaded:", {
  showOrderOptions: typeof window.showOrderOptions,
  selectProduct: typeof window.selectProduct,
  orderProduct: typeof window.orderProduct,
  closeOrderModal: typeof window.closeOrderModal,
  globalsModuleReady: window.globalsModuleReady
});
