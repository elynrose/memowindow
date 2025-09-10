// Memories-specific functionality
import { getCurrentUser } from './app-auth.js';

// Initialize memories functionality
export function initMemories() {
    console.log('💕 Initializing memories functionality...');
    
    // Wait for authentication to be ready, then load memories
    waitForAuthAndLoadMemories();
    
    console.log('✅ Memories functionality initialized');
}

// Wait for authentication and then load memories
function waitForAuthAndLoadMemories() {
    const checkAuth = () => {
        const currentUser = getCurrentUser();
        if (currentUser) {
            console.log('✅ User authenticated, loading memories...');
            loadMemories();
        } else {
            console.log('⏳ Waiting for authentication...');
            // Check again in 500ms
            setTimeout(checkAuth, 500);
        }
    };
    
    // Start checking
    checkAuth();
}

// Load user memories
async function loadMemories() {
    try {
        const currentUser = getCurrentUser();
        if (!currentUser) {
            console.error('User not authenticated');
            return;
        }
        
        const response = await fetch(`get_waveforms.php?user_id=${encodeURIComponent(currentUser.uid)}`);
        const data = await response.json();
        
        if (data.success && data.waveforms) {
            displayMemories(data.waveforms);
        } else {
            showEmptyState();
        }
        
    } catch (error) {
        console.error('Error loading memories:', error);
        showErrorState();
    }
}

// Display memories in grid
function displayMemories(memories) {
    const container = document.getElementById('memoriesContainer');
    
    if (memories.length === 0) {
        showEmptyState();
        return;
    }
    
    const memoriesHTML = memories.map(memory => `
        <div class="memory-card">
            <img src="${memory.image_url}" 
                 alt="${memory.title || 'Memory'}" 
                 class="memory-image"
                 onclick="viewMemory('${memory.image_url}', '${memory.title || 'Untitled'}', '${memory.qr_url || ''}')">
            <div class="memory-content">
                <h3 class="memory-title">${memory.title || 'Untitled'}</h3>
                <p class="memory-date">${new Date(memory.created_at).toLocaleDateString()}</p>
                <div class="memory-actions">
                    <a href="${memory.image_url}" target="_blank" class="memory-action">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        View
                    </a>
                    <a href="${memory.qr_url || '#'}" target="_blank" class="memory-action">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm13-2h-2v3h-3v2h3v3h2v-3h3v-2h-3v-3z"/>
                        </svg>
                        QR
                    </a>
                    <a href="#" onclick="orderPrint(${memory.id}, '${memory.image_url}')" class="memory-action order">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 8h-1V3H6v5H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zM8 5h8v3H8V5zm8 12.5h-8v-4h8v4z"/>
                        </svg>
                        Order Print
                    </a>
                    <a href="#" onclick="deleteMemory(${memory.id})" class="memory-action delete">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                        Delete
                    </a>
                </div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = `
        <div class="memories-grid">
            ${memoriesHTML}
        </div>
    `;
}

// Show empty state
function showEmptyState() {
    const container = document.getElementById('memoriesContainer');
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">🎵</div>
            <h3>No memories yet</h3>
            <p>Create your first beautiful waveform memory to get started!</p>
            <a href="app.html" class="create-memory-btn" style="margin-top: 1rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                </svg>
                Create Your First Memory
            </a>
        </div>
    `;
}

// Show error state
function showErrorState() {
    const container = document.getElementById('memoriesContainer');
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">⚠️</div>
            <h3>Error loading memories</h3>
            <p>There was a problem loading your memories. Please try refreshing the page.</p>
            <button onclick="location.reload()" class="create-memory-btn" style="margin-top: 1rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                </svg>
                Refresh Page
            </button>
        </div>
    `;
}

// View memory in modal
window.viewMemory = function(imageUrl, title, qrUrl) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    modal.innerHTML = `
        <div style="position: relative; max-width: 90vw; max-height: 90vh;">
            <button onclick="closeImageModal()" style="position: absolute; top: -40px; right: 0; background: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center;">×</button>
            <img src="${imageUrl}" alt="${title}" style="max-width: 100%; max-height: 100%; border-radius: 8px;">
            <div style="text-align: center; color: white; margin-top: 16px; font-size: 18px;">${title}</div>
            ${qrUrl ? `<div style="text-align: center; margin-top: 8px;"><a href="${qrUrl}" target="_blank" style="color: #667eea; text-decoration: none;">View QR Code</a></div>` : ''}
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close on escape key
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    };
    document.addEventListener('keydown', handleEscape);
    
    // Store cleanup function
    modal._cleanup = () => {
        document.removeEventListener('keydown', handleEscape);
    };
};

// Close image modal
window.closeImageModal = function() {
    const modal = document.querySelector('div[style*="position: fixed"]');
    if (modal) {
        if (modal._cleanup) {
            modal._cleanup();
        }
        document.body.removeChild(modal);
    }
};

// Order print
window.orderPrint = async function(memoryId, imageUrl) {
    try {
        const currentUser = getCurrentUser();
        if (!currentUser) {
            alert('Please log in to order prints.');
            return;
        }
        
        // Get product details (using default product for now)
        const productId = '5894'; // Default product ID
        
        // Create checkout session
        const response = await fetch('create_checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                memory_id: memoryId,
                image_url: imageUrl,
                user_id: currentUser.uid,
                user_email: currentUser.email,
                user_name: currentUser.displayName || currentUser.email
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.checkout_url) {
            // Redirect to Stripe checkout
            window.location.href = data.checkout_url;
        } else {
            throw new Error(data.error || 'Failed to create checkout session');
        }
        
    } catch (error) {
        console.error('Error creating order:', error);
        alert('Failed to create order: ' + error.message);
    }
};

// Delete memory
window.deleteMemory = async function(memoryId) {
    if (!confirm('Are you sure you want to delete this memory? This action cannot be undone.')) {
        return;
    }
    
    try {
        const currentUser = getCurrentUser();
        if (!currentUser) {
            alert('Please log in to delete memories.');
            return;
        }
        
        const response = await fetch('delete_memory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `memory_id=${memoryId}&user_id=${encodeURIComponent(currentUser.uid)}`
        });
        
        if (!response.ok) {
            throw new Error('Delete request failed');
        }
        
        const result = await response.json();
        if (result.success) {
            showToast('Memory deleted successfully', 'success');
            loadMemories(); // Reload the list
        } else {
            throw new Error(result.error || 'Delete failed');
        }
        
    } catch (error) {
        console.error('Error deleting memory:', error);
        showToast('Failed to delete memory: ' + error.message, 'error');
    }
};

// Show toast notification
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        font-weight: 500;
        max-width: 300px;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

// Make functions available globally
window.initMemories = initMemories;
