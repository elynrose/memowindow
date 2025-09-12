// Firebase Storage functionality
import { ref, uploadBytes, getDownloadURL, deleteObject } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-storage.js';
import { storage } from '../firebase-config.php';

// Generate QR code using external service
async function generateQRCode(imageUrl) {
  const qrParams = new URLSearchParams({
    size: '512x512',
    margin: '1',
    data: imageUrl,
  });
  
  try {
    const response = await fetch(`https://api.qrserver.com/v1/create-qr-code/?${qrParams}`);
    if (!response.ok) throw new Error('QR service error');
    return await response.blob();
  } catch (error) {
    console.error('QR generation failed:', error);
    throw new Error('Failed to generate QR code');
  }
}

// Upload file to Firebase Storage (primary) and local backup
export async function uploadToFirebaseStorage(blob, fileName, folder = 'waveforms') {
  try {
    // Upload to Firebase Storage (primary)
    const storageRef = ref(storage, `${folder}/${fileName}`);
    const snapshot = await uploadBytes(storageRef, blob);
    const downloadURL = await getDownloadURL(snapshot.ref);
    
    // Also save to local backup (non-blocking)
    try {
      await saveToLocalBackup(blob, fileName, folder);
    } catch (backupError) {
      console.warn('⚠️ Local backup failed (non-critical):', backupError.message);
      // Don't throw - backup failure shouldn't break the main upload
    }
    
    return downloadURL;
  } catch (error) {
    console.error('Firebase Storage upload failed:', error);
    
    // Provide specific error messages for common production issues
    if (error.code === 'storage/unauthorized') {
      throw new Error('Permission denied. Please check Firebase Storage rules allow authenticated uploads.');
    } else if (error.code === 'storage/unauthenticated') {
      throw new Error('Authentication required. Please sign in first.');
    } else if (error.code === 'storage/quota-exceeded') {
      throw new Error('Storage quota exceeded. Please contact administrator.');
    } else if (error.code === 'storage/invalid-format') {
      throw new Error('Invalid file format. Only PNG images are supported.');
    } else {
      throw new Error(`Firebase Storage error: ${error.message}`);
    }
  }
}

// Save file to local backup storage
async function saveToLocalBackup(blob, fileName, folder) {
  try {
    // Convert blob to base64 for transmission
    const arrayBuffer = await blob.arrayBuffer();
    const base64Data = btoa(String.fromCharCode(...new Uint8Array(arrayBuffer)));
    
    const response = await fetch('backup_storage.php?action=save_blob', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        blob_data: base64Data,
        folder: folder,
        filename: fileName
      })
    });
    
    if (!response.ok) {
      throw new Error(`Backup API error: ${response.status}`);
    }
    
    const result = await response.json();
    if (!result.success) {
      throw new Error(result.error || 'Backup save failed');
    }
    
    // File backed up locally
    return result;
    
  } catch (error) {
    console.error('Local backup failed:', error);
    throw error;
  }
}

// Generate secure hash for user paths (privacy protection)
function generateSecureUserHash(userId) {
  // Create a secure hash using the user ID and a app-specific salt
  const appSalt = 'MemoWindow_2024_SecureHash'; // This should be in environment variables
  const combined = userId + appSalt;
  
  // Simple SHA-256 equivalent using built-in crypto if available
  if (crypto && crypto.subtle) {
    return crypto.subtle.digest('SHA-256', new TextEncoder().encode(combined))
      .then(hashBuffer => {
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        return hashHex.substring(0, 16); // Use first 16 characters
      });
  } else {
    // Fallback simple hash for environments without crypto.subtle
    let hash = 0;
    for (let i = 0; i < combined.length; i++) {
      const char = combined.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash; // Convert to 32bit integer
    }
    return Promise.resolve(Math.abs(hash).toString(16).substring(0, 16));
  }
}

// Upload waveform, audio file, and generate QR code with enhanced security
export async function uploadWaveformFiles(waveformBlob, originalFileName, userId, audioFile = null, playPageUrl = null) {
  try {
    // Generate secure user hash for privacy-protected file paths
    const userHash = await generateSecureUserHash(userId);
    const timestamp = Date.now();
    const randomSuffix = Math.random().toString(36).substring(2, 8);
    const baseName = originalFileName ? originalFileName.replace(/\.[^.]+$/, '').replace(/[^a-zA-Z0-9]/g, '_') : 'memory';
    
    // Create secure, privacy-protected file names
    const waveformFileName = `${userHash}/${timestamp}_${randomSuffix}_${baseName}_waveform.png`;
    const qrFileName = `${userHash}/${timestamp}_${randomSuffix}_${baseName}_qr.png`;
    
    let waveformUrl = null;
    
    // Upload waveform image if provided
    if (waveformBlob) {
      waveformUrl = await uploadToFirebaseStorage(waveformBlob, waveformFileName, 'waveforms');
    }
    
    let audioUrl = null;
    
    // Upload original audio file if provided
    if (audioFile) {
      const audioExtension = audioFile.name.split('.').pop().toLowerCase();
      const audioFileName = `${userHash}/${timestamp}_${randomSuffix}_${baseName}_audio.${audioExtension}`;
      audioUrl = await uploadToFirebaseStorage(audioFile, audioFileName, 'audio');
    }
    
    // Generate QR code if playPageUrl is provided
    let qrUrl = null;
    if (playPageUrl) {
      const qrBlob = await generateQRCode(playPageUrl);
      qrUrl = await uploadToFirebaseStorage(qrBlob, qrFileName, 'qr-codes');
    }
    
    // Log successful upload with hashed user ID for privacy
    const userHashShort = userHash.substring(0, 8);
    console.log(`🔒 Upload successful for user ${userHashShort}...`);
    
    return {
      waveformUrl,
      qrUrl,
      audioUrl,
      playPageUrl,
      success: true,
      metadata: {
        userHash: userHashShort,
        timestamp,
        filesUploaded: {
          waveform: !!waveformBlob,
          audio: !!audioFile,
          qr: !!playPageUrl
        }
      }
    };
  } catch (error) {
    console.error('🚨 Upload process failed:', error);
    return {
      error: error.message,
      success: false
    };
  }
}

// Delete file from Firebase Storage and local backup
export async function deleteFromFirebaseStorage(storagePath) {
  try {
    // Delete from Firebase Storage (primary)
    const storageRef = ref(storage, storagePath);
    await deleteObject(storageRef);
    
    // Also delete from local backup (non-blocking)
    try {
      await deleteFromLocalBackup(storagePath);
    } catch (backupError) {
      console.warn('⚠️ Local backup delete failed (non-critical):', backupError.message);
      // Don't throw - backup delete failure shouldn't break the main delete
    }
    
    return true;
  } catch (error) {
    console.error('❌ Failed to delete from Firebase Storage:', error);
    
    if (error.code === 'storage/object-not-found') {
      return true; // Consider this a success
    }
    
    return false;
  }
}

// Delete file from local backup storage
async function deleteFromLocalBackup(storagePath) {
  try {
    // Extract filename from storage path
    const fileName = storagePath.split('/').pop();
    const folder = storagePath.split('/')[0];
    
    const response = await fetch(`backup_storage.php?action=delete_file&path=backups/${folder}/${fileName}`, {
      method: 'DELETE'
    });
    
    if (!response.ok) {
      throw new Error(`Backup delete API error: ${response.status}`);
    }
    
    const result = await response.json();
    if (result.success) {
      // File deleted from local backup
    }
    
    return result.success;
    
  } catch (error) {
    console.error('Local backup delete failed:', error);
    throw error;
  }
}

// Delete multiple files from Firebase Storage
export async function deleteMemoryFiles(imageUrl, audioUrl) {
  const results = [];
  
  try {
    // Extract and delete image file
    if (imageUrl && imageUrl.includes('firebasestorage.googleapis.com')) {
      const imagePath = extractStoragePath(imageUrl);
      if (imagePath) {
        const imageDeleted = await deleteFromFirebaseStorage(imagePath);
        results.push({ type: 'image', path: imagePath, success: imageDeleted });
      }
    }
    
    // Extract and delete audio file
    if (audioUrl && audioUrl.includes('firebasestorage.googleapis.com')) {
      const audioPath = extractStoragePath(audioUrl);
      if (audioPath) {
        const audioDeleted = await deleteFromFirebaseStorage(audioPath);
        results.push({ type: 'audio', path: audioPath, success: audioDeleted });
      }
    }
    
    return {
      success: true,
      results: results
    };
    
  } catch (error) {
    console.error('Error deleting memory files:', error);
    return {
      success: false,
      error: error.message,
      results: results
    };
  }
}

// Helper function to extract storage path from Firebase URL
function extractStoragePath(url) {
  try {
    // Firebase Storage URLs: https://firebasestorage.googleapis.com/v0/b/bucket/o/path%2Fto%2Ffile.ext?alt=media&token=...
    const match = url.match(/\/o\/([^?]+)/);
    return match ? decodeURIComponent(match[1]) : null;
  } catch (error) {
    console.error('Error extracting storage path:', error);
    return null;
  }
}
