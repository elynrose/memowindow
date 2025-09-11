import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import Header from '../components/Header';
import './MemoriesPage.css';

const MemoriesPage = () => {
  const { currentUser } = useAuth();
  const navigate = useNavigate();
  const [memories, setMemories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [sortBy, setSortBy] = useState('newest');

  // Mock data for demo - in a real app, this would fetch from your API
  const mockMemories = [
    {
      id: 1,
      title: "Mom's Laughter",
      created_at: new Date().toISOString(),
      image_url: "https://via.placeholder.com/400x200/667eea/ffffff?text=Mom's+Laughter",
      qr_url: "https://via.placeholder.com/120x120/000000/ffffff?text=QR",
      play_url: "/play/1",
      audio_url: "https://example.com/audio1.mp3",
      order_status: null
    },
    {
      id: 2,
      title: "Dad's Bedtime Story",
      created_at: new Date(Date.now() - 86400000).toISOString(),
      image_url: "https://via.placeholder.com/400x200/764ba2/ffffff?text=Dad's+Story",
      qr_url: "https://via.placeholder.com/120x120/000000/ffffff?text=QR",
      play_url: "/play/2",
      audio_url: "https://example.com/audio2.mp3",
      order_status: "paid"
    },
    {
      id: 3,
      title: "Baby's First Words",
      created_at: new Date(Date.now() - 172800000).toISOString(),
      image_url: "https://via.placeholder.com/400x200/10b981/ffffff?text=Baby's+Words",
      qr_url: "https://via.placeholder.com/120x120/000000/ffffff?text=QR",
      play_url: "/play/3",
      audio_url: "https://example.com/audio3.mp3",
      order_status: "shipped"
    }
  ];

  const loadMemories = useCallback(async () => {
    if (!currentUser) return;
    
    setLoading(true);
    setError(null);
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch(`/api/memories?user_id=${currentUser.uid}`);
      // const data = await response.json();
      
      // For demo purposes, use mock data
      setTimeout(() => {
        setMemories(mockMemories);
        setLoading(false);
      }, 1000);
      
    } catch (error) {
      console.error('Error loading memories:', error);
      setError('Failed to load memories. Please try again.');
      setLoading(false);
    }
  }, [currentUser]);

  useEffect(() => {
    loadMemories();
  }, [loadMemories]);

  const deleteMemory = async (memoryId) => {
    if (!window.confirm('Are you sure you want to delete this memory?')) return;
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/memories', {
      //   method: 'DELETE',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ memory_id: memoryId, user_id: currentUser.uid })
      // });
      
      // For demo purposes, just remove from local state
      setMemories(prev => prev.filter(m => m.id !== memoryId));
      alert('Memory deleted successfully');
      
    } catch (error) {
      console.error('Error deleting memory:', error);
      alert('Failed to delete memory: ' + error.message);
    }
  };

  const createOrder = async (memoryId) => {
    try {
      const memory = memories.find(m => m.id === memoryId);
      if (memory) {
        navigate('/checkout', { state: { memory } });
      } else {
        alert('Memory not found');
      }
    } catch (error) {
      console.error('Error creating order:', error);
      alert('Failed to create order: ' + error.message);
    }
  };

  const filteredAndSortedMemories = memories
    .filter(memory => 
      memory.title.toLowerCase().includes(searchTerm.toLowerCase())
    )
    .sort((a, b) => {
      switch (sortBy) {
        case 'newest':
          return new Date(b.created_at) - new Date(a.created_at);
        case 'oldest':
          return new Date(a.created_at) - new Date(b.created_at);
        case 'title':
          return a.title.localeCompare(b.title);
        default:
          return 0;
      }
    });

  if (loading) {
    return (
      <div className="memories-page">
        <Header />
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading your memories...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="memories-page">
        <Header />
        <div className="error-container">
          <h2>Error Loading Memories</h2>
          <p>{error}</p>
          <button className="btn btn-primary" onClick={loadMemories}>
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="memories-page">
      <Header />
      <div className="memories-content">
        <div className="container">
          {/* Page Header */}
          <div className="page-header">
            <h1 className="page-title">My Memories</h1>
            <p className="page-subtitle">Your beautiful waveform memories, ready to share and print</p>
            <Link to="/app" className="create-memory-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
              </svg>
              Create New Memory
            </Link>
          </div>

          {/* Search and Filter */}
          <div className="search-filter-bar">
            <div className="search-box">
              <input
                type="text"
                placeholder="Search memories..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="form-input"
              />
            </div>
            <div className="sort-dropdown">
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="form-input"
              >
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="title">By Title</option>
              </select>
            </div>
          </div>

          {/* Memories Grid */}
          {filteredAndSortedMemories.length === 0 ? (
            <div className="empty-state">
              <div className="empty-state-icon">💕</div>
              <h3>No memories found</h3>
              <p>
                {searchTerm 
                  ? `No memories match "${searchTerm}"` 
                  : "Create your first memory to get started"
                }
              </p>
              {!searchTerm && (
                <Link to="/app" className="create-memory-btn">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                  </svg>
                  Create Your First Memory
                </Link>
              )}
            </div>
          ) : (
            <div className="memories-grid">
              {filteredAndSortedMemories.map(memory => (
                <div key={memory.id} className="memory-card">
                  <div className="memory-image-container">
                    <img 
                      src={memory.image_url} 
                      alt={memory.title}
                      className="memory-image"
                    />
                    {memory.order_status && (
                      <div className={`order-badge ${memory.order_status}`}>
                        {memory.order_status === 'paid' && 'Ordered'}
                        {memory.order_status === 'processing' && 'Processing'}
                        {memory.order_status === 'shipped' && 'Shipped'}
                        {memory.order_status === 'delivered' && 'Delivered'}
                      </div>
                    )}
                  </div>
                  <div className="memory-content">
                    <h3 className="memory-title">{memory.title}</h3>
                    <p className="memory-date">
                      {new Date(memory.created_at).toLocaleDateString()}
                    </p>
                    <div className="memory-actions">
                      <a 
                        href={memory.image_url} 
                        target="_blank" 
                        rel="noopener noreferrer"
                        className="memory-action"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        View
                      </a>
                      <a 
                        href={memory.qr_url} 
                        target="_blank" 
                        rel="noopener noreferrer"
                        className="memory-action"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm13-2h-2v2h-2v2h2v2h2v-2h2v-2h-2v-2z"/>
                        </svg>
                        QR Code
                      </a>
                      <Link 
                        to={memory.play_url}
                        className="memory-action"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M8 5v14l11-7z"/>
                        </svg>
                        Play
                      </Link>
                      {!memory.order_status && (
                        <button 
                          onClick={() => createOrder(memory.id)}
                          className="memory-action order"
                        >
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                          </svg>
                          Order Print
                        </button>
                      )}
                      <button 
                        onClick={() => deleteMemory(memory.id)}
                        className="memory-action delete"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                        Delete
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MemoriesPage;