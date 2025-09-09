// Firebase Storage functionality
import { ref, uploadBytes, getDownloadURL, deleteObject } from 'firebase/storage';
import { storage } from './firebase-config.js';

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

// Upload file to Firebase Storage
async function uploadToFirebaseStorage(blob, fileName, folder = 'waveforms') {
  try {
    const storageRef = ref(storage, `${folder}/${fileName}`);
    const snapshot = await uploadBytes(storageRef, blob);
    const downloadURL = await getDownloadURL(snapshot.ref);
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

// Upload waveform, audio file, and generate QR code
export async function uploadWaveformFiles(waveformBlob, originalFileName, userId, audioFile = null) {
  try {
    // Generate unique filenames
    const timestamp = Date.now();
    const baseName = originalFileName.replace(/\.[^.]+$/, '');
    const waveformFileName = `${userId}_${timestamp}_${baseName}_waveform.png`;
    const qrFileName = `${userId}_${timestamp}_${baseName}_qr.png`;
    
    let waveformUrl = null;
    
    // Upload waveform image if provided
    if (waveformBlob) {
      waveformUrl = await uploadToFirebaseStorage(waveformBlob, waveformFileName, 'waveforms');
    }
    
    let audioUrl = null;
    let playPageUrl = null;
    
    // Upload original audio file if provided
    if (audioFile) {
      const audioFileName = `${userId}_${timestamp}_${baseName}_audio.${audioFile.name.split('.').pop()}`;
      audioUrl = await uploadToFirebaseStorage(audioFile, audioFileName, 'audio');
    }
    
    // Create a temporary record to get the ID for the play page URL
    // We'll update this after we get the database ID
    playPageUrl = `${window.location.origin}${window.location.pathname.replace(/\/[^\/]*$/, '')}/play.php?id=TEMP_ID`;
    
    // Generate QR code pointing to the play page instead of the image
    const qrBlob = await generateQRCode(playPageUrl);
    const qrUrl = await uploadToFirebaseStorage(qrBlob, qrFileName, 'qr-codes');
    
    return {
      waveformUrl,
      qrUrl,
      audioUrl,
      playPageUrl,
      success: true
    };
  } catch (error) {
    console.error('Upload process failed:', error);
    return {
      error: error.message,
      success: false
    };
  }
}

// Delete file from Firebase Storage
export async function deleteFromFirebaseStorage(storagePath) {
  try {
    const storageRef = ref(storage, storagePath);
    await deleteObject(storageRef);
    return true;
  } catch (error) {
    console.error('❌ Failed to delete from Firebase Storage:', error);
    
    if (error.code === 'storage/object-not-found') {
      return true; // Consider this a success
    }
    
    return false;
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
