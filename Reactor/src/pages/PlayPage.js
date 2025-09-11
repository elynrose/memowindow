import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import './PlayPage.css';

const PlayPage = () => {
  const { uid } = useParams();
  const navigate = useNavigate();
  const [memory, setMemory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [audioElement, setAudioElement] = useState(null);

  useEffect(() => {
    loadMemory();
  }, [uid]);

  const loadMemory = async () => {
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch(`/api/memories/play?uid=${uid}`);
      // const data = await response.json();
      
      // For demo purposes, use mock data
      setTimeout(() => {
        const mockMemory = {
          id: 1,
          title: "Mom's Laughter",
          image_url: "https://via.placeholder.com/800x400/667eea/ffffff?text=Mom's+Laughter+Waveform",
          audio_url: "https://www.soundjay.com/misc/sounds/bell-ringing-05.wav", // Demo audio
          created_at: new Date().toISOString(),
          user_name: "John Doe"
        };
        setMemory(mockMemory);
        setLoading(false);
      }, 1000);
      
    } catch (error) {
      console.error('Error loading memory:', error);
      setError('Memory not found or no longer available');
      setLoading(false);
    }
  };

  const togglePlay = () => {
    if (!memory) return;

    if (isPlaying) {
      if (audioElement) {
        audioElement.pause();
        audioElement.currentTime = 0;
      }
      setIsPlaying(false);
    } else {
      const audio = new Audio(memory.audio_url);
      audio.addEventListener('ended', () => setIsPlaying(false));
      audio.addEventListener('error', () => {
        setIsPlaying(false);
        alert('Unable to play audio. The file may not be available.');
      });
      
      audio.play().then(() => {
        setAudioElement(audio);
        setIsPlaying(true);
      }).catch(error => {
        console.error('Error playing audio:', error);
        alert('Unable to play audio. Please check your browser settings.');
      });
    }
  };

  const shareMemory = async () => {
    if (navigator.share) {
      try {
        await navigator.share({
          title: memory.title,
          text: `Listen to this beautiful memory: ${memory.title}`,
          url: window.location.href
        });
      } catch (error) {
        console.log('Error sharing:', error);
      }
    } else {
      // Fallback: copy to clipboard
      try {
        await navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard!');
      } catch (error) {
        console.error('Error copying to clipboard:', error);
      }
    }
  };

  if (loading) {
    return (
      <div className="play-page">
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading memory...</p>
        </div>
      </div>
    );
  }

  if (error || !memory) {
    return (
      <div className="play-page">
        <div className="error-container">
          <div className="error-icon">😔</div>
          <h2>Memory Not Found</h2>
          <p>{error || 'This memory may have been deleted or is no longer available.'}</p>
          <button 
            className="btn btn-primary"
            onClick={() => navigate('/')}
          >
            Go to MemoWindow
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="play-page">
      <div className="play-content">
        <div className="container">
          {/* Header */}
          <div className="play-header">
            <div className="logo">
              <img src="/logo192.png" alt="MemoWindow" />
            </div>
            <button 
              className="btn btn-secondary"
              onClick={() => navigate('/')}
            >
              Create Your Own
            </button>
          </div>

          {/* Memory Display */}
          <div className="memory-display">
            <div className="memory-image-container">
              <img 
                src={memory.image_url} 
                alt={memory.title}
                className="memory-image"
              />
              <div className="memory-overlay">
                <div className="memory-title">{memory.title}</div>
                <div className="memory-meta">
                  Created by {memory.user_name} • {new Date(memory.created_at).toLocaleDateString()}
                </div>
              </div>
            </div>

            {/* Audio Controls */}
            <div className="audio-controls">
              <button 
                className={`play-button ${isPlaying ? 'playing' : ''}`}
                onClick={togglePlay}
              >
                {isPlaying ? (
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                  </svg>
                ) : (
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                )}
              </button>
              <div className="audio-info">
                <p className="audio-text">
                  {isPlaying ? 'Playing...' : 'Click to play the original audio'}
                </p>
              </div>
            </div>

            {/* Share Section */}
            <div className="share-section">
              <h3>Share This Memory</h3>
              <p>Let others experience this beautiful waveform memory</p>
              <div className="share-buttons">
                <button 
                  className="btn btn-primary"
                  onClick={shareMemory}
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                  </svg>
                  Share Memory
                </button>
                <button 
                  className="btn btn-secondary"
                  onClick={() => window.print()}
                >
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                  </svg>
                  Print
                </button>
              </div>
            </div>

            {/* Call to Action */}
            <div className="cta-section">
              <h3>Create Your Own Memory</h3>
              <p>Transform your precious voice recordings into beautiful waveform art</p>
              <button 
                className="btn btn-primary btn-large"
                onClick={() => navigate('/login')}
              >
                Get Started Free
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Footer */}
      <footer className="play-footer">
        <div className="container">
          <p>&copy; 2024 MemoWindow. Transform voice into beautiful waveform art.</p>
        </div>
      </footer>
    </div>
  );
};

export default PlayPage;