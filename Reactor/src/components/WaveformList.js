import React, { useState, useEffect, useCallback } from 'react';
import { useAuth } from '../hooks/useAuth';
import './WaveformList.css';

const WaveformList = ({ refreshTrigger }) => {
  const { currentUser } = useAuth();
  const [waveforms, setWaveforms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Mock data for demo - in a real app, this would fetch from your API
  const mockWaveforms = [
    {
      id: 1,
      title: "Mom's Laughter",
      created_at: new Date().toISOString(),
      image_url: "https://via.placeholder.com/400x200/667eea/ffffff?text=Waveform+1",
      qr_url: "https://via.placeholder.com/120x120/000000/ffffff?text=QR",
      play_url: "#"
    },
    {
      id: 2,
      title: "Dad's Bedtime Story",
      created_at: new Date(Date.now() - 86400000).toISOString(),
      image_url: "https://via.placeholder.com/400x200/764ba2/ffffff?text=Waveform+2",
      qr_url: "https://via.placeholder.com/120x120/000000/ffffff?text=QR",
      play_url: "#"
    }
  ];

  const loadWaveforms = useCallback(async () => {
    if (!currentUser) return;
    
    setLoading(true);
    setError(null);
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch(`/api/waveforms?user_id=${currentUser.uid}`);
      // const data = await response.json();
      
      // For demo purposes, use mock data
      setTimeout(() => {
        setWaveforms(mockWaveforms);
        setLoading(false);
      }, 1000);
      
    } catch (error) {
      console.error('Error loading waveforms:', error);
      setError('Failed to load memories. Please try again.');
      setLoading(false);
    }
  }, [currentUser]);

  useEffect(() => {
    loadWaveforms();
  }, [currentUser, refreshTrigger, loadWaveforms]);

  const deleteWaveform = async (waveformId) => {
    if (!window.confirm('Are you sure you want to delete this memory?')) return;
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/waveforms', {
      //   method: 'DELETE',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ memory_id: waveformId, user_id: currentUser.uid })
      // });
      
      // For demo purposes, just remove from local state
      setWaveforms(prev => prev.filter(w => w.id !== waveformId));
      alert('Memory deleted successfully');
      
    } catch (error) {
      console.error('Error deleting waveform:', error);
      alert('Failed to delete memory: ' + error.message);
    }
  };

  if (loading) {
    return (
      <div className="waveform-list">
        <h2>Your MemoWindows</h2>
        <div className="loading">
          <div className="loading-spinner"></div>
          Loading your memories...
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="waveform-list">
        <h2>Your MemoWindows</h2>
        <div className="error-message">
          <p>{error}</p>
          <button className="btn btn-primary" onClick={loadWaveforms}>
            Try Again
          </button>
        </div>
      </div>
    );
  }

  if (waveforms.length === 0) {
    return (
      <div className="waveform-list">
        <h2>Your MemoWindows</h2>
        <div className="empty-state">
          <p>No memories found. Create your first memory above!</p>
        </div>
      </div>
    );
  }

  return (
    <div className="waveform-list">
      <h2>Your MemoWindows</h2>
      <div className="waveforms-grid">
        {waveforms.map(waveform => (
          <div key={waveform.id} className="waveform-item">
            <div className="waveform-preview">
              <img 
                src={waveform.image_url} 
                alt={waveform.title}
                className="waveform-image"
              />
            </div>
            <div className="waveform-info">
              <h3 className="waveform-title">{waveform.title}</h3>
              <p className="waveform-date">
                {new Date(waveform.created_at).toLocaleDateString()}
              </p>
            </div>
            <div className="waveform-actions">
              <a 
                href={waveform.image_url} 
                target="_blank" 
                rel="noopener noreferrer"
                className="action-link"
              >
                View
              </a>
              <a 
                href={waveform.qr_url} 
                target="_blank" 
                rel="noopener noreferrer"
                className="action-link"
              >
                QR
              </a>
              <button 
                onClick={() => deleteWaveform(waveform.id)}
                className="action-link delete"
              >
                Delete
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default WaveformList;