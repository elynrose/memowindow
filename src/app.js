// App-specific functionality for memory creation
import { getCurrentUser } from './app-auth.js';
import { uploadWaveformFiles } from './storage.js';

// Global variables
let selectedFiles = [];
let currentPeaks = null;
let isRecording = false;
let mediaRecorder = null;
let audioChunks = [];

// DOM elements
let fileInput, fileUploadArea, btnRecord, btnCreate, titleInput, waveformList, memoryPreview, previewCanvas, previewStatus;

// Initialize app functionality
export function initApp() {
    // Initializing app functionality
    
    // Get DOM elements
    fileInput = document.getElementById('fileInput');
    fileUploadArea = document.getElementById('fileUploadArea');
    btnRecord = document.getElementById('btnRecord');
    btnCreate = document.getElementById('btnCreate');
    titleInput = document.getElementById('titleInput');
    waveformList = document.getElementById('waveformList');
    memoryPreview = document.getElementById('memoryPreview');
    previewCanvas = document.getElementById('previewCanvas');
    previewStatus = document.getElementById('previewStatus');
    
    if (!fileInput || !fileUploadArea || !btnRecord || !btnCreate) {
        console.error('❌ Required DOM elements not found');
        return;
    }
    
    // Set up event listeners
    setupEventListeners();
    
    // Wait for authentication and then load user's waveforms
    waitForAuthAndLoadWaveforms();
    
    // App functionality initialized
}

// Wait for authentication and then load waveforms
function waitForAuthAndLoadWaveforms() {
    const checkAuth = () => {
        const currentUser = getCurrentUser();
        if (currentUser) {
            // User authenticated, loading waveforms
            loadUserWaveforms();
        } else {
            // Waiting for authentication
            // Check again in 500ms
            setTimeout(checkAuth, 500);
        }
    };
    
    // Start checking
    checkAuth();
}

// Set up event listeners
function setupEventListeners() {
    // File input change
    fileInput.addEventListener('change', handleFileSelect);
    
    // File upload area click
    fileUploadArea.addEventListener('click', () => fileInput.click());
    
    // Drag and drop
    fileUploadArea.addEventListener('dragover', handleDragOver);
    fileUploadArea.addEventListener('drop', handleDrop);
    fileUploadArea.addEventListener('dragleave', handleDragLeave);
    
    // Title input
    titleInput.addEventListener('input', () => {
        validateForm();
        // Redraw preview when title changes
        if (currentPeaks) {
            drawPreview();
        }
    });
    
    // Record button functionality
    btnRecord.addEventListener('click', toggleRecording);
    
    // Create button functionality
    btnCreate.addEventListener('click', createMemory);
}

// Handle file selection
function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    if (files.length > 0) {
        selectedFiles = files;
        updateFileUploadArea();
        processFileForPreview(files[0]);
        validateForm();
    }
}

// Handle drag over
function handleDragOver(event) {
    event.preventDefault();
    fileUploadArea.style.borderColor = '#667eea';
    fileUploadArea.style.background = '#f0f4ff';
}

// Handle drop
function handleDrop(event) {
    event.preventDefault();
    const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('audio/'));
    if (files.length > 0) {
        selectedFiles = files;
        fileInput.files = event.dataTransfer.files;
        updateFileUploadArea();
        processFileForPreview(files[0]);
        validateForm();
    }
    handleDragLeave();
}

// Handle drag leave
function handleDragLeave() {
    fileUploadArea.style.borderColor = '#d1d5db';
    fileUploadArea.style.background = '#f9fafb';
}

// Update file upload area
function updateFileUploadArea() {
    if (selectedFiles.length > 0) {
        fileUploadArea.classList.add('has-files');
        fileUploadArea.innerHTML = `
            <div style="margin-bottom: 1rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #10b981; margin: 0 auto;">
                    <path d="M9 12l2 2 4-4"></path>
                    <circle cx="12" cy="12" r="10"></circle>
                </svg>
            </div>
            <p style="font-size: 1.125rem; font-weight: 600; color: #10b981; margin-bottom: 0.5rem;">${selectedFiles.length} file(s) selected</p>
            <p style="color: #6b7280; font-size: 0.875rem;">Click to change files</p>
        `;
    }
}

// Process file for preview
async function processFileForPreview(file) {
    try {
        const arrayBuffer = await file.arrayBuffer();
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
        
        // Compute peaks for preview
        currentPeaks = computePeaksFromBuffer(audioBuffer);
        drawPreview();
        
    } catch (error) {
        console.error('Error processing file for preview:', error);
    }
}

// Compute peaks from audio buffer (original implementation)
function computePeaksFromBuffer(buf, width = 2000) {
    const ch = buf.getChannelData(0);
    const hop = Math.max(1, Math.floor(buf.length / width));
    const min = new Float32Array(width), max = new Float32Array(width);
    for (let x = 0; x < width; x++) {
        const s = x * hop, e = Math.min((x + 1) * hop, buf.length);
        let mi = 1.0, ma = -1.0;
        for (let i = s; i < e; i++) { 
            const v = ch[i]; 
            if (v < mi) mi = v; 
            if (v > ma) ma = v; 
        }
        min[x] = mi; 
        max[x] = ma;
    }
    return { min, max };
}

// Draw preview (original implementation)
function drawPreview() {
    if (!previewCanvas || !currentPeaks) return;
    
    const ctx = previewCanvas.getContext('2d');
    const W = previewCanvas.width;
    const H = previewCanvas.height;
    
    // Clear canvas
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);
    
    // Get title from input
    const title = titleInput.value.trim() || 'Untitled Memory';
    
    // Calculate layout areas
    const titleHeight = 80;
    const qrSize = 120;
    const padding = 40;
    const waveformWidth = W * 0.7; // 70% of canvas width
    const waveformArea = {
        x: (W - waveformWidth) / 2, // Center the waveform
        y: titleHeight + padding,
        width: waveformWidth,
        height: H - titleHeight - qrSize - (padding * 3)
    };
    
    // Draw title at top center
    ctx.fillStyle = '#0b0d12';
    ctx.font = 'bold 32px system-ui, -apple-system, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Word wrap title if too long
    const maxTitleWidth = W - (padding * 2);
    const words = title.split(' ');
    let line = '';
    let lines = [];
    
    for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' ';
        const metrics = ctx.measureText(testLine);
        const testWidth = metrics.width;
        
        if (testWidth > maxTitleWidth && n > 0) {
            lines.push(line);
            line = words[n] + ' ';
        } else {
            line = testLine;
        }
    }
    lines.push(line);
    
    // Draw title lines
    const lineHeight = 40;
    const titleStartY = titleHeight / 2 - ((lines.length - 1) * lineHeight / 2);
    lines.forEach((line, index) => {
        ctx.fillText(line.trim(), W / 2, titleStartY + (index * lineHeight));
    });
    
    // Draw waveform in center area (using bars like original)
    ctx.fillStyle = '#000';
    const waveformPad = waveformArea.height * 0.1;
    function yMap(v) { 
        return waveformArea.y + waveformArea.height - waveformPad - (v + 1) / 2 * (waveformArea.height - 2 * waveformPad); 
    }
    
    for (let x = 0; x < waveformArea.width; x++) {
        const i = Math.floor(x * currentPeaks.min.length / waveformArea.width);
        const y1 = yMap(currentPeaks.max[i]);
        const y2 = yMap(currentPeaks.min[i]);
        const lineWidth = Math.max(1, Math.floor(waveformArea.width / 800)); // Responsive line width
        ctx.fillRect(waveformArea.x + x, y1, lineWidth, Math.max(1, y2 - y1));
    }
    
    // Add QR code placeholder (will be replaced with actual QR when available)
    drawQRPlaceholder(padding, H - qrSize - padding, qrSize);
}

function drawQRPlaceholder(x, y, size) {
    const ctx = previewCanvas.getContext('2d');
    // Draw QR placeholder
    ctx.fillStyle = '#f3f4f6';
    ctx.fillRect(x, y, size, size);
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 2;
    ctx.strokeRect(x, y, size, size);
    
    // QR placeholder text
    ctx.fillStyle = '#6b7280';
    ctx.font = '12px system-ui, -apple-system, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('QR Code', x + size/2, y + size/2 - 8);
    ctx.fillText('(Generated after save)', x + size/2, y + size/2 + 8);
}

// Toggle recording
async function toggleRecording() {
    if (isRecording) {
        stopRecording();
    } else {
        startRecording();
    }
}

// Start recording
async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        
        mediaRecorder.ondataavailable = (event) => {
            audioChunks.push(event.data);
        };
        
        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
            const audioFile = new File([audioBlob], 'recording.wav', { type: 'audio/wav' });
            selectedFiles = [audioFile];
            updateFileUploadArea();
            processFileForPreview(audioFile);
            validateForm();
        };
        
        mediaRecorder.start();
        isRecording = true;
        btnRecord.classList.add('recording');
        btnRecord.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>';
        
    } catch (error) {
        console.error('Error starting recording:', error);
        alert('Could not access microphone. Please check permissions.');
    }
}

// Stop recording
function stopRecording() {
    if (mediaRecorder && isRecording) {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        isRecording = false;
        btnRecord.classList.remove('recording');
        btnRecord.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2s2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 19.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
    }
}

// Validate form
function validateForm() {
    const hasTitle = titleInput.value.trim().length > 0;
    const hasFiles = selectedFiles.length > 0;
    
    btnCreate.disabled = !(hasTitle && hasFiles);
}

// Create memory
async function createMemory() {
    if (!titleInput.value.trim() || selectedFiles.length === 0) {
        alert('Please enter a title and select audio files.');
        return;
    }
    
    const title = titleInput.value.trim();
    btnCreate.disabled = true;
    btnCreate.innerHTML = '<div class="loading-spinner" style="width: 20px; height: 20px; margin: 0 auto;"></div> Creating...';
    
    try {
        const currentUser = getCurrentUser();
        if (!currentUser) {
            throw new Error('Not authenticated');
        }
        
        // Generate unique ID
        const uniqueId = 'mw_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Get the base URL from the server (uses production URL for QR codes)
        const baseUrlResponse = await fetch('get_base_url.php');
        const baseUrlData = await baseUrlResponse.json();
        const baseUrl = baseUrlData.base_url;
        
        // Process each audio file
        for (let i = 0; i < selectedFiles.length; i++) {
            const file = selectedFiles[i];
            const baseName = file.name.replace(/\.[^/.]+$/, '');
            
            // Generate the final play URL and QR code with the unique_id (secure)
            const playPageUrl = `${baseUrl}/play.php?uid=${uniqueId}`;
            const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=1200x1200&margin=1&data=${encodeURIComponent(playPageUrl)}`;
            
            // Create waveform from audio with correct QR URL
            const waveformBlob = await createWaveformFromAudio(file, qrApiUrl);
            
            // Upload waveform image with QR code
            const waveformResult = await uploadWaveformFiles(
                waveformBlob,
                `${baseName}_composition.png`,
                currentUser.uid,
                null,
                playPageUrl
            );
            
            if (!waveformResult.success) {
                throw new Error(waveformResult.error || 'Waveform upload failed');
            }
            
            // Upload audio file
            const audioResult = await uploadWaveformFiles(
                null,
                file.name,
                currentUser.uid,
                file
            );
            
            if (!audioResult.success) {
                throw new Error(audioResult.error || 'Audio upload failed');
            }
            
            // Save to database with correct URLs
            const saveResult = await saveMemoryToDatabase({
                title: title,
                user_id: currentUser.uid,
                image_url: waveformResult.waveformUrl,
                qr_url: qrApiUrl, // Final QR URL
                audio_url: audioResult.audioUrl,
                original_name: file.name,
                play_url: playPageUrl, // Final play URL
                unique_id: uniqueId
            });
            
            if (!saveResult.success) {
                throw new Error(saveResult.error || 'Failed to save memory');
            }
        }
        
        // Show success message
        showToast('Memory created successfully!', 'success');
        
        // Reset form
        titleInput.value = '';
        selectedFiles = [];
        fileInput.value = '';
        fileUploadArea.classList.remove('has-files');
        fileUploadArea.innerHTML = `
            <div style="margin-bottom: 1rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #6b7280; margin: 0 auto;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </div>
            <p style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Drop audio files here or click to browse</p>
            <p style="color: #6b7280; font-size: 0.875rem;">Supports MP3, WAV, M4A, and other audio formats</p>
        `;
        memoryPreview.style.display = 'none';
        currentPeaks = null;
        validateForm();
        
        // Redirect to memories page
        setTimeout(() => {
            window.location.href = 'memories.html';
        }, 1500);
        
    } catch (error) {
        console.error('Error creating memory:', error);
        showToast('Failed to create memory: ' + error.message, 'error');
    } finally {
        btnCreate.disabled = false;
        btnCreate.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Create Memory
        `;
    }
}

// Create waveform from audio (original implementation)
async function createWaveformFromAudio(audioFile, qrCodeUrl = null) {
    const arrayBuffer = await audioFile.arrayBuffer();
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
    
    // Create high-resolution canvas for print quality
    const W = 3600;
    const H = 2400;
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    
    // Background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);
    
    // Compute peaks
    const peaks = computePeaksFromBuffer(audioBuffer);
    
    // Get title
    const title = titleInput.value.trim() || 'Untitled Memory';
    
    // Calculate layout (same proportions as preview)
    const titleHeight = Math.floor(H * 0.13); // 13% for title
    const qrSize = Math.floor(H * 0.2); // 20% for QR code
    const padding = Math.floor(W * 0.02); // 2% padding
    const waveformWidth = W * 0.7; // 70% of canvas width (same as preview)
    const waveformArea = {
        x: (W - waveformWidth) / 2, // Center the waveform
        y: titleHeight + padding,
        width: waveformWidth,
        height: H - titleHeight - qrSize - (padding * 3)
    };
    
    // Draw title
    ctx.fillStyle = '#0b0d12';
    const fontSize = Math.floor(W * 0.024); // Responsive font size
    ctx.font = `bold ${fontSize}px system-ui, -apple-system, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Word wrap title
    const maxTitleWidth = W - (padding * 2);
    const words = title.split(' ');
    let line = '';
    let lines = [];
    
    for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' ';
        const metrics = ctx.measureText(testLine);
        const testWidth = metrics.width;
        
        if (testWidth > maxTitleWidth && n > 0) {
            lines.push(line);
            line = words[n] + ' ';
        } else {
            line = testLine;
        }
    }
    lines.push(line);
    
    // Draw title lines
    const lineHeight = fontSize * 1.2;
    const titleStartY = titleHeight / 2 - ((lines.length - 1) * lineHeight / 2);
    lines.forEach((line, index) => {
        ctx.fillText(line.trim(), W / 2, titleStartY + (index * lineHeight));
    });
    
    // Draw waveform (using bars like original)
    ctx.fillStyle = '#000';
    const waveformPad = waveformArea.height * 0.1;
    function yMap(v) { 
        return waveformArea.y + waveformArea.height - waveformPad - (v + 1) / 2 * (waveformArea.height - 2 * waveformPad); 
    }
    
    for (let x = 0; x < waveformArea.width; x++) {
        const i = Math.floor(x * peaks.min.length / waveformArea.width);
        const y1 = yMap(peaks.max[i]);
        const y2 = yMap(peaks.min[i]);
        const lineWidth = Math.max(1, Math.floor(waveformArea.width / 800)); // Responsive line width
        ctx.fillRect(waveformArea.x + x, y1, lineWidth, Math.max(1, y2 - y1));
    }
    
    // Draw QR code if provided
    if (qrCodeUrl) {
        try {
            await drawQRCode(ctx, padding, H - qrSize - padding, qrSize, qrCodeUrl);
        } catch (error) {
            console.error('Failed to draw QR code:', error);
            // Draw placeholder if QR fails
            drawQRPlaceholderOnCanvas(ctx, padding, H - qrSize - padding, qrSize);
        }
    } else {
        drawQRPlaceholderOnCanvas(ctx, padding, H - qrSize - padding, qrSize);
    }
    
    return new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
}

// Function to draw QR placeholder on export canvas
function drawQRPlaceholderOnCanvas(ctx, x, y, size) {
    ctx.fillStyle = '#f3f4f6';
    ctx.fillRect(x, y, size, size);
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 3;
    ctx.strokeRect(x, y, size, size);
    
    ctx.fillStyle = '#6b7280';
    const fontSize = Math.floor(size * 0.08);
    ctx.font = `${fontSize}px system-ui, -apple-system, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('QR Code', x + size/2, y + size/2);
}

// Draw QR code
async function drawQRCode(ctx, x, y, size, qrUrl) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            ctx.drawImage(img, x, y, size, size);
            resolve();
        };
        img.onerror = reject;
        img.src = qrUrl;
    });
}

// Save memory to database
async function saveMemoryToDatabase(memoryData) {
    try {
        const formData = new FormData();
        formData.append('title', memoryData.title);
        formData.append('user_id', memoryData.user_id);
        formData.append('image_url', memoryData.image_url);
        formData.append('qr_url', memoryData.qr_url);
        formData.append('audio_url', memoryData.audio_url);
        formData.append('original_name', memoryData.original_name);
        formData.append('play_url', memoryData.play_url);
        
        if (memoryData.unique_id) {
            formData.append('unique_id', memoryData.unique_id);
        }
        
        const response = await fetch('upload.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        return result;
        
    } catch (error) {
        console.error('Error saving to database:', error);
        return { success: false, error: error.message };
    }
}


// Load user waveforms
async function loadUserWaveforms() {
    try {
        const currentUser = getCurrentUser();
        if (!currentUser) return;
        
        const response = await fetch(`get_waveforms.php?user_id=${encodeURIComponent(currentUser.uid)}`);
        const data = await response.json();
        
        if (data.waveforms && data.waveforms.length > 0) {
            displayWaveforms(data.waveforms);
        } else {
            waveformList.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 2rem;">No memories found. Create your first memory above!</p>';
        }
        
    } catch (error) {
        console.error('Error loading waveforms:', error);
        waveformList.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 2rem;">Error loading memories. Please refresh the page.</p>';
    }
}

// Display waveforms
function displayWaveforms(waveforms) {
    if (waveforms.length === 0) {
        waveformList.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 2rem;">No memories found. Create your first memory above!</p>';
        return;
    }
    
    const waveformsHTML = waveforms.map(waveform => `
        <div class="waveform-item">
            <div class="waveform-info">
                <div class="waveform-title">${waveform.title || 'Untitled'}</div>
                <div class="waveform-date">${new Date(waveform.created_at).toLocaleDateString()}</div>
            </div>
            <div class="waveform-actions">
                <a href="${waveform.image_url}" target="_blank" class="action-link">View</a>
                <a href="${waveform.qr_url}" target="_blank" class="action-link">QR</a>
                <a href="#" onclick="deleteWaveform(${waveform.id})" class="action-link delete">Delete</a>
            </div>
        </div>
    `).join('');
    
    waveformList.innerHTML = waveformsHTML;
}

// Delete waveform
window.deleteWaveform = async function(waveformId) {
    if (!confirm('Are you sure you want to delete this memory?')) return;
    
    try {
        const currentUser = getCurrentUser();
        if (!currentUser) return;
        
        const response = await fetch('delete_memory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `memory_id=${waveformId}&user_id=${encodeURIComponent(currentUser.uid)}`
        });
        
        if (!response.ok) {
            throw new Error('Delete request failed');
        }
        
        const result = await response.json();
        if (result.success) {
            showToast('Memory deleted successfully', 'success');
            loadUserWaveforms(); // Reload the list
        } else {
            throw new Error(result.error || 'Delete failed');
        }
        
    } catch (error) {
        console.error('Error deleting waveform:', error);
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
window.initApp = initApp;
window.loadUserWaveforms = loadUserWaveforms;

