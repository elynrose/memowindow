// App-specific functionality for memory creation
import unifiedAuth from './unified-auth.js';
import { uploadWaveformFiles } from './storage.js';

// Global variables
let selectedFiles = [];
let currentPeaks = null;
let isRecording = false;
let mediaRecorder = null;
let audioChunks = [];

// Audio limit and countdown variables
let maxAudioLength = 100; // Default Basic plan limit
let countdownInterval = null;
let recordingStartTime = null;

// DOM elements
let fileInput, fileUploadArea, btnRecord, btnCreate, titleInput, waveformList, memoryPreview, previewCanvas, previewStatus;
let audioLimitInfo, countdownTimer, timeRemaining, progressBar, packageName, maxLength, recordIcon;

// Initialize app functionality
export function initApp() {
    // Initializing app functionality
    
    // Get DOM elements
    fileInput = document.getElementById('fileInput');
    fileUploadArea = document.getElementById('fileUploadArea');
    btnRecord = document.getElementById('btnRecord');
    recordIcon = document.getElementById('recordIcon');
    btnCreate = document.getElementById('btnCreate');
    titleInput = document.getElementById('titleInput');
    waveformList = document.getElementById('waveformList');
    memoryPreview = document.getElementById('memoryPreview');
    previewCanvas = document.getElementById('previewCanvas');
    previewStatus = document.getElementById('previewStatus');
    
    // Countdown timer elements
    audioLimitInfo = document.getElementById('audioLimitInfo');
    countdownTimer = document.getElementById('countdownTimer');
    timeRemaining = document.getElementById('timeRemaining');
    progressBar = document.getElementById('progressBar');
    packageName = document.getElementById('packageName');
    maxLength = document.getElementById('maxLength');
    
    if (!fileInput || !fileUploadArea || !btnRecord || !btnCreate) {
        return;
    }
    
    // Set up event listeners
    setupEventListeners();
    
    // Wait for authentication and then load user's waveforms and audio limit
    waitForAuthAndLoadWaveforms();
    
    // App functionality initialized
}

// Wait for authentication and then load waveforms
async function waitForAuthAndLoadWaveforms() {
    await unifiedAuth.waitForAuth();
    const currentUser = unifiedAuth.getCurrentUser();
    
    if (currentUser) {
        loadUserWaveforms();
        loadUserAudioLimit();
    } else {
    }
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
async function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    if (files.length > 0) {
        // Validate each audio file
        let validFiles = [];
        for (const file of files) {
            if (file.type.startsWith('audio/')) {
                const isValid = await validateAudioFile(file);
                if (isValid) {
                    validFiles.push(file);
                }
            } else {
                validFiles.push(file); // Non-audio files are always valid
            }
        }
        
        if (validFiles.length > 0) {
            selectedFiles = validFiles;
            updateFileUploadArea();
            processFileForPreview(validFiles[0]);
            validateForm();
        } else {
            // Clear the file input if no valid files
            fileInput.value = '';
        }
    }
}

// Handle drag over
function handleDragOver(event) {
    event.preventDefault();
    fileUploadArea.style.borderColor = '#667eea';
    fileUploadArea.style.background = '#f0f4ff';
}

// Handle drop
async function handleDrop(event) {
    event.preventDefault();
    const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('audio/'));
    if (files.length > 0) {
        // Validate each audio file
        let validFiles = [];
        for (const file of files) {
            const isValid = await validateAudioFile(file);
            if (isValid) {
                validFiles.push(file);
            }
        }
        
        if (validFiles.length > 0) {
            selectedFiles = validFiles;
            fileInput.files = event.dataTransfer.files;
            updateFileUploadArea();
            processFileForPreview(validFiles[0]);
            validateForm();
        }
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
    }
}

// High-quality peak computation with enhanced resolution and noise filtering
function computePeaksFromBuffer(buf, width = 2000) {
    const channels = buf.numberOfChannels;
    const sampleRate = buf.sampleRate;
    
    // Use multiple channels if available (mix to mono)
    let audioData;
    if (channels === 1) {
        audioData = buf.getChannelData(0);
    } else {
        // Mix multiple channels to mono for better waveform representation
        const length = buf.length;
        audioData = new Float32Array(length);
        for (let i = 0; i < length; i++) {
            let sum = 0;
            for (let ch = 0; ch < channels; ch++) {
                sum += buf.getChannelData(ch)[i];
            }
            audioData[i] = sum / channels;
        }
    }
    
    const hop = Math.max(1, Math.floor(buf.length / width));
    const min = new Float32Array(width);
    const max = new Float32Array(width);
    
    // Enhanced peak detection with RMS calculation for better visual representation
    for (let x = 0; x < width; x++) {
        const start = x * hop;
        const end = Math.min((x + 1) * hop, buf.length);
        let minVal = 1.0;
        let maxVal = -1.0;
        let rmsSum = 0;
        let sampleCount = 0;
        
        // Calculate both peaks and RMS for this segment
        for (let i = start; i < end; i++) {
            const sample = audioData[i];
            if (sample < minVal) minVal = sample;
            if (sample > maxVal) maxVal = sample;
            rmsSum += sample * sample;
            sampleCount++;
        }
        
        // Apply light smoothing based on RMS to reduce noise
        const rms = Math.sqrt(rmsSum / sampleCount);
        const smoothingFactor = Math.min(rms * 2, 0.1);
        
        // Smooth peaks slightly to reduce harsh digital artifacts
        if (x > 0 && x < width - 1) {
            const prevMin = min[x - 1] || minVal;
            const prevMax = max[x - 1] || maxVal;
            
            minVal = minVal * (1 - smoothingFactor) + prevMin * smoothingFactor;
            maxVal = maxVal * (1 - smoothingFactor) + prevMax * smoothingFactor;
        }
        
        min[x] = minVal;
        max[x] = maxVal;
    }
    
    return { 
        min, 
        max, 
        sampleRate,
        channels,
        duration: buf.duration
    };
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
        
        // Start countdown timer
        startCountdownTimer();
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Microphone Access Required',
            text: 'Could not access microphone. Please check permissions.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#667eea'
        });
    }
}

// Stop recording
function stopRecording() {
    if (mediaRecorder && isRecording) {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        isRecording = false;
        btnRecord.classList.remove('recording');
        btnRecord.innerHTML = '<svg id="recordIcon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>';
        
        // Stop countdown timer
        stopCountdownTimer();
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
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please enter a title and select audio files.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#667eea'
        });
        return;
    }
    
    const title = titleInput.value.trim();
    btnCreate.disabled = true;
    btnCreate.innerHTML = '<div class="loading-spinner" style="width: 20px; height: 20px; margin: 0 auto;"></div> Creating...';
    
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        if (!currentUser) {
            throw new Error('Not authenticated');
        }
        
        // Check subscription limits (simplified for testing)
        try {
            const subscriptionResponse = await fetch(`check_subscription_simple.php`, {
                credentials: 'include' // Include cookies for session management
            });
            const subscriptionData = await subscriptionResponse.json();
            
            if (!subscriptionResponse.ok) {
            } else if (subscriptionData.success && !subscriptionData.limits.can_create_memory.allowed) {
                throw new Error(subscriptionData.limits.can_create_memory.reason);
            }
        } catch (subError) {
            // Continue with upload even if subscription check fails
        }
        
        // Generate unique ID
        const uniqueId = 'mw_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Get the base URL from the server (uses production URL for QR codes)
        let baseUrl;
        try {
            const baseUrlResponse = await fetch('get_base_url.php');
            const baseUrlData = await baseUrlResponse.json();
            baseUrl = baseUrlData.base_url;
        } catch (urlError) {
            baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
        }
        
        // Process each audio file
        for (let i = 0; i < selectedFiles.length; i++) {
            const file = selectedFiles[i];
            const baseName = file.name.replace(/\.[^/.]+$/, '');
            
            // Generate the final play URL - QR code will be generated server-side
            const playPageUrl = `${baseUrl}/play.php?uid=${uniqueId}`;
            
            // Create waveform from audio with real QR code
            const waveformBlob = await createWaveformFromAudio(file, playPageUrl);
            
            if (!waveformBlob || waveformBlob.size === 0) {
                throw new Error('Failed to generate waveform image');
            }
            
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
            
            // Save to database with correct URLs (QR code will be generated server-side)
            const saveResult = await saveMemoryToDatabase({
                title: title,
                user_id: currentUser.uid,
                image_url: waveformResult.waveformUrl,
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
            window.location.href = 'memories.php';
        }, 1500);
        
    } catch (error) {
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

// Professional-quality waveform generation with enhanced visual rendering
async function createWaveformFromAudio(audioFile, playPageUrl = null) {
    const arrayBuffer = await audioFile.arrayBuffer();
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
    
    // Create ultra-high-resolution canvas for professional print quality
    const W = 3600; // 15 inches at 240 DPI
    const H = 2400; // 10 inches at 240 DPI
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    
    // Enable high-quality rendering
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.textRenderingOptimization = 'optimizeQuality';
    
    // Premium white background with subtle texture
    const gradient = ctx.createLinearGradient(0, 0, 0, H);
    gradient.addColorStop(0, '#ffffff');
    gradient.addColorStop(1, '#fafafa');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, W, H);
    
    // Compute enhanced peaks with high resolution
    const peaks = computePeaksFromBuffer(audioBuffer, Math.floor(W * 0.8)); // Higher resolution for print
    
    // Get title
    const title = titleInput.value.trim() || 'Untitled Memory';
    
    // Calculate precise layout with golden ratio proportions
    const titleHeight = Math.floor(H * 0.13); // 13% for title
    const padding = Math.floor(W * 0.02); // 2% padding
    const waveformWidth = W * 0.8; // 80% of canvas width (more space without QR)
    const waveformArea = {
        x: (W - waveformWidth) / 2, // Center the waveform
        y: titleHeight + padding,
        width: waveformWidth,
        height: H - titleHeight - (padding * 2) // More height without QR section
    };
    
    // Draw professional title with enhanced typography
    ctx.fillStyle = '#0b0d12';
    const fontSize = Math.floor(W * 0.028); // Larger font for print quality
    ctx.font = `600 ${fontSize}px "SF Pro Display", system-ui, -apple-system, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Enhanced word wrapping with better line breaks
    const maxTitleWidth = W - (padding * 2);
    const words = title.split(' ');
    let line = '';
    let lines = [];
    
    for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' ';
        const metrics = ctx.measureText(testLine);
        const testWidth = metrics.width;
        
        if (testWidth > maxTitleWidth && n > 0) {
            lines.push(line.trim());
            line = words[n] + ' ';
        } else {
            line = testLine;
        }
    }
    lines.push(line.trim());
    
    // Draw title with subtle shadow for depth
    const lineHeight = fontSize * 1.15;
    const titleStartY = titleHeight / 2 - ((lines.length - 1) * lineHeight / 2);
    
    lines.forEach((line, index) => {
        const y = titleStartY + (index * lineHeight);
        
        // Subtle shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.fillText(line, W / 2 + 2, y + 2);
        
        // Main text
        ctx.fillStyle = '#0b0d12';
        ctx.fillText(line, W / 2, y);
    });
    
    // Draw professional waveform with gradient and anti-aliasing
    const waveformGradient = ctx.createLinearGradient(0, waveformArea.y, 0, waveformArea.y + waveformArea.height);
    waveformGradient.addColorStop(0, '#1a1a1a');
    waveformGradient.addColorStop(0.5, '#333333');
    waveformGradient.addColorStop(1, '#1a1a1a');
    ctx.fillStyle = waveformGradient;
    
    const waveformPad = waveformArea.height * 0.08; // Slightly less padding for more visual impact
    const centerY = waveformArea.y + waveformArea.height / 2;
    
    function yMap(v) { 
        const normalizedY = waveformArea.y + waveformArea.height - waveformPad - (v + 1) / 2 * (waveformArea.height - 2 * waveformPad);
        return normalizedY;
    }
    
    // Enhanced waveform rendering with variable line widths and smooth edges
    const barWidth = Math.max(1, Math.floor(waveformArea.width / peaks.min.length * 0.8));
    const barSpacing = Math.max(0, Math.floor(waveformArea.width / peaks.min.length * 0.2));
    
    for (let x = 0; x < waveformArea.width; x += barWidth + barSpacing) {
        const peakIndex = Math.floor(x * peaks.min.length / waveformArea.width);
        const y1 = yMap(peaks.max[peakIndex]);
        const y2 = yMap(peaks.min[peakIndex]);
        const height = Math.max(2, y2 - y1); // Minimum height for visual consistency
        
        // Create subtle rounded rectangles for smoother appearance
        const barX = waveformArea.x + x;
        const radius = Math.min(barWidth / 2, 2);
        
        ctx.beginPath();
        ctx.roundRect(barX, y1, barWidth, height, radius);
        ctx.fill();
    }
    
    // Add subtle center line for reference
    ctx.strokeStyle = 'rgba(0, 0, 0, 0.05)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(waveformArea.x, centerY);
    ctx.lineTo(waveformArea.x + waveformArea.width, centerY);
    ctx.stroke();
    
    // Draw QR code in bottom left section if play URL is available
    if (playPageUrl) {
        const qrSize = Math.floor(H * 0.15); // Smaller QR code
        const qrPadding = 100; // Minimum 100px padding from edges
        const qrX = qrPadding; // Left side with padding
        const qrY = H - qrSize - qrPadding; // Bottom with padding
        
        try {
            // Draw QR code background with professional styling
            const borderWidth = 8;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(qrX - borderWidth, qrY - borderWidth, qrSize + borderWidth * 2, qrSize + borderWidth * 2);
            
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 2;
            ctx.strokeRect(qrX - borderWidth, qrY - borderWidth, qrSize + borderWidth * 2, qrSize + borderWidth * 2);
            
            // Generate and draw real QR code
            await drawQRCode(ctx, qrX, qrY, qrSize, playPageUrl);
        } catch (error) {
            console.error('QR code generation failed:', error);
            drawEnhancedQRPlaceholder(ctx, qrX, qrY, qrSize);
        }
    }
    
    // Add subtle watermark/branding in corner
    ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
    ctx.font = `${Math.floor(W * 0.008)}px system-ui, -apple-system, sans-serif`;
    ctx.textAlign = 'right';
    ctx.textBaseline = 'bottom';
    ctx.fillText('MemoWindow', W - padding, H - padding / 2);
    
    return new Promise(resolve => {
        canvas.toBlob(resolve, 'image/png', 1.0); // Maximum quality
    });
}

// Generate real QR code using qrcode.js library
async function generateRealQRCode(data, size = 200) {
    try {
        // Check if QRCode library is loaded
        if (typeof QRCode === 'undefined') {
            console.error('QRCode library not loaded');
            return null;
        }
        
        // Create a temporary div element for QR code generation
        const tempDiv = document.createElement('div');
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        tempDiv.style.top = '-9999px';
        document.body.appendChild(tempDiv);
        
        // Generate QR code using the davidshimjs/qrcodejs library
        const qrCode = new QRCode(tempDiv, {
            text: data,
            width: size,
            height: size,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
        
        // Wait for QR code to be generated
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Get the canvas from the generated QR code
        const canvas = tempDiv.querySelector('canvas');
        
        // Clean up temporary div
        document.body.removeChild(tempDiv);
        
        return canvas;
    } catch (error) {
        console.error('QR code generation failed:', error);
        return null;
    }
}

// Enhanced QR placeholder for professional appearance
function drawEnhancedQRPlaceholder(ctx, x, y, size) {
    // Background with gradient
    const gradient = ctx.createLinearGradient(x, y, x + size, y + size);
    gradient.addColorStop(0, '#f8fafc');
    gradient.addColorStop(1, '#e2e8f0');
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, size, size);
    
    // Professional border
    ctx.strokeStyle = '#cbd5e0';
    ctx.lineWidth = 2;
    ctx.strokeRect(x, y, size, size);
    
    // QR code pattern placeholder
    const patternSize = size * 0.15;
    ctx.fillStyle = '#4a5568';
    
    // Corner squares
    [[x + size * 0.1, y + size * 0.1], 
     [x + size * 0.75, y + size * 0.1], 
     [x + size * 0.1, y + size * 0.75]].forEach(([px, py]) => {
        ctx.fillRect(px, py, patternSize, patternSize);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(px + patternSize * 0.3, py + patternSize * 0.3, patternSize * 0.4, patternSize * 0.4);
        ctx.fillStyle = '#4a5568';
    });
    
    // Center text
    ctx.fillStyle = '#718096';
    const fontSize = Math.floor(size * 0.06);
    ctx.font = `500 ${fontSize}px system-ui, -apple-system, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('QR Code', x + size/2, y + size/2 + size * 0.05);
    ctx.fillText('Generated', x + size/2, y + size/2 + size * 0.15);
}

// Fallback QR placeholder for export canvas
function drawQRPlaceholderOnCanvas(ctx, x, y, size) {
    drawEnhancedQRPlaceholder(ctx, x, y, size);
}

// Draw QR code using real QR code generation
async function drawQRCode(ctx, x, y, size, qrData) {
    try {
        // Generate real QR code using qrcode.js library
        const qrCanvas = await generateRealQRCode(qrData, size);
        
        if (qrCanvas) {
            // Draw the generated QR code onto the main canvas
            ctx.drawImage(qrCanvas, x, y, size, size);
            return;
        }
        
        // Fallback to placeholder if QR generation fails
        drawEnhancedQRPlaceholder(ctx, x, y, size);
    } catch (error) {
        console.error('QR code drawing failed:', error);
        // Fallback to placeholder
        drawEnhancedQRPlaceholder(ctx, x, y, size);
    }
}

// Save memory to database (simplified version for testing)
async function saveMemoryToDatabase(memoryData) {
    try {
        
        const formData = new FormData();
        formData.append('title', memoryData.title);
        formData.append('user_id', memoryData.user_id);
        formData.append('image_url', memoryData.image_url);
        formData.append('audio_url', memoryData.audio_url || '');
        formData.append('original_name', memoryData.original_name || '');
        formData.append('play_url', memoryData.play_url || '');
        
        if (memoryData.unique_id) {
            formData.append('unique_id', memoryData.unique_id);
        }
        
        const response = await fetch('upload_simple.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        return result;
        
    } catch (error) {
        return { success: false, error: error.message };
    }
}


// Load user waveforms
async function loadUserWaveforms() {
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        if (!currentUser) return;
        
        const response = await fetch(`get_waveforms.php`);
        const data = await response.json();
        
        if (data.waveforms && data.waveforms.length > 0) {
            displayWaveforms(data.waveforms);
        } else {
            waveformList.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 2rem;">No memories found. Create your first memory above!</p>';
        }
        
    } catch (error) {
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
    const result = await Swal.fire({
        title: 'Delete Memory?',
        text: 'Are you sure you want to delete this memory?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        if (!currentUser) return;
        
        const response = await fetch('delete_memory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'include', // Include cookies for session management
            body: `memory_id=${waveformId}`
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

// Load user's audio length limit
async function loadUserAudioLimit() {
    try {
        const currentUser = unifiedAuth.getCurrentUser();
        if (!currentUser) {
            // Show default limit info even when not logged in
            if (packageName && maxLength) {
                packageName.textContent = 'Basic';
                maxLength.textContent = '100';
                audioLimitInfo.style.display = 'block';
            }
            return;
        }
        
        const response = await fetch(`get_user_audio_limit.php`, {
            credentials: 'include' // Include cookies for session management
        });
        const data = await response.json();
        
        
        if (data.success) {
            maxAudioLength = data.max_audio_length_seconds;
            
            // Update the UI
            if (packageName && maxLength) {
                packageName.textContent = data.package_name;
                maxLength.textContent = maxAudioLength;
                audioLimitInfo.style.display = 'block';
            } else {
            }
            
        } else {
        }
    } catch (error) {
    }
}

// Start countdown timer
function startCountdownTimer() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    recordingStartTime = Date.now();
    countdownTimer.style.display = 'block';
    
    countdownInterval = setInterval(() => {
        const elapsed = (Date.now() - recordingStartTime) / 1000;
        const remaining = Math.max(0, maxAudioLength - elapsed);
        
        // Update time display
        const minutes = Math.floor(remaining / 60);
        const seconds = Math.floor(remaining % 60);
        timeRemaining.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Update progress bar
        const progress = (remaining / maxAudioLength) * 100;
        progressBar.style.width = `${progress}%`;
        
        // Change color as time runs out
        if (remaining <= 10) {
            timeRemaining.style.color = '#ef4444';
            progressBar.style.background = '#ef4444';
        } else if (remaining <= 30) {
            timeRemaining.style.color = '#f59e0b';
            progressBar.style.background = 'linear-gradient(90deg, #f59e0b, #ef4444)';
        } else {
            timeRemaining.style.color = '#ef4444';
            progressBar.style.background = 'linear-gradient(90deg, #22c55e, #ef4444)';
        }
        
        // Auto-stop recording when time runs out
        if (remaining <= 0) {
            stopRecording();
            Swal.fire({
                icon: 'warning',
                title: 'Time Limit Reached',
                text: `Your ${packageName.textContent} allows up to ${maxAudioLength} seconds. Recording stopped automatically.`,
                confirmButtonText: 'OK',
                confirmButtonColor: '#667eea'
            });
        }
    }, 100);
}

// Stop countdown timer
function stopCountdownTimer() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    countdownTimer.style.display = 'none';
    recordingStartTime = null;
}

// Get audio duration from file
function getAudioDuration(file) {
    return new Promise((resolve, reject) => {
        const audio = new Audio();
        const url = URL.createObjectURL(file);
        
        audio.addEventListener('loadedmetadata', () => {
            URL.revokeObjectURL(url);
            resolve(audio.duration);
        });
        
        audio.addEventListener('error', () => {
            URL.revokeObjectURL(url);
            reject(new Error('Could not load audio file'));
        });
        
        audio.src = url;
    });
}

// Validate audio file length
async function validateAudioFile(file) {
    try {
        const duration = await getAudioDuration(file);
        const durationSeconds = Math.ceil(duration);
        
        
        if (durationSeconds > maxAudioLength) {
            const minutes = Math.floor(durationSeconds / 60);
            const seconds = durationSeconds % 60;
            const limitMinutes = Math.floor(maxAudioLength / 60);
            const limitSeconds = maxAudioLength % 60;
            
            const durationText = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
            const limitText = limitMinutes > 0 ? `${limitMinutes}m ${limitSeconds}s` : `${limitSeconds}s`;
            
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Audio File Too Long',
                html: `
                    <p>Your audio file is <strong>${durationText}</strong> long.</p>
                    <p>Your <strong>${packageName.textContent}</strong> allows up to <strong>${limitText}</strong>.</p>
                    <p>Please upgrade your plan to upload longer audio files.</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Upgrade Plan',
                cancelButtonText: 'Choose Different File',
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6b7280'
            });
            
            if (result.isConfirmed) {
                // Redirect to subscription page
                window.location.href = 'subscription_checkout.php';
            }
            
            return false;
        }
        
        return true;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not read audio file. Please try a different file.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#667eea'
        });
        return false;
    }
}

// Make functions available globally
window.initApp = initApp;
window.loadUserWaveforms = loadUserWaveforms;

