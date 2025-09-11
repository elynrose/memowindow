import React, { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import './VoiceClone.css';

const VoiceClone = ({ memoryId, audioUrl, memoryTitle, onCloneCreated }) => {
  const { currentUser } = useAuth();
  const [isLoading, setIsLoading] = useState(false);
  const [clonedVoices, setClonedVoices] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [selectedVoice, setSelectedVoice] = useState(null);
  const [isGenerating, setIsGenerating] = useState(false);

  // Mock data for demo - in a real app, this would fetch from your API
  const mockClonedVoices = [
    {
      id: 1,
      name: `${memoryTitle} - Clone 1`,
      created_at: new Date().toISOString(),
      status: 'ready',
      sample_audio: 'https://example.com/sample1.wav'
    }
  ];

  useEffect(() => {
    loadClonedVoices();
  }, [memoryId]);

  const loadClonedVoices = async () => {
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch(`/api/voice-clones?memory_id=${memoryId}&user_id=${currentUser.uid}`);
      // const data = await response.json();
      
      // For demo purposes, use mock data
      setClonedVoices(mockClonedVoices);
    } catch (error) {
      console.error('Error loading cloned voices:', error);
    }
  };

  const createVoiceClone = async () => {
    if (!audioUrl) {
      alert('No audio available for voice cloning');
      return;
    }

    setIsLoading(true);
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/voice-clones', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({
      //     memory_id: memoryId,
      //     audio_url: audioUrl,
      //     user_id: currentUser.uid
      //   })
      // });
      
      // For demo purposes, simulate API call
      setTimeout(() => {
        const newClone = {
          id: Date.now(),
          name: `${memoryTitle} - Clone ${clonedVoices.length + 1}`,
          created_at: new Date().toISOString(),
          status: 'processing',
          sample_audio: null
        };
        
        setClonedVoices(prev => [...prev, newClone]);
        setIsLoading(false);
        
        // Simulate processing completion
        setTimeout(() => {
          setClonedVoices(prev => prev.map(clone => 
            clone.id === newClone.id 
              ? { ...clone, status: 'ready', sample_audio: 'https://example.com/sample.wav' }
              : clone
          ));
        }, 5000);
        
        if (onCloneCreated) {
          onCloneCreated(newClone);
        }
      }, 2000);
      
    } catch (error) {
      console.error('Error creating voice clone:', error);
      alert('Failed to create voice clone: ' + error.message);
      setIsLoading(false);
    }
  };

  const generateMessage = async () => {
    if (!selectedVoice || !newMessage.trim()) {
      alert('Please select a voice and enter a message');
      return;
    }

    setIsGenerating(true);
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/voice-clones/generate', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({
      //     voice_id: selectedVoice.id,
      //     message: newMessage,
      //     user_id: currentUser.uid
      //   })
      // });
      
      // For demo purposes, simulate generation
      setTimeout(() => {
        alert('Message generated successfully! (Demo mode)');
        setNewMessage('');
        setIsGenerating(false);
      }, 3000);
      
    } catch (error) {
      console.error('Error generating message:', error);
      alert('Failed to generate message: ' + error.message);
      setIsGenerating(false);
    }
  };

  const deleteVoiceClone = async (cloneId) => {
    if (!window.confirm('Are you sure you want to delete this voice clone?')) return;
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/voice-clones', {
      //   method: 'DELETE',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ clone_id: cloneId, user_id: currentUser.uid })
      // });
      
      // For demo purposes, just remove from local state
      setClonedVoices(prev => prev.filter(clone => clone.id !== cloneId));
      alert('Voice clone deleted successfully');
      
    } catch (error) {
      console.error('Error deleting voice clone:', error);
      alert('Failed to delete voice clone: ' + error.message);
    }
  };

  return (
    <div className="voice-clone-container">
      <div className="voice-clone-header">
        <h3>🎤 Voice Cloning</h3>
        <p>Create AI-powered voice clones from your memories</p>
      </div>

      {/* Create New Voice Clone */}
      <div className="voice-clone-section">
        <h4>Create Voice Clone</h4>
        <p>Generate a voice clone from this memory's audio</p>
        <button 
          className="btn btn-primary"
          onClick={createVoiceClone}
          disabled={isLoading || !audioUrl}
        >
          {isLoading ? (
            <>
              <div className="loading-spinner" style={{ width: '16px', height: '16px' }}></div>
              Creating Clone...
            </>
          ) : (
            <>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 1c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2s2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 19.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
              </svg>
              Create Voice Clone
            </>
          )}
        </button>
      </div>

      {/* Existing Voice Clones */}
      {clonedVoices.length > 0 && (
        <div className="voice-clone-section">
          <h4>Your Voice Clones</h4>
          <div className="voice-clones-list">
            {clonedVoices.map(clone => (
              <div key={clone.id} className="voice-clone-item">
                <div className="clone-info">
                  <h5>{clone.name}</h5>
                  <p className="clone-status">
                    Status: <span className={`status-badge ${clone.status}`}>
                      {clone.status === 'processing' ? 'Processing...' : 'Ready'}
                    </span>
                  </p>
                  <p className="clone-date">
                    Created: {new Date(clone.created_at).toLocaleDateString()}
                  </p>
                </div>
                <div className="clone-actions">
                  {clone.status === 'ready' && (
                    <button 
                      className="btn btn-secondary btn-sm"
                      onClick={() => setSelectedVoice(clone)}
                    >
                      Select
                    </button>
                  )}
                  <button 
                    className="btn btn-danger btn-sm"
                    onClick={() => deleteVoiceClone(clone.id)}
                  >
                    Delete
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Generate Message */}
      {selectedVoice && (
        <div className="voice-clone-section">
          <h4>Generate New Message</h4>
          <p>Create new audio using the selected voice clone</p>
          <div className="message-generator">
            <div className="selected-voice">
              <strong>Selected Voice:</strong> {selectedVoice.name}
            </div>
            <textarea
              value={newMessage}
              onChange={(e) => setNewMessage(e.target.value)}
              placeholder="Enter the message you want the voice to say..."
              className="message-input"
              rows="4"
            />
            <button 
              className="btn btn-primary"
              onClick={generateMessage}
              disabled={isGenerating || !newMessage.trim()}
            >
              {isGenerating ? (
                <>
                  <div className="loading-spinner" style={{ width: '16px', height: '16px' }}></div>
                  Generating...
                </>
              ) : (
                <>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                  Generate Audio
                </>
              )}
            </button>
          </div>
        </div>
      )}

      {/* Usage Limits */}
      <div className="voice-clone-section usage-info">
        <h4>Usage Limits</h4>
        <div className="usage-stats">
          <div className="usage-item">
            <span className="usage-label">Voice Clones Created:</span>
            <span className="usage-value">{clonedVoices.length}/10</span>
          </div>
          <div className="usage-item">
            <span className="usage-label">Messages Generated:</span>
            <span className="usage-value">0/50</span>
          </div>
        </div>
        <p className="usage-note">
          Upgrade to Premium for unlimited voice clones and messages
        </p>
      </div>
    </div>
  );
};

export default VoiceClone;