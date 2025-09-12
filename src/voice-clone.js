// Voice cloning functionality
import unifiedAuth from './unified-auth.js';

let currentUser = null;

// Initialize voice cloning functionality
export function initVoiceClone() {
    currentUser = unifiedAuth.getCurrentUser();
    if (!currentUser) {
        return;
    }
    
    // Add voice clone buttons to existing memories
    addVoiceCloneButtons();
}

// Make function available globally
window.initVoiceClone = initVoiceClone;

// Add voice clone buttons to memory items
function addVoiceCloneButtons() {
    const memoryItems = document.querySelectorAll('.memory-item');
    
    memoryItems.forEach(item => {
        const memoryId = item.dataset.memoryId;
        const audioUrl = item.dataset.audioUrl;
        const memoryTitle = item.dataset.memoryTitle;
        
        
        if (memoryId && audioUrl && !item.querySelector('.voice-clone-btn')) {
            const cloneBtn = createVoiceCloneButton(memoryId, audioUrl, memoryTitle);
            
            // Find the actions container or create one
            let actionsContainer = item.querySelector('.memory-actions');
            if (!actionsContainer) {
                actionsContainer = document.createElement('div');
                actionsContainer.className = 'memory-actions';
                item.appendChild(actionsContainer);
            }
            
            actionsContainer.appendChild(cloneBtn);
        } else if (!audioUrl) {
        }
    });
}

// Create voice clone button
function createVoiceCloneButton(memoryId, audioUrl, memoryTitle) {
    const btn = document.createElement('button');
    btn.className = 'voice-clone-btn';
    btn.innerHTML = '🎤 Clone Voice';
    btn.style.cssText = `
        background: #8b5cf6;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        margin-left: 8px;
        transition: background 0.2s;
    `;
    
    btn.addEventListener('mouseenter', () => {
        btn.style.background = '#7c3aed';
    });
    
    btn.addEventListener('mouseleave', () => {
        btn.style.background = '#8b5cf6';
    });
    
    btn.addEventListener('click', () => {
        showVoiceCloneModal(memoryId, audioUrl, memoryTitle);
    });
    
    return btn;
}

// Show voice clone modal
function showVoiceCloneModal(memoryId, audioUrl, memoryTitle) {
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
}

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
        
        const response = await fetch('voice_clone_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include', // Include cookies for session management
            body: new URLSearchParams({
                action: 'create_clone',
                user_id: unifiedAuth.getCurrentUser().uid,
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
                showVoiceGenerationModal(result.voice_id, voiceName);
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

// Show voice generation modal
function showVoiceGenerationModal(voiceId, voiceName) {
    const modal = document.createElement('div');
    modal.className = 'voice-generation-modal';
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
        max-width: 600px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
    `;
    
    dialog.innerHTML = `
        <h3 style="margin: 0 0 16px 0; color: #0b0d12; font-size: 18px;">🎵 Generate Audio with "${voiceName}"</h3>
        <p style="margin: 0 0 20px 0; color: #6b7280; line-height: 1.5;">
            Enter text to generate speech using the cloned voice.
        </p>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Text to speak:</label>
            <textarea id="textToSpeak" placeholder="Enter the text you want the voice to say..." 
                      style="width: 100%; height: 100px; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; resize: vertical;"></textarea>
        </div>
        
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button id="cancelGenerate" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                Cancel
            </button>
            <button id="generateAudio" style="background: #8b5cf6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                Generate Audio
            </button>
        </div>
        
        <div id="generateStatus" style="margin-top: 16px; display: none;">
            <div id="generateProgress" style="color: #6b7280; font-size: 14px;"></div>
            <audio id="generatedAudio" controls style="margin-top: 12px; width: 100%; display: none;"></audio>
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
    
    dialog.querySelector('#cancelGenerate').addEventListener('click', cleanup);
    
    dialog.querySelector('#generateAudio').addEventListener('click', async () => {
        const text = dialog.querySelector('#textToSpeak').value.trim();
        
        if (!text) {
            alert('Please enter text to generate');
            return;
        }
        
        await generateAudio(voiceId, text, dialog);
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
}

// Generate audio from text
async function generateAudio(voiceId, text, dialog) {
    const statusDiv = dialog.querySelector('#generateStatus');
    const progressDiv = dialog.querySelector('#generateProgress');
    const audioElement = dialog.querySelector('#generatedAudio');
    const generateBtn = dialog.querySelector('#generateAudio');
    
    statusDiv.style.display = 'block';
    generateBtn.disabled = true;
    generateBtn.textContent = 'Generating...';
    
    try {
        progressDiv.textContent = 'Generating speech...';
        
        const response = await fetch('voice_clone_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include', // Include cookies for session management
            body: new URLSearchParams({
                action: 'generate_speech',
                user_id: unifiedAuth.getCurrentUser().uid,
                voice_id: voiceId,
                text: text
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            progressDiv.textContent = '✅ Audio generated successfully!';
            progressDiv.style.color = '#10b981';
            
            // Create audio URL from the generated file
            const audioUrl = URL.createObjectURL(new Blob([result.audio_file], { type: 'audio/mpeg' }));
            audioElement.src = audioUrl;
            audioElement.style.display = 'block';
            
            generateBtn.disabled = false;
            generateBtn.textContent = 'Generate Audio';
        } else {
            throw new Error(result.error || 'Failed to generate audio');
        }
        
    } catch (error) {
        progressDiv.textContent = `❌ Error: ${error.message}`;
        progressDiv.style.color = '#ef4444';
        generateBtn.disabled = false;
        generateBtn.textContent = 'Generate Audio';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Wait for memories to load, then initialize voice cloning
    const checkForMemories = () => {
        const memoryItems = document.querySelectorAll('.memory-item');
        if (memoryItems.length > 0) {
            initVoiceClone();
        } else {
            // Keep checking every 500ms until memories are loaded
            setTimeout(checkForMemories, 500);
        }
    };
    
    // Start checking after a short delay
    setTimeout(checkForMemories, 1000);
});
