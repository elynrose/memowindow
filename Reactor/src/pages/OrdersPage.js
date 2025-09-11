import React, { useState, useEffect, useCallback } from 'react';
import { useAuth } from '../hooks/useAuth';
import Header from '../components/Header';
import './OrdersPage.css';

const OrdersPage = () => {
  const { currentUser } = useAuth();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filterStatus, setFilterStatus] = useState('all');

  // Mock data for demo - in a real app, this would fetch from your API
  const mockOrders = [
    {
      id: 1,
      order_number: 'MW-2024-001',
      memory_title: "Mom's Laughter",
      memory_image: "https://via.placeholder.com/400x200/667eea/ffffff?text=Mom's+Laughter",
      status: 'paid',
      amount_paid: 29.99,
      created_at: new Date().toISOString(),
      estimated_delivery: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
      tracking_number: null,
      shipping_address: {
        name: 'John Doe',
        street: '123 Main St',
        city: 'New York',
        state: 'NY',
        zip: '10001',
        country: 'USA'
      }
    },
    {
      id: 2,
      order_number: 'MW-2024-002',
      memory_title: "Dad's Bedtime Story",
      memory_image: "https://via.placeholder.com/400x200/764ba2/ffffff?text=Dad's+Story",
      status: 'processing',
      amount_paid: 29.99,
      created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
      estimated_delivery: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toISOString(),
      tracking_number: null,
      shipping_address: {
        name: 'John Doe',
        street: '123 Main St',
        city: 'New York',
        state: 'NY',
        zip: '10001',
        country: 'USA'
      }
    },
    {
      id: 3,
      order_number: 'MW-2024-003',
      memory_title: "Baby's First Words",
      memory_image: "https://via.placeholder.com/400x200/10b981/ffffff?text=Baby's+Words",
      status: 'shipped',
      amount_paid: 29.99,
      created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
      estimated_delivery: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString(),
      tracking_number: '1Z999AA1234567890',
      shipping_address: {
        name: 'John Doe',
        street: '123 Main St',
        city: 'New York',
        state: 'NY',
        zip: '10001',
        country: 'USA'
      }
    },
    {
      id: 4,
      order_number: 'MW-2024-004',
      memory_title: "Grandma's Voice",
      memory_image: "https://via.placeholder.com/400x200/f59e0b/ffffff?text=Grandma's+Voice",
      status: 'delivered',
      amount_paid: 29.99,
      created_at: new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString(),
      estimated_delivery: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
      tracking_number: '1Z999AA1234567890',
      shipping_address: {
        name: 'John Doe',
        street: '123 Main St',
        city: 'New York',
        state: 'NY',
        zip: '10001',
        country: 'USA'
      }
    }
  ];

  const loadOrders = useCallback(async () => {
    if (!currentUser) return;
    
    setLoading(true);
    setError(null);
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch(`/api/orders?user_id=${currentUser.uid}`);
      // const data = await response.json();
      
      // For demo purposes, use mock data
      setTimeout(() => {
        setOrders(mockOrders);
        setLoading(false);
      }, 1000);
      
    } catch (error) {
      console.error('Error loading orders:', error);
      setError('Failed to load orders. Please try again.');
      setLoading(false);
    }
  }, [currentUser]);

  useEffect(() => {
    loadOrders();
  }, [loadOrders]);

  const cancelOrder = async (orderId) => {
    if (!window.confirm('Are you sure you want to cancel this order?')) return;
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/orders/cancel', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ order_id: orderId, user_id: currentUser.uid })
      // });
      
      // For demo purposes, just update local state
      setOrders(prev => prev.map(order => 
        order.id === orderId 
          ? { ...order, status: 'cancelled' }
          : order
      ));
      alert('Order cancelled successfully');
      
    } catch (error) {
      console.error('Error cancelling order:', error);
      alert('Failed to cancel order: ' + error.message);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'paid': return '#1d4ed8';
      case 'processing': return '#92400e';
      case 'shipped': return '#065f46';
      case 'delivered': return '#3730a3';
      case 'cancelled': return '#dc2626';
      default: return '#6b7280';
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case 'paid': return 'Paid';
      case 'processing': return 'Processing';
      case 'shipped': return 'Shipped';
      case 'delivered': return 'Delivered';
      case 'cancelled': return 'Cancelled';
      default: return status;
    }
  };

  const filteredOrders = orders.filter(order => 
    filterStatus === 'all' || order.status === filterStatus
  );

  if (loading) {
    return (
      <div className="orders-page">
        <Header />
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading your orders...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="orders-page">
        <Header />
        <div className="error-container">
          <h2>Error Loading Orders</h2>
          <p>{error}</p>
          <button className="btn btn-primary" onClick={loadOrders}>
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="orders-page">
      <Header />
      <div className="orders-content">
        <div className="container">
          {/* Page Header */}
          <div className="page-header">
            <h1 className="page-title">My Orders</h1>
            <p className="page-subtitle">Track your MemoryWave print orders</p>
          </div>

          {/* Filter Bar */}
          <div className="filter-bar">
            <div className="filter-options">
              <button 
                className={`filter-btn ${filterStatus === 'all' ? 'active' : ''}`}
                onClick={() => setFilterStatus('all')}
              >
                All Orders
              </button>
              <button 
                className={`filter-btn ${filterStatus === 'paid' ? 'active' : ''}`}
                onClick={() => setFilterStatus('paid')}
              >
                Paid
              </button>
              <button 
                className={`filter-btn ${filterStatus === 'processing' ? 'active' : ''}`}
                onClick={() => setFilterStatus('processing')}
              >
                Processing
              </button>
              <button 
                className={`filter-btn ${filterStatus === 'shipped' ? 'active' : ''}`}
                onClick={() => setFilterStatus('shipped')}
              >
                Shipped
              </button>
              <button 
                className={`filter-btn ${filterStatus === 'delivered' ? 'active' : ''}`}
                onClick={() => setFilterStatus('delivered')}
              >
                Delivered
              </button>
            </div>
          </div>

          {/* Orders List */}
          {filteredOrders.length === 0 ? (
            <div className="empty-state">
              <div className="empty-state-icon">📦</div>
              <h3>No orders found</h3>
              <p>
                {filterStatus === 'all' 
                  ? "You haven't placed any orders yet" 
                  : `No orders with status "${getStatusText(filterStatus)}"`
                }
              </p>
              {filterStatus === 'all' && (
                <a href="/memories" className="btn btn-primary">
                  Browse Memories
                </a>
              )}
            </div>
          ) : (
            <div className="orders-list">
              {filteredOrders.map(order => (
                <div key={order.id} className="order-card">
                  <div className="order-header">
                    <div className="order-title-section">
                      <h3 className="order-title">{order.memory_title}</h3>
                      <p className="order-number">Order #{order.order_number}</p>
                    </div>
                    <div className="order-status-section">
                      <span 
                        className="order-status"
                        style={{ 
                          backgroundColor: getStatusColor(order.status) + '20',
                          color: getStatusColor(order.status)
                        }}
                      >
                        {getStatusText(order.status)}
                      </span>
                      {order.status === 'paid' && (
                        <button 
                          onClick={() => cancelOrder(order.id)}
                          className="cancel-btn"
                        >
                          Cancel
                        </button>
                      )}
                    </div>
                  </div>

                  <div className="order-details">
                    <div className="order-image">
                      <img 
                        src={order.memory_image} 
                        alt={order.memory_title}
                      />
                    </div>
                    <div className="order-info">
                      <div className="order-info-item">
                        <strong>Order Date:</strong> {new Date(order.created_at).toLocaleDateString()}
                      </div>
                      <div className="order-info-item">
                        <strong>Amount Paid:</strong> ${order.amount_paid}
                      </div>
                      {order.estimated_delivery && (
                        <div className="order-info-item">
                          <strong>Estimated Delivery:</strong> {new Date(order.estimated_delivery).toLocaleDateString()}
                        </div>
                      )}
                      {order.tracking_number && (
                        <div className="order-info-item">
                          <strong>Tracking Number:</strong> 
                          <a 
                            href={`https://www.ups.com/track?tracknum=${order.tracking_number}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="tracking-link"
                          >
                            {order.tracking_number}
                          </a>
                        </div>
                      )}
                    </div>
                    <div className="order-price">
                      ${order.amount_paid}
                    </div>
                  </div>

                  <div className="order-meta">
                    <div className="shipping-address">
                      <strong>Shipping to:</strong> {order.shipping_address.name}, {order.shipping_address.city}, {order.shipping_address.state} {order.shipping_address.zip}
                    </div>
                    <div className="order-actions">
                      {order.tracking_number && (
                        <a 
                          href={`https://www.ups.com/track?tracknum=${order.tracking_number}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="track-btn"
                        >
                          Track Package
                        </a>
                      )}
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

export default OrdersPage;