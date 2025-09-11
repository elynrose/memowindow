// API service for backend communication
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:3001/api';

class ApiService {
  // Generic request method
  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const config = {
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      ...options,
    };

    // Add auth token if available
    const token = localStorage.getItem('authToken');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  // Authentication endpoints
  async login(email, password) {
    return this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  }

  async register(email, password, displayName) {
    return this.request('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ email, password, displayName }),
    });
  }

  async logout() {
    return this.request('/auth/logout', {
      method: 'POST',
    });
  }

  // Memories endpoints
  async getMemories(userId) {
    return this.request(`/memories?user_id=${userId}`);
  }

  async createMemory(memoryData) {
    return this.request('/memories', {
      method: 'POST',
      body: JSON.stringify(memoryData),
    });
  }

  async updateMemory(memoryId, memoryData) {
    return this.request(`/memories/${memoryId}`, {
      method: 'PUT',
      body: JSON.stringify(memoryData),
    });
  }

  async deleteMemory(memoryId) {
    return this.request(`/memories/${memoryId}`, {
      method: 'DELETE',
    });
  }

  async getMemoryForPlay(uniqueId) {
    return this.request(`/memories/play/${uniqueId}`);
  }

  // Orders endpoints
  async getOrders(userId) {
    return this.request(`/orders?user_id=${userId}`);
  }

  async createOrder(orderData) {
    return this.request('/orders', {
      method: 'POST',
      body: JSON.stringify(orderData),
    });
  }

  async updateOrderStatus(orderId, status) {
    return this.request(`/orders/${orderId}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    });
  }

  async cancelOrder(orderId) {
    return this.request(`/orders/${orderId}/cancel`, {
      method: 'POST',
    });
  }

  // Voice cloning endpoints
  async getVoiceClones(userId) {
    return this.request(`/voice-clones?user_id=${userId}`);
  }

  async createVoiceClone(cloneData) {
    return this.request('/voice-clones', {
      method: 'POST',
      body: JSON.stringify(cloneData),
    });
  }

  async generateVoiceMessage(voiceId, message) {
    return this.request('/voice-clones/generate', {
      method: 'POST',
      body: JSON.stringify({ voiceId, message }),
    });
  }

  async deleteVoiceClone(cloneId) {
    return this.request(`/voice-clones/${cloneId}`, {
      method: 'DELETE',
    });
  }

  // Subscription endpoints
  async getSubscription(userId) {
    return this.request(`/subscriptions?user_id=${userId}`);
  }

  async createSubscription(subscriptionData) {
    return this.request('/subscriptions', {
      method: 'POST',
      body: JSON.stringify(subscriptionData),
    });
  }

  async updateSubscription(subscriptionId, subscriptionData) {
    return this.request(`/subscriptions/${subscriptionId}`, {
      method: 'PUT',
      body: JSON.stringify(subscriptionData),
    });
  }

  async cancelSubscription(subscriptionId) {
    return this.request(`/subscriptions/${subscriptionId}/cancel`, {
      method: 'POST',
    });
  }

  // Payment endpoints
  async createPaymentIntent(amount, currency = 'usd') {
    return this.request('/payments/create-intent', {
      method: 'POST',
      body: JSON.stringify({ amount, currency }),
    });
  }

  async confirmPayment(paymentIntentId) {
    return this.request('/payments/confirm', {
      method: 'POST',
      body: JSON.stringify({ paymentIntentId }),
    });
  }

  // Admin endpoints
  async getAdminStats() {
    return this.request('/admin/stats');
  }

  async getAdminUsers() {
    return this.request('/admin/users');
  }

  async getAdminOrders() {
    return this.request('/admin/orders');
  }

  async getAdminMemories() {
    return this.request('/admin/memories');
  }

  async deleteAdminUser(userId) {
    return this.request(`/admin/users/${userId}`, {
      method: 'DELETE',
    });
  }

  async deleteAdminMemory(memoryId) {
    return this.request(`/admin/memories/${memoryId}`, {
      method: 'DELETE',
    });
  }

  // File upload endpoints
  async uploadFile(file, type = 'audio') {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);

    const token = localStorage.getItem('authToken');
    const response = await fetch(`${API_BASE_URL}/upload`, {
      method: 'POST',
      headers: {
        ...(token && { Authorization: `Bearer ${token}` }),
      },
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`Upload failed: ${response.status}`);
    }

    return await response.json();
  }

  // Analytics endpoints
  async getAnalytics(dateRange = '30d') {
    return this.request(`/analytics?range=${dateRange}`);
  }

  async getUserAnalytics(userId, dateRange = '30d') {
    return this.request(`/analytics/user/${userId}?range=${dateRange}`);
  }
}

// Create and export a singleton instance
const apiService = new ApiService();
export default apiService;