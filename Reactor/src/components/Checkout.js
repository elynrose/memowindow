import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import apiService from '../services/api';
import './Checkout.css';

const Checkout = () => {
  const { currentUser } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [shippingInfo, setShippingInfo] = useState({
    name: '',
    email: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    country: 'USA'
  });
  const [paymentInfo, setPaymentInfo] = useState({
    cardNumber: '',
    expiryDate: '',
    cvv: '',
    nameOnCard: ''
  });
  const [orderSummary, setOrderSummary] = useState(null);

  // Get memory data from location state
  const memory = location.state?.memory;

  useEffect(() => {
    if (!memory) {
      navigate('/memories');
      return;
    }

    // Set default email from user
    if (currentUser?.email) {
      setShippingInfo(prev => ({ ...prev, email: currentUser.email }));
    }

    // Set order summary
    setOrderSummary({
      memory: memory,
      subtotal: 29.99,
      shipping: 5.99,
      tax: 2.88,
      total: 38.86
    });
  }, [memory, currentUser, navigate]);

  const handleShippingChange = (e) => {
    const { name, value } = e.target;
    setShippingInfo(prev => ({ ...prev, [name]: value }));
  };

  const handlePaymentChange = (e) => {
    const { name, value } = e.target;
    setPaymentInfo(prev => ({ ...prev, [name]: value }));
  };

  const validateForm = () => {
    const requiredFields = ['name', 'email', 'address', 'city', 'state', 'zip'];
    const missingFields = requiredFields.filter(field => !shippingInfo[field]);
    
    if (missingFields.length > 0) {
      setError(`Please fill in: ${missingFields.join(', ')}`);
      return false;
    }

    if (!paymentInfo.cardNumber || !paymentInfo.expiryDate || !paymentInfo.cvv || !paymentInfo.nameOnCard) {
      setError('Please fill in all payment information');
      return false;
    }

    return true;
  };

  const processPayment = async () => {
    if (!validateForm()) return;

    setLoading(true);
    setError(null);

    try {
      // In a real app, you would:
      // 1. Create payment intent with Stripe
      // 2. Process the payment
      // 3. Create the order
      
      // For demo purposes, simulate payment processing
      await new Promise(resolve => setTimeout(resolve, 2000));

      const orderData = {
        memoryId: memory.id,
        memoryTitle: memory.title,
        amount: orderSummary.total,
        shippingInfo,
        paymentInfo: {
          // In production, don't send actual card details
          last4: paymentInfo.cardNumber.slice(-4),
          brand: 'visa' // Would be determined by Stripe
        }
      };

      // Create order
      const order = await apiService.createOrder(orderData);

      // Redirect to success page
      navigate('/order-success', { 
        state: { 
          order: order.order,
          memory: memory 
        } 
      });

    } catch (error) {
      console.error('Payment processing error:', error);
      setError('Payment failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (!orderSummary) {
    return (
      <div className="checkout-page">
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading checkout...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="checkout-page">
      <div className="checkout-container">
        <div className="checkout-header">
          <h1>Checkout</h1>
          <p>Complete your order for "{memory.title}"</p>
        </div>

        <div className="checkout-content">
          <div className="checkout-form">
            {/* Shipping Information */}
            <div className="form-section">
              <h2>Shipping Information</h2>
              <div className="form-grid">
                <div className="form-group">
                  <label htmlFor="name">Full Name *</label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value={shippingInfo.name}
                    onChange={handleShippingChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="email">Email *</label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    value={shippingInfo.email}
                    onChange={handleShippingChange}
                    required
                  />
                </div>
                <div className="form-group full-width">
                  <label htmlFor="address">Address *</label>
                  <input
                    type="text"
                    id="address"
                    name="address"
                    value={shippingInfo.address}
                    onChange={handleShippingChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="city">City *</label>
                  <input
                    type="text"
                    id="city"
                    name="city"
                    value={shippingInfo.city}
                    onChange={handleShippingChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="state">State *</label>
                  <input
                    type="text"
                    id="state"
                    name="state"
                    value={shippingInfo.state}
                    onChange={handleShippingChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="zip">ZIP Code *</label>
                  <input
                    type="text"
                    id="zip"
                    name="zip"
                    value={shippingInfo.zip}
                    onChange={handleShippingChange}
                    required
                  />
                </div>
              </div>
            </div>

            {/* Payment Information */}
            <div className="form-section">
              <h2>Payment Information</h2>
              <div className="form-grid">
                <div className="form-group full-width">
                  <label htmlFor="nameOnCard">Name on Card *</label>
                  <input
                    type="text"
                    id="nameOnCard"
                    name="nameOnCard"
                    value={paymentInfo.nameOnCard}
                    onChange={handlePaymentChange}
                    required
                  />
                </div>
                <div className="form-group full-width">
                  <label htmlFor="cardNumber">Card Number *</label>
                  <input
                    type="text"
                    id="cardNumber"
                    name="cardNumber"
                    value={paymentInfo.cardNumber}
                    onChange={handlePaymentChange}
                    placeholder="1234 5678 9012 3456"
                    maxLength="19"
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="expiryDate">Expiry Date *</label>
                  <input
                    type="text"
                    id="expiryDate"
                    name="expiryDate"
                    value={paymentInfo.expiryDate}
                    onChange={handlePaymentChange}
                    placeholder="MM/YY"
                    maxLength="5"
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="cvv">CVV *</label>
                  <input
                    type="text"
                    id="cvv"
                    name="cvv"
                    value={paymentInfo.cvv}
                    onChange={handlePaymentChange}
                    placeholder="123"
                    maxLength="4"
                    required
                  />
                </div>
              </div>
            </div>

            {error && (
              <div className="error-message">
                {error}
              </div>
            )}

            <div className="checkout-actions">
              <button 
                className="btn btn-secondary"
                onClick={() => navigate('/memories')}
              >
                Back to Memories
              </button>
              <button 
                className="btn btn-primary"
                onClick={processPayment}
                disabled={loading}
              >
                {loading ? (
                  <>
                    <div className="loading-spinner" style={{ width: '20px', height: '20px' }}></div>
                    Processing Payment...
                  </>
                ) : (
                  `Pay $${orderSummary.total.toFixed(2)}`
                )}
              </button>
            </div>
          </div>

          {/* Order Summary */}
          <div className="order-summary">
            <h2>Order Summary</h2>
            <div className="memory-preview">
              <img src={memory.image_url} alt={memory.title} />
              <div className="memory-info">
                <h3>{memory.title}</h3>
                <p>High-quality print on premium paper</p>
              </div>
            </div>
            
            <div className="summary-details">
              <div className="summary-row">
                <span>Subtotal</span>
                <span>${orderSummary.subtotal.toFixed(2)}</span>
              </div>
              <div className="summary-row">
                <span>Shipping</span>
                <span>${orderSummary.shipping.toFixed(2)}</span>
              </div>
              <div className="summary-row">
                <span>Tax</span>
                <span>${orderSummary.tax.toFixed(2)}</span>
              </div>
              <div className="summary-row total">
                <span>Total</span>
                <span>${orderSummary.total.toFixed(2)}</span>
              </div>
            </div>

            <div className="shipping-info">
              <h3>Shipping Details</h3>
              <p>• Standard shipping: 3-5 business days</p>
              <p>• Express shipping available at checkout</p>
              <p>• Free shipping on orders over $50</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Checkout;