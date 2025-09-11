import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './hooks/useAuth';
import LandingPage from './pages/LandingPage';
import Login from './components/Login';
import AppPage from './pages/AppPage';
import MemoriesPage from './pages/MemoriesPage';
import OrdersPage from './pages/OrdersPage';
import PlayPage from './pages/PlayPage';
import AdminDashboard from './pages/AdminDashboard';
import PrivacyPolicy from './pages/PrivacyPolicy';
import TermsOfService from './pages/TermsOfService';
import RefundPolicy from './pages/RefundPolicy';
import Checkout from './components/Checkout';
import OrderSuccess from './pages/OrderSuccess';
import './App.css';

// Protected Route component
const ProtectedRoute = ({ children }) => {
  const { currentUser, loading } = useAuth();
  
  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Loading...</p>
      </div>
    );
  }
  
  return currentUser ? children : <Navigate to="/login" />;
};

// Public Route component (redirect to app if already logged in)
const PublicRoute = ({ children }) => {
  const { currentUser, loading } = useAuth();
  
  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Loading...</p>
      </div>
    );
  }
  
  return currentUser ? <Navigate to="/app" /> : children;
};

function App() {
  return (
    <Router>
      <div className="App">
        <Routes>
          {/* Landing page - accessible to everyone */}
          <Route path="/" element={<LandingPage />} />
          
          {/* Login page - only accessible if not logged in */}
          <Route 
            path="/login" 
            element={
              <PublicRoute>
                <Login />
              </PublicRoute>
            } 
          />
          
          {/* App page - only accessible if logged in */}
          <Route 
            path="/app" 
            element={
              <ProtectedRoute>
                <AppPage />
              </ProtectedRoute>
            } 
          />
          
          {/* Memories page - only accessible if logged in */}
          <Route 
            path="/memories" 
            element={
              <ProtectedRoute>
                <MemoriesPage />
              </ProtectedRoute>
            } 
          />
          
          {/* Orders page - only accessible if logged in */}
          <Route 
            path="/orders" 
            element={
              <ProtectedRoute>
                <OrdersPage />
              </ProtectedRoute>
            } 
          />
          
          {/* Play page - accessible to everyone */}
          <Route path="/play/:uid" element={<PlayPage />} />
          
          {/* Admin dashboard - only accessible if logged in and admin */}
          <Route 
            path="/admin" 
            element={
              <ProtectedRoute>
                <AdminDashboard />
              </ProtectedRoute>
            } 
          />
          
          {/* Checkout and order pages - only accessible if logged in */}
          <Route 
            path="/checkout" 
            element={
              <ProtectedRoute>
                <Checkout />
              </ProtectedRoute>
            } 
          />
          <Route 
            path="/order-success" 
            element={
              <ProtectedRoute>
                <OrderSuccess />
              </ProtectedRoute>
            } 
          />
          
          {/* Legal pages - accessible to everyone */}
          <Route path="/privacy-policy" element={<PrivacyPolicy />} />
          <Route path="/terms-of-service" element={<TermsOfService />} />
          <Route path="/refund-policy" element={<RefundPolicy />} />
          
          {/* Redirect any unknown routes to home */}
          <Route path="*" element={<Navigate to="/" />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;