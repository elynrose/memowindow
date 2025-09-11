import React, { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import Header from '../components/Header';
import './AdminDashboard.css';

const AdminDashboard = () => {
  const { currentUser } = useAuth();
  const [activeTab, setActiveTab] = useState('overview');
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({});
  const [users, setUsers] = useState([]);
  const [orders, setOrders] = useState([]);
  const [memories, setMemories] = useState([]);

  // Mock data for demo - in a real app, this would fetch from your API
  const mockStats = {
    total_users: 1250,
    total_memories: 3420,
    total_orders: 890,
    total_revenue: 26750.00,
    active_subscriptions: 450,
    pending_orders: 23
  };

  const mockUsers = [
    {
      id: 1,
      email: 'user1@example.com',
      display_name: 'John Doe',
      created_at: new Date().toISOString(),
      subscription_plan: 'Premium',
      total_memories: 15,
      total_orders: 3
    },
    {
      id: 2,
      email: 'user2@example.com',
      display_name: 'Jane Smith',
      created_at: new Date(Date.now() - 86400000).toISOString(),
      subscription_plan: 'Standard',
      total_memories: 8,
      total_orders: 1
    }
  ];

  const mockOrders = [
    {
      id: 1,
      order_number: 'MW-2024-001',
      user_email: 'user1@example.com',
      memory_title: "Mom's Laughter",
      status: 'paid',
      amount: 29.99,
      created_at: new Date().toISOString()
    },
    {
      id: 2,
      order_number: 'MW-2024-002',
      user_email: 'user2@example.com',
      memory_title: "Dad's Story",
      status: 'processing',
      amount: 29.99,
      created_at: new Date(Date.now() - 86400000).toISOString()
    }
  ];

  const mockMemories = [
    {
      id: 1,
      title: "Mom's Laughter",
      user_email: 'user1@example.com',
      created_at: new Date().toISOString(),
      has_audio: true,
      has_order: true
    },
    {
      id: 2,
      title: "Dad's Story",
      user_email: 'user2@example.com',
      created_at: new Date(Date.now() - 86400000).toISOString(),
      has_audio: true,
      has_order: false
    }
  ];

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    setLoading(true);
    
    try {
      // In a real app, you'd make API calls here
      // const [statsRes, usersRes, ordersRes, memoriesRes] = await Promise.all([
      //   fetch('/api/admin/stats'),
      //   fetch('/api/admin/users'),
      //   fetch('/api/admin/orders'),
      //   fetch('/api/admin/memories')
      // ]);
      
      // For demo purposes, use mock data
      setTimeout(() => {
        setStats(mockStats);
        setUsers(mockUsers);
        setOrders(mockOrders);
        setMemories(mockMemories);
        setLoading(false);
      }, 1000);
      
    } catch (error) {
      console.error('Error loading dashboard data:', error);
      setLoading(false);
    }
  };

  const updateOrderStatus = async (orderId, newStatus) => {
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/admin/orders/status', {
      //   method: 'PUT',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ order_id: orderId, status: newStatus })
      // });
      
      // For demo purposes, update local state
      setOrders(prev => prev.map(order => 
        order.id === orderId ? { ...order, status: newStatus } : order
      ));
      alert('Order status updated successfully');
      
    } catch (error) {
      console.error('Error updating order status:', error);
      alert('Failed to update order status');
    }
  };

  const deleteUser = async (userId) => {
    if (!window.confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/admin/users', {
      //   method: 'DELETE',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ user_id: userId })
      // });
      
      // For demo purposes, remove from local state
      setUsers(prev => prev.filter(user => user.id !== userId));
      alert('User deleted successfully');
      
    } catch (error) {
      console.error('Error deleting user:', error);
      alert('Failed to delete user');
    }
  };

  const deleteMemory = async (memoryId) => {
    if (!window.confirm('Are you sure you want to delete this memory?')) return;
    
    try {
      // In a real app, you'd make an API call here
      // const response = await fetch('/api/admin/memories', {
      //   method: 'DELETE',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify({ memory_id: memoryId })
      // });
      
      // For demo purposes, remove from local state
      setMemories(prev => prev.filter(memory => memory.id !== memoryId));
      alert('Memory deleted successfully');
      
    } catch (error) {
      console.error('Error deleting memory:', error);
      alert('Failed to delete memory');
    }
  };

  if (loading) {
    return (
      <div className="admin-dashboard">
        <Header />
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading admin dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="admin-dashboard">
      <Header />
      <div className="admin-content">
        <div className="container">
          <div className="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Manage users, orders, and system settings</p>
          </div>

          {/* Navigation Tabs */}
          <div className="admin-tabs">
            <button 
              className={`tab-btn ${activeTab === 'overview' ? 'active' : ''}`}
              onClick={() => setActiveTab('overview')}
            >
              Overview
            </button>
            <button 
              className={`tab-btn ${activeTab === 'users' ? 'active' : ''}`}
              onClick={() => setActiveTab('users')}
            >
              Users
            </button>
            <button 
              className={`tab-btn ${activeTab === 'orders' ? 'active' : ''}`}
              onClick={() => setActiveTab('orders')}
            >
              Orders
            </button>
            <button 
              className={`tab-btn ${activeTab === 'memories' ? 'active' : ''}`}
              onClick={() => setActiveTab('memories')}
            >
              Memories
            </button>
          </div>

          {/* Overview Tab */}
          {activeTab === 'overview' && (
            <div className="admin-tab-content">
              <div className="stats-grid">
                <div className="stat-card">
                  <div className="stat-icon">👥</div>
                  <div className="stat-info">
                    <h3>{stats.total_users?.toLocaleString()}</h3>
                    <p>Total Users</p>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">💕</div>
                  <div className="stat-info">
                    <h3>{stats.total_memories?.toLocaleString()}</h3>
                    <p>Total Memories</p>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">📦</div>
                  <div className="stat-info">
                    <h3>{stats.total_orders?.toLocaleString()}</h3>
                    <p>Total Orders</p>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">💰</div>
                  <div className="stat-info">
                    <h3>${stats.total_revenue?.toLocaleString()}</h3>
                    <p>Total Revenue</p>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">📊</div>
                  <div className="stat-info">
                    <h3>{stats.active_subscriptions?.toLocaleString()}</h3>
                    <p>Active Subscriptions</p>
                  </div>
                </div>
                <div className="stat-card">
                  <div className="stat-icon">⏳</div>
                  <div className="stat-info">
                    <h3>{stats.pending_orders?.toLocaleString()}</h3>
                    <p>Pending Orders</p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Users Tab */}
          {activeTab === 'users' && (
            <div className="admin-tab-content">
              <div className="data-table">
                <div className="table-header">
                  <h3>Users ({users.length})</h3>
                </div>
                <div className="table-content">
                  {users.map(user => (
                    <div key={user.id} className="table-row">
                      <div className="table-cell">
                        <strong>{user.display_name || 'No Name'}</strong>
                        <br />
                        <small>{user.email}</small>
                      </div>
                      <div className="table-cell">
                        <span className={`plan-badge ${user.subscription_plan?.toLowerCase()}`}>
                          {user.subscription_plan}
                        </span>
                      </div>
                      <div className="table-cell">
                        {user.total_memories} memories
                        <br />
                        {user.total_orders} orders
                      </div>
                      <div className="table-cell">
                        {new Date(user.created_at).toLocaleDateString()}
                      </div>
                      <div className="table-cell">
                        <button 
                          className="btn btn-danger btn-sm"
                          onClick={() => deleteUser(user.id)}
                        >
                          Delete
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Orders Tab */}
          {activeTab === 'orders' && (
            <div className="admin-tab-content">
              <div className="data-table">
                <div className="table-header">
                  <h3>Orders ({orders.length})</h3>
                </div>
                <div className="table-content">
                  {orders.map(order => (
                    <div key={order.id} className="table-row">
                      <div className="table-cell">
                        <strong>{order.order_number}</strong>
                        <br />
                        <small>{order.user_email}</small>
                      </div>
                      <div className="table-cell">
                        {order.memory_title}
                      </div>
                      <div className="table-cell">
                        <select 
                          value={order.status}
                          onChange={(e) => updateOrderStatus(order.id, e.target.value)}
                          className="status-select"
                        >
                          <option value="paid">Paid</option>
                          <option value="processing">Processing</option>
                          <option value="shipped">Shipped</option>
                          <option value="delivered">Delivered</option>
                          <option value="cancelled">Cancelled</option>
                        </select>
                      </div>
                      <div className="table-cell">
                        ${order.amount}
                      </div>
                      <div className="table-cell">
                        {new Date(order.created_at).toLocaleDateString()}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Memories Tab */}
          {activeTab === 'memories' && (
            <div className="admin-tab-content">
              <div className="data-table">
                <div className="table-header">
                  <h3>Memories ({memories.length})</h3>
                </div>
                <div className="table-content">
                  {memories.map(memory => (
                    <div key={memory.id} className="table-row">
                      <div className="table-cell">
                        <strong>{memory.title}</strong>
                        <br />
                        <small>{memory.user_email}</small>
                      </div>
                      <div className="table-cell">
                        <span className={`feature-badge ${memory.has_audio ? 'has-audio' : 'no-audio'}`}>
                          {memory.has_audio ? 'Has Audio' : 'No Audio'}
                        </span>
                        <br />
                        <span className={`feature-badge ${memory.has_order ? 'has-order' : 'no-order'}`}>
                          {memory.has_order ? 'Has Order' : 'No Order'}
                        </span>
                      </div>
                      <div className="table-cell">
                        {new Date(memory.created_at).toLocaleDateString()}
                      </div>
                      <div className="table-cell">
                        <button 
                          className="btn btn-danger btn-sm"
                          onClick={() => deleteMemory(memory.id)}
                        >
                          Delete
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;