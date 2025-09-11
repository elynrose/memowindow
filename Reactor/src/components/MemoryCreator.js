import React, { useState, useRef, useCallback } from 'react';
import { useAuth } from '../hooks/useAuth';
import { AudioProcessor } from '../utils/audioProcessor';
import { StorageService } from '../services/storage';
import VoiceClone from './VoiceClone';
import './MemoryCreator.css';

const MemoryCreator = ({ onMemoryCreated }) => {
  const { currentUser } = useAuth();
  const [title, setTitle] = useState('');
  const [selectedFiles, setSelectedFiles] = useState([]);
  const [isRecording, setIsRecording] = useState(false);
  const [currentPeaks, setCurrentPeaks] = useState(null);
  const [isCreating, setIsCreating] = useState(false);
  const [previewCanvas, setPreviewCanvas] = useState(null);
  const [createdMemory, setCreatedMemory] = useState(null);
  
  const fileInputRef = useRef(null);
  const mediaRecorderRef = useRef(null);
  const audioChunksRef = useRef([]);

  const validateForm = useCallback(() => {
    return title.trim().length > 0 && selectedFiles.length > 0;
  }, [title, selectedFiles]);

  const handleFileSelect = useCallback(async (files) => {
    const audioFiles = Array.from(files).filter(file => file.type.startsWith('audio/'));
    if (audioFiles.length > 0) {
      setSelectedFiles(audioFiles);
      
      // Process first file for preview
      const result = await AudioProcessor.processFileForPreview(audioFiles[0]);
      if (result.success) {
        setCurrentPeaks(result.peaks);
        if (previewCanvas) {
          AudioProcessor.drawPreview(previewCanvas, result.peaks, title || 'Untitled Memory');
        }
      }
    }
  }, [title, previewCanvas]);

  const handleFileInputChange = (event) => {
    handleFileSelect(event.target.files);
  };

  const handleDrop = (event) => {
    event.preventDefault();
    handleFileSelect(event.dataTransfer.files);
  };

  const handleDragOver = (event) => {
    event.preventDefault();
  };

  const handleDragLeave = (event) => {
    event.preventDefault();
  };

  const toggleRecording = async () => {
    if (isRecording) {
      stopRecording();
    } else {
      startRecording();
    }
  };

  const startRecording = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorderRef.current = new MediaRecorder(stream);
      audioChunksRef.current = [];
      
      mediaRecorderRef.current.ondataavailable = (event) => {
        audioChunksRef.current.push(event.data);
      };
      
      mediaRecorderRef.current.onstop = () => {
        const audioBlob = new Blob(audioChunksRef.current, { type: 'audio/wav' });
        const audioFile = new File([audioBlob], 'recording.wav', { type: 'audio/wav' });
        setSelectedFiles([audioFile]);
        handleFileSelect([audioFile]);
      };
      
      mediaRecorderRef.current.start();
      setIsRecording(true);
    } catch (error) {
      console.error('Error starting recording:', error);
      alert('Could not access microphone. Please check permissions.');
    }
  };

  const stopRecording = () => {
    if (mediaRecorderRef.current && isRecording) {
      mediaRecorderRef.current.stop();
      mediaRecorderRef.current.stream.getTracks().forEach(track => track.stop());
      setIsRecording(false);
    }
  };

  const createMemory = async () => {
    if (!validateForm()) {
      alert('Please enter a title and select audio files.');
      return;
    }
    
    setIsCreating(true);
    
    try {
      // Check subscription limits (simplified for React version)
      // In a real app, you'd call your backend API here
      
      // Generate unique ID
      const uniqueId = 'mw_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      
      // For demo purposes, we'll use a placeholder base URL
      const baseUrl = window.location.origin;
      const playPageUrl = `${baseUrl}/play?uid=${uniqueId}`;
      const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=1200x1200&margin=1&data=${encodeURIComponent(playPageUrl)}`;
      
      // Process each audio file
      for (let i = 0; i < selectedFiles.length; i++) {
        const file = selectedFiles[i];
        const baseName = file.name.replace(/\.[^/.]+$/, '');
        
        // Create waveform from audio with QR code
        const waveformBlob = await AudioProcessor.createWaveformFromAudio(file, title, qrApiUrl);
        
        // Upload files to Firebase Storage
        const uploadResult = await StorageService.uploadWaveformFiles(
          waveformBlob,
          `${baseName}_composition.png`,
          currentUser.uid,
          file
        );
        
        if (!uploadResult.success) {
          throw new Error(uploadResult.error || 'Upload failed');
        }
        
        // In a real app, you'd save to your database here
        const memoryData = {
          id: Date.now(),
          title,
          userId: currentUser.uid,
          imageUrl: uploadResult.waveformUrl,
          audioUrl: uploadResult.audioUrl,
          qrUrl: qrApiUrl,
          playUrl: playPageUrl,
          uniqueId
        };
        
        console.log('Memory created:', memoryData);
        setCreatedMemory(memoryData);
      }
      
      // Show success message
      alert('Memory created successfully!');
      
      // Reset form
      setTitle('');
      setSelectedFiles([]);
      setCurrentPeaks(null);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
      
      // Notify parent component
      if (onMemoryCreated) {
        onMemoryCreated();
      }
      
    } catch (error) {
      console.error('Error creating memory:', error);
      alert('Failed to create memory: ' + error.message);
    } finally {
      setIsCreating(false);
    }
  };

  // Update preview when title changes
  React.useEffect(() => {
    if (currentPeaks && previewCanvas) {
      AudioProcessor.drawPreview(previewCanvas, currentPeaks, title || 'Untitled Memory');
    }
  }, [title, currentPeaks, previewCanvas]);

  return (
    <div className="memory-creator">
      {/* Memory Title Card */}
      <div className="card">
        <h2>Memory Title</h2>
        <div className="form-group">
          <input 
            type="text" 
            className="form-input" 
            placeholder="Memory title (e.g., 'Mom's Laughter', 'Dad's Bedtime Story')" 
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            required 
          />
        </div>
      </div>

      {/* Upload Audio Files Card */}
      <div className="card">
        <h2>Upload Audio Files</h2>
        <div 
          className={`file-upload-area ${selectedFiles.length > 0 ? 'has-files' : ''}`}
          onClick={() => fileInputRef.current?.click()}
          onDrop={handleDrop}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
        >
          {selectedFiles.length > 0 ? (
            <>
              <div style={{ marginBottom: '1rem' }}>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ color: '#10b981', margin: '0 auto' }}>
                  <path d="M9 12l2 2 4-4"></path>
                  <circle cx="12" cy="12" r="10"></circle>
                </svg>
              </div>
              <p style={{ fontSize: '1.125rem', fontWeight: '600', color: '#10b981', marginBottom: '0.5rem' }}>
                {selectedFiles.length} file(s) selected
              </p>
              <p style={{ color: '#6b7280', fontSize: '0.875rem' }}>Click to change files</p>
            </>
          ) : (
            <>
              <div style={{ marginBottom: '1rem' }}>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ color: '#6b7280', margin: '0 auto' }}>
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7,10 12,15 17,10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </div>
              <p style={{ fontSize: '1.125rem', fontWeight: '600', color: '#374151', marginBottom: '0.5rem' }}>
                Drop audio files here or click to browse
              </p>
              <p style={{ color: '#6b7280', fontSize: '0.875rem' }}>
                Supports MP3, WAV, M4A, and other audio formats
              </p>
            </>
          )}
          <input 
            ref={fileInputRef}
            type="file" 
            multiple 
            accept="audio/*" 
            style={{ display: 'none' }}
            onChange={handleFileInputChange}
          />
        </div>
        
        <div style={{ textAlign: 'center', marginTop: '1.5rem' }}>
          <p style={{ color: '#6b7280', marginBottom: '1rem' }}>Or record your voice</p>
          <button 
            type="button" 
            className={`record-button ${isRecording ? 'recording' : ''}`}
            onClick={toggleRecording}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 1c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2s2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 19.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
            </svg>
          </button>
        </div>
        
        <div style={{ marginTop: '1.5rem', textAlign: 'center' }}>
          <button 
            className="btn btn-primary btn-full" 
            disabled={!validateForm() || isCreating}
            onClick={createMemory}
          >
            {isCreating ? (
              <>
                <div className="loading-spinner" style={{ width: '20px', height: '20px', margin: '0 auto' }}></div>
                Creating...
              </>
            ) : (
              <>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Create Memory
              </>
            )}
          </button>
        </div>
      </div>

      {/* Memory Preview */}
      {currentPeaks && (
        <div className="card">
          <h2>Memory Preview</h2>
          <div style={{ textAlign: 'center' }}>
            <canvas 
              ref={setPreviewCanvas}
              width="400" 
              height="200" 
              style={{ border: '1px solid #e5e7eb', borderRadius: '8px', maxWidth: '100%' }}
            />
            <p style={{ marginTop: '1rem', color: '#6b7280' }}>
              Preview of your waveform memory
            </p>
          </div>
        </div>
      )}

      {/* Voice Cloning Section */}
      {createdMemory && (
        <VoiceClone 
          memoryId={createdMemory.id}
          audioUrl={createdMemory.audioUrl}
          memoryTitle={createdMemory.title}
          onCloneCreated={() => {
            console.log('Voice clone created successfully');
          }}
        />
      )}
    </div>
  );
};

export default MemoryCreator;