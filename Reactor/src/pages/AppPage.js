import React, { useState } from 'react';
import { useAuth } from '../hooks/useAuth';
import Header from '../components/Header';
import MemoryCreator from '../components/MemoryCreator';
import WaveformList from '../components/WaveformList';
import './AppPage.css';

const AppPage = () => {
  const { currentUser, loading } = useAuth();
  const [refreshTrigger, setRefreshTrigger] = useState(0);

  const handleMemoryCreated = () => {
    // Trigger a refresh of the waveform list
    setRefreshTrigger(prev => prev + 1);
  };

  if (loading) {
    return (
      <div className="app-page">
        <Header />
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading...</p>
        </div>
      </div>
    );
  }

  if (!currentUser) {
    return (
      <div className="app-page">
        <Header />
        <div className="auth-required">
          <h2>Authentication Required</h2>
          <p>Please sign in to access the app.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="app-page">
      <Header />
      <div className="app-content">
        <div className="container">
          <MemoryCreator onMemoryCreated={handleMemoryCreated} />
          <WaveformList refreshTrigger={refreshTrigger} />
        </div>
      </div>
    </div>
  );
};

export default AppPage;