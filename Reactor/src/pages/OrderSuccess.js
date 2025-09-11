import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import Header from '../components/Header';
import './OrderSuccess.css';

const OrderSuccess = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { order, memory } = location.state || {};

  if (!order || !memory) {
    navigate('/memories');
    return null;
  }

  return (
    <div className="order-success-page">
      <Header />
      <div className="order-success-content">
        <div className="container">
          <div className="success-header">
            <div className="success-icon">✅</div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. We'll start processing it right away.</p>
          </div>

          <div className="order-details">
            <div className="order-info">
              <h2>Order Details</h2>
              <div className="info-grid">
                <div className="info-item">
                  <strong>Order Number:</strong>
                  <span>{order.orderNumber || 'MW-2024-001'}</span>
                </div>
                <div className="info-item">
                  <strong>Memory:</strong>
                  <span>{memory.title}</span>
                </div>
                <div className="info-item">
                  <strong>Total Amount:</strong>
                  <span>${order.amount?.toFixed(2) || '38.86'}</span>
                </div>
                <div className="info-item">
                  <strong>Status:</strong>
                  <span className="status-badge paid">Paid</span>
                </div>
                <div className="info-item">
                  <strong>Order Date:</strong>
                  <span>{new Date(order.createdAt || Date.now()).toLocaleDateString()}</span>
                </div>
                <div className="info-item">
                  <strong>Estimated Delivery:</strong>
                  <span>{new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toLocaleDateString()}</span>
                </div>
              </div>
            </div>

            <div className="memory-preview">
              <h2>Your Memory</h2>
              <div className="preview-card">
                <img src={memory.image_url} alt={memory.title} />
                <div className="preview-info">
                  <h3>{memory.title}</h3>
                  <p>High-quality print on premium paper</p>
                </div>
              </div>
            </div>
          </div>

          <div className="next-steps">
            <h2>What's Next?</h2>
            <div className="steps-grid">
              <div className="step-item">
                <div className="step-number">1</div>
                <div className="step-content">
                  <h3>Processing</h3>
                  <p>We'll prepare your memory for printing within 1-2 business days.</p>
                </div>
              </div>
              <div className="step-item">
                <div className="step-number">2</div>
                <div className="step-content">
                  <h3>Printing</h3>
                  <p>Your waveform will be printed on high-quality paper with care.</p>
                </div>
              </div>
              <div className="step-item">
                <div className="step-number">3</div>
                <div className="step-content">
                  <h3>Shipping</h3>
                  <p>We'll ship your order and send you tracking information.</p>
                </div>
              </div>
            </div>
          </div>

          <div className="action-buttons">
            <button 
              className="btn btn-secondary"
              onClick={() => navigate('/orders')}
            >
              View All Orders
            </button>
            <button 
              className="btn btn-primary"
              onClick={() => navigate('/memories')}
            >
              Create Another Memory
            </button>
          </div>

          <div className="support-info">
            <h3>Need Help?</h3>
            <p>
              If you have any questions about your order, please contact our support team.
            </p>
            <div className="contact-methods">
              <a href="mailto:support@memowindow.com" className="contact-link">
                📧 support@memowindow.com
              </a>
              <a href="tel:1-800-MEMOWINDOW" className="contact-link">
                📞 1-800-MEMOWINDOW
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OrderSuccess;