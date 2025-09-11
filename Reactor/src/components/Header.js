import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import './Header.css';

const Header = () => {
  const { currentUser, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    const result = await logout();
    if (result.success) {
      navigate('/');
    }
  };

  return (
    <header className="header">
      <nav className="nav">
        <Link to="/" className="logo">
          <img src="/logo192.png" alt="MemoWindow" style={{ height: '40px', width: 'auto' }} />
        </Link>
        
        {currentUser ? (
          <div className="user-info">
            <Link to="/memories" className="header-link">My Memories</Link>
            <Link to="/orders" className="header-link">My Orders</Link>
            <button onClick={handleLogout} className="header-link">Sign Out</button>
            <div className="user-profile">
              <img 
                className="user-avatar" 
                src={currentUser.photoURL || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%23667eea"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>'} 
                alt="User avatar" 
              />
              <span>{currentUser.displayName || currentUser.email}</span>
            </div>
          </div>
        ) : (
          <Link to="/login" className="cta-button">Get Started</Link>
        )}
      </nav>
    </header>
  );
};

export default Header;