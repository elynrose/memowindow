// Memories-specific functionality
import unifiedAuth from './unified-auth.js';
import { uploadToFirebaseStorage } from './storage.js';
import './globals.js'; // Import globals module to ensure it loads

// Initialize memories functionality
export function initMemories() {
    // Initializing memories functionality
    
    // Set up event delegation for order buttons
    setupOrderButtonHandlers();
    
    // Wait for globals module to load, then load memories
    waitForGlobalsAndLoadMemories();
    
    // Memories functionality initialized
}

// Set up event delegation for memory action buttons
function setupOrderButtonHandlers() {
    document.addEventListener('click', function(event) {
        // Handle order buttons
        if (event.target.closest('.memory-action.order')) {
            event.preventDefault();
            
            const orderButton = event.target.closest('.memory-action.order');
            const memoryId = orderButton.getAttribute('data-memory-id');
            const imageUrl = orderButton.getAttribute('data-image-url');
            const title = orderButton.getAttribute('data-title');
            
            
            // Call showOrderOptions if it's available
            if (window.showOrderOptions) {
                window.showOrderOptions(memoryId, imageUrl, title, orderButton);
            } else {
                alert('Order functionality is loading. Please wait a moment and try again.');
            }
        }
        
        // Handle memory image clicks
        else if (event.target.closest('.memory-image-clickable')) {
            event.preventDefault();
            
            const imageElement = event.target.closest('.memory-image-clickable');
            const imageUrl = imageElement.getAttribute('data-image-url');
            const title = imageElement.getAttribute('data-title');
            const qrUrl = imageElement.getAttribute('data-qr-url');
            
            
            if (window.viewMemory) {
                window.viewMemory(imageUrl, title, qrUrl);
            }
        }
        
        // Handle delete buttons
        else if (event.target.closest('.memory-action.delete')) {
            event.preventDefault();
            
            const deleteButton = event.target.closest('.memory-action.delete');
            const memoryId = deleteButton.getAttribute('data-memory-id');
            
            
            if (window.deleteMemory) {
                window.deleteMemory(memoryId);
            }
        }
        
        // Handle voice clone buttons
        else if (event.target.closest('.memory-action.voice-clone')) {
            event.preventDefault();
            
            const voiceButton = event.target.closest('.memory-action.voice-clone');
            const memoryId = voiceButton.getAttribute('data-memory-id');
            const audioUrl = voiceButton.getAttribute('data-audio-url');
            const title = voiceButton.getAttribute('data-title');
            
            
            if (window.checkVoiceCloneStatus) {
                window.checkVoiceCloneStatus(memoryId, audioUrl, title);
            }
        }
        
        // Handle generate audio buttons
        else if (event.target.closest('.memory-action.generate-audio')) {
            event.preventDefault();
            
            const generateButton = event.target.closest('.memory-action.generate-audio');
            const memoryId = generateButton.getAttribute('data-memory-id');
            const title = generateButton.getAttribute('data-title');
            
            
            if (window.showGenerateAudioModal) {
                window.showGenerateAudioModal(memoryId, title);
            }
        }
    });
}

// Wait for globals module and authentication, then load memories
async function waitForGlobalsAndLoadMemories() {
    
    // Wait for globals module to load
    await waitForGlobalsModule();
    
    // Wait for authentication
    await unifiedAuth.waitForAuth();
    const currentUser = unifiedAuth.getCurrentUser();
    
    if (currentUser) {
        loadMemories();
    } else {
        showLoginPrompt();
    }
}

// Wait for globals module to be available
async function waitForGlobalsModule() {
    
    // Since we're importing globals.js directly, it should be available immediately
    // But let's add a small delay to ensure it's fully initialized
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Check if globals module is loaded
    if (window.globalsModuleReady && window.showOrderOptions && window.orderProduct && window.selectProduct) {
        return;
    }
    
    // Log the current state for debugging
    console.log("Window state:", {
        globalsModuleReady: window.globalsModuleReady,
        showOrderOptions: typeof window.showOrderOptions,
        orderProduct: typeof window.orderProduct,
        selectProduct: typeof window.selectProduct
    });
    throw new Error("Order functionality not available");
}

// Show login prompt if user is not authenticated
function showLoginPrompt() {
    const container = document.getElementById('memoriesContainer');
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">🔐</div>
            <h3>Please Sign In</h3>
            <p>You need to be signed in to view your memories.</p>
            <a href="login.php" class="create-memory-btn" style="margin-top: 1rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                Sign In
            </a>
        </div>
    `;
}

// Load user memories
async function loadMemories() {
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        if (!currentUser) {
            showLoginPrompt();
            return;
        }
        
        // Loading memories for user
        
        const response = await fetch(`get_waveforms.php`, {
            credentials: 'include' // Include cookies for session management
        });
        // API Response received
        
        if (!response.ok) {
            throw new Error(`API request failed with status ${response.status}`);
        }
        
        const data = await response.json();
        // API Response data received
        
        if (data.waveforms && data.waveforms.length > 0) {
            // Found memories
            displayMemories(data.waveforms);
        } else {
            // No memories found
            showEmptyState();
        }
        
    } catch (error) {
        showErrorState(error.message);
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
        <div class="memory-card memory-item" 
             data-memory-id="${memory.id}" 
             data-audio-url="${memory.audio_url || ''}" 
             data-memory-title="${memory.title || 'Untitled'}">
            <img src="${memory.image_url}" 
                 alt="${memory.title || 'Memory'}" 
                 class="memory-image memory-image-clickable" 
                 data-image-url="${memory.image_url}" 
                 data-title="${memory.title || 'Untitled'}" 
                 data-qr-url="${memory.qr_url || ''}">
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
                    <a href="#" class="memory-action order" data-memory-id="${memory.id}" data-image-url="${memory.image_url}" data-title="${memory.title || 'Untitled'}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 8h-1V3H6v5H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zM8 5h8v3H8V5zm8 12.5h-8v-4h8v4z"/>
                        </svg>
                        Order
                    </a>
                    ${memory.audio_url ? `
                    <button class="memory-action voice-clone" data-memory-id="${memory.id}" data-audio-url="${memory.audio_url}" data-title="${memory.title || 'Untitled'}" style="background: #8b5cf6; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; display: none;">
                        🎤 Clone Voice
                    </button>
                    <button class="memory-action generate-audio" data-memory-id="${memory.id}" data-title="${memory.title || 'Untitled'}" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; display: none;">
                        🎵 Generate Audio
                    </button>
                    ` : ''}
                    <a href="#" class="memory-action delete" data-memory-id="${memory.id}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
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
    
    // Check voice clone feature status and show/hide buttons
    checkVoiceCloneFeatureStatus();
    
    // Voice clone buttons are now embedded directly in the memory HTML
}

// Show empty state
function showEmptyState() {
    const container = document.getElementById('memoriesContainer');
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">🎵</div>
            <h3>No memories yet</h3>
            <p>Create your first beautiful waveform memory to get started!</p>
            <a href="app.php" class="create-memory-btn" style="margin-top: 1rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                </svg>
                Create Your First Memory
            </a>
        </div>
    `;
}

// Show error state
function showErrorState(errorMessage = '') {
    const container = document.getElementById('memoriesContainer');
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">⚠️</div>
            <h3>Error loading memories</h3>
            <p>There was a problem loading your memories. Please try refreshing the page.</p>
            ${errorMessage ? `<p style="color: #dc2626; font-size: 0.875rem; margin-top: 0.5rem;">Error: ${errorMessage}</p>` : ''}
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
        const currentUser = unifiedAuth.getCurrentUser();
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
        alert('Failed to create order: ' + error.message);
    }
};

// Delete memory
window.deleteMemory = async function(memoryId) {
    if (!confirm('Are you sure you want to delete this memory? This action cannot be undone.')) {
        return;
    }
    
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        if (!currentUser) {
            alert('Please log in to delete memories.');
            return;
        }
        
        const response = await fetch('delete_memory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'include', // Include cookies for session management
            body: `memory_id=${memoryId}`
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

// Show voice clone modal
window.showVoiceCloneModal = function(memoryId, audioUrl, memoryTitle, statusInfo = null) {
    const modal = document.createElement('div');
    modal.className = 'voice-clone-modal';
    modal.style.cssText = `
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
    dialog.style.cssText = `
        background: white;
        padding: 24px;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
    `;
    
    dialog.innerHTML = `
        <h3 style="margin: 0 0 16px 0; color: #0b0d12; font-size: 18px;">🎤 Clone Voice from Memory</h3>
        <p style="margin: 0 0 20px 0; color: #6b7280; line-height: 1.5;">
            Create a voice clone from "${memoryTitle}" to generate new audio with this voice.
        </p>
        
        ${statusInfo ? `
        <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 6px; padding: 12px; margin-bottom: 20px;">
            <div style="font-size: 14px; color: #0369a1;">
                <strong>Usage:</strong> ${statusInfo.usage}/${statusInfo.limit} clones this month
            </div>
        </div>
        ` : ''}
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Voice Name:</label>
            <input type="text" id="voiceName" placeholder="e.g., Mom's Voice" 
                   style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
        </div>
        
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button id="cancelClone" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                Cancel
            </button>
            <button id="createClone" style="background: #8b5cf6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                Create Voice Clone
            </button>
        </div>
        
        <div id="cloneStatus" style="margin-top: 16px; display: none;">
            <div id="cloneProgress" style="color: #6b7280; font-size: 14px;"></div>
        </div>
    `;
    
    modal.appendChild(dialog);
    document.body.appendChild(modal);
    
    // Animate in
    requestAnimationFrame(() => {
        modal.style.opacity = '1';
        dialog.style.transform = 'scale(1)';
    });
    
    // Handle events
    const cleanup = () => {
        modal.style.opacity = '0';
        dialog.style.transform = 'scale(0.9)';
        setTimeout(() => modal.remove(), 300);
    };
    
    dialog.querySelector('#cancelClone').addEventListener('click', cleanup);
    
    dialog.querySelector('#createClone').addEventListener('click', async () => {
        const voiceName = dialog.querySelector('#voiceName').value.trim();
        
        if (!voiceName) {
            alert('Please enter a voice name');
            return;
        }
        
        await createVoiceClone(memoryId, audioUrl, voiceName, dialog);
    });
    
    // Handle escape key
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            cleanup();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
    
    // Handle overlay click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            cleanup();
        }
    });
};

// Create voice clone
async function createVoiceClone(memoryId, audioUrl, voiceName, dialog) {
    const statusDiv = dialog.querySelector('#cloneStatus');
    const progressDiv = dialog.querySelector('#cloneProgress');
    const createBtn = dialog.querySelector('#createClone');
    
    statusDiv.style.display = 'block';
    createBtn.disabled = true;
    createBtn.textContent = 'Creating...';
    
    try {
        progressDiv.textContent = 'Downloading audio file...';
        
        const currentUser = unifiedAuth.getCurrentUser();
        const response = await fetch('voice_clone_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'create_clone',
                user_id: currentUser.uid,
                memory_id: memoryId,
                voice_name: voiceName,
                audio_url: audioUrl
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            progressDiv.textContent = '✅ Voice clone created successfully!';
            progressDiv.style.color = '#10b981';
            
            setTimeout(() => {
                dialog.closest('.voice-clone-modal').remove();
                alert('Voice clone created! You can now generate audio with this voice.');
            }, 2000);
        } else {
            throw new Error(result.error || 'Failed to create voice clone');
        }
        
    } catch (error) {
        progressDiv.textContent = `❌ Error: ${error.message}`;
        progressDiv.style.color = '#ef4444';
        createBtn.disabled = false;
        createBtn.textContent = 'Create Voice Clone';
    }
}

// Check voice clone status before showing modal
window.checkVoiceCloneStatus = async function(memoryId, audioUrl, memoryTitle) {
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        
        // Check subscription limits first
        const subscriptionResponse = await fetch(`check_subscription.php`, {
            credentials: 'include' // Include cookies for session management
        });
        const subscriptionData = await subscriptionResponse.json();
        
        if (subscriptionData.success && !subscriptionData.limits.can_create_voice_clone.allowed) {
            alert(subscriptionData.limits.can_create_voice_clone.reason);
            return;
        }
        
        const response = await fetch('voice_clone_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include', // Include cookies for session management
            body: new URLSearchParams({
                action: 'check_status',
                user_id: currentUser.uid
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.can_create) {
                // User can create voice clone, show the modal
                showVoiceCloneModal(memoryId, audioUrl, memoryTitle, result);
            } else {
                // Show error message
                alert(`Cannot create voice clone: ${result.reason}`);
            }
        } else {
            alert('Error checking voice clone status');
        }
    } catch (error) {
        alert('Error checking voice clone status');
    }
};

// Check voice clone feature status and show/hide buttons
async function checkVoiceCloneFeatureStatus() {
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        const response = await fetch('voice_clone_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include', // Include cookies for session management
            body: new URLSearchParams({
                action: 'check_status'
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.enabled) {
            // Show all voice clone and generate audio buttons
            document.querySelectorAll('.voice-clone, .generate-audio').forEach(button => {
                button.style.display = 'inline-block';
            });
        } else {
            // Hide all voice clone and generate audio buttons
            document.querySelectorAll('.voice-clone, .generate-audio').forEach(button => {
                button.style.display = 'none';
            });
        }
    } catch (error) {
        // Hide buttons on error to be safe
        document.querySelectorAll('.voice-clone, .generate-audio').forEach(button => {
            button.style.display = 'none';
        });
    }
}

// Show generate audio modal
window.showGenerateAudioModal = async function(memoryId, memoryTitle) {
    try {
        // Get user's cloned voices
        const currentUser = unifiedAuth.getCurrentUser();
        const response = await fetch('voice_clone_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include', // Include cookies for session management
            body: new URLSearchParams({
                action: 'get_user_voices',
                user_id: currentUser.uid
            })
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            alert('Error loading voice clones: ' + response.status);
            return;
        }
        
        const result = await response.json();
        
        if (!result.success || !result.voices || result.voices.length === 0) {
            alert('No cloned voices found. Please clone a voice first.');
            return;
        }
        
        // Create modal
        const dialog = document.createElement('div');
        dialog.className = 'generate-audio-modal';
        dialog.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); display: flex; align-items: center; 
            justify-content: center; z-index: 1000;
        `;
        
        dialog.innerHTML = `
            <div style="background: white; padding: 24px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto;">
                <h3 style="margin: 0 0 16px 0; color: #0b0d12; font-size: 18px;">🎵 Generate Audio for "${memoryTitle}"</h3>
                <p style="margin: 0 0 20px 0; color: #6b7280; line-height: 1.5;">
                    Select a cloned voice and enter text to generate new audio for this memory.
                </p>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Select Voice:</label>
                    <select id="voiceSelect" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                        ${result.voices.map(voice => `
                            <option value="${voice.voice_id}" data-voice-name="${voice.voice_name}">
                                ${voice.voice_name} ${voice.memory_title ? `(from "${voice.memory_title}")` : ''}
                            </option>
                        `).join('')}
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Text to Convert:</label>
                    <textarea id="textInput" placeholder="Enter the text you want to convert to speech..." 
                              style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; min-height: 100px; resize: vertical;"></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button id="cancelGenerateAudio" 
                            style="padding: 10px 20px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer;">
                        Cancel
                    </button>
                    <button onclick="generateAudio(${memoryId})" 
                            style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Generate Audio
                    </button>
                </div>
            </div>
        `;
        
        // Add click handler for cancel button
        dialog.querySelector('#cancelGenerateAudio').addEventListener('click', () => {
            dialog.remove();
        });
        
        // Add click handler for overlay (click outside to close)
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                dialog.remove();
            }
        });
        
        // Store reference for later use
        window.currentGenerateAudioModal = dialog;
        
        document.body.appendChild(dialog);
        
    } catch (error) {
        alert('Error loading voice options');
    }
};

// Generate audio with selected voice
window.generateAudio = async function(memoryId) {
    const voiceSelect = document.getElementById('voiceSelect');
    const textInput = document.getElementById('textInput');
    
    if (!voiceSelect || !textInput) {
        alert('Error: Form elements not found');
        return;
    }
    
    const voiceId = voiceSelect.value;
    const text = textInput.value.trim();
    
    if (!voiceId || !text) {
        alert('Please select a voice and enter text');
        return;
    }
    
    if (text.length > 5000) {
        alert('Text is too long. Please keep it under 5000 characters.');
        return;
    }
    
    try {
        // Show loading state
        const generateBtn = document.querySelector('button[onclick*="generateAudio"]');
        const originalText = generateBtn.textContent;
        generateBtn.textContent = 'Generating...';
        generateBtn.disabled = true;
        
        const currentUser = unifiedAuth.getCurrentUser();
        const response = await fetch('voice_clone_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include', // Include cookies for session management
            body: new URLSearchParams({
                action: 'generate_speech',
                voice_id: voiceId,
                text: text,
                memory_id: memoryId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Firebase upload is required for all generated audio (primary storage)
            if (result.needs_firebase_upload && result.audio_data) {
                try {
                    // Convert base64 to blob
                    const audioBlob = await base64ToBlob(result.audio_data, 'audio/mpeg');
                    
                    // Generate filename for Firebase
                    const timestamp = Date.now();
                    const fileName = `generated_${currentUser.uid}_${timestamp}_${result.audio_id}.mp3`;
                    
                    // Upload to Firebase Storage (primary)
                    const firebaseUrl = await uploadToFirebaseStorage(audioBlob, fileName, 'generated-audio');
                    
                    // Update the database with Firebase URL as primary
                    const updateResponse = await fetch('voice_clone_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'update_audio_url',
                            user_id: currentUser.uid,
                            generated_audio_id: result.generated_audio_id,
                            firebase_url: firebaseUrl,
                            local_backup_url: result.local_backup_url
                        })
                    });
                    
                    const updateResult = await updateResponse.json();
                    if (!updateResult.success) {
                        throw new Error('Failed to update database with Firebase URL: ' + updateResult.error);
                    }
                    
                    
                } catch (firebaseError) {
                    
                    // If Firebase fails, fall back to local backup
                    if (result.local_backup_success && result.local_backup_url) {
                        
                        // Update database with local backup URL
                        const fallbackResponse = await fetch('voice_clone_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'update_audio_url',
                                user_id: currentUser.uid,
                                generated_audio_id: result.generated_audio_id,
                                firebase_url: result.local_backup_url, // Use local as fallback
                                local_backup_url: result.local_backup_url
                            })
                        });
                        
                        const fallbackResult = await fallbackResponse.json();
                        if (!fallbackResult.success) {
                            throw new Error('Failed to update database with fallback URL: ' + fallbackResult.error);
                        }
                        
                        alert('Audio generated successfully! (Saved locally - Firebase upload failed)');
                    } else {
                        throw new Error('Both Firebase and local storage failed');
                    }
                }
            } else {
                alert('Audio generated successfully! The new audio has been added to your memory.');
            }
            
            // Close modal
            if (window.currentGenerateAudioModal) {
                window.currentGenerateAudioModal.remove();
                window.currentGenerateAudioModal = null;
            }
            // Refresh memories to show the new audio
            loadMemories();
        } else {
            alert(`Error generating audio: ${result.error}`);
        }
        
    } catch (error) {
        alert('Error generating audio');
        
        // Close modal on error
        if (window.currentGenerateAudioModal) {
            window.currentGenerateAudioModal.remove();
            window.currentGenerateAudioModal = null;
        }
    } finally {
        // Reset button state
        const generateBtn = document.querySelector('button[onclick*="generateAudio"]');
        if (generateBtn) {
            generateBtn.textContent = 'Generate Audio';
            generateBtn.disabled = false;
        }
    }
};

// Helper function to convert base64 to blob
async function base64ToBlob(base64Data, mimeType) {
    const response = await fetch(`data:${mimeType};base64,${base64Data}`);
    return await response.blob();
}

// Make functions available globally
window.initMemories = initMemories;
