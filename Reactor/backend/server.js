const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(helmet());
app.use(cors({
  origin: process.env.FRONTEND_URL || 'http://localhost:3000',
  credentials: true
}));
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100 // limit each IP to 100 requests per windowMs
});
app.use('/api/', limiter);

// File upload configuration
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    const uploadDir = 'uploads/';
    if (!fs.existsSync(uploadDir)) {
      fs.mkdirSync(uploadDir, { recursive: true });
    }
    cb(null, uploadDir);
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, file.fieldname + '-' + uniqueSuffix + path.extname(file.originalname));
  }
});

const upload = multer({ 
  storage,
  limits: {
    fileSize: 50 * 1024 * 1024 // 50MB limit
  },
  fileFilter: (req, file, cb) => {
    // Allow audio files
    if (file.mimetype.startsWith('audio/')) {
      cb(null, true);
    } else {
      cb(new Error('Only audio files are allowed'), false);
    }
  }
});

// Mock database (in production, use a real database)
const mockDatabase = {
  users: [],
  memories: [],
  orders: [],
  voiceClones: [],
  subscriptions: []
};

// Authentication middleware (simplified)
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }

  // In production, verify JWT token here
  // For demo purposes, we'll just check if token exists
  req.user = { id: 'demo-user-id', email: 'demo@example.com' };
  next();
};

// Admin middleware
const requireAdmin = (req, res, next) => {
  // In production, check if user has admin role
  // For demo purposes, we'll allow all authenticated users
  next();
};

// Routes

// Health check
app.get('/api/health', (req, res) => {
  res.json({ status: 'OK', timestamp: new Date().toISOString() });
});

// Authentication routes
app.post('/api/auth/login', (req, res) => {
  const { email, password } = req.body;
  
  // In production, verify credentials against database
  if (email && password) {
    const token = 'demo-jwt-token';
    res.json({ 
      success: true, 
      token,
      user: { id: 'demo-user-id', email, displayName: 'Demo User' }
    });
  } else {
    res.status(400).json({ error: 'Invalid credentials' });
  }
});

app.post('/api/auth/register', (req, res) => {
  const { email, password, displayName } = req.body;
  
  // In production, create user in database
  const user = {
    id: Date.now().toString(),
    email,
    displayName,
    createdAt: new Date().toISOString()
  };
  
  mockDatabase.users.push(user);
  
  const token = 'demo-jwt-token';
  res.json({ 
    success: true, 
    token,
    user 
  });
});

// Memories routes
app.get('/api/memories', authenticateToken, (req, res) => {
  const { user_id } = req.query;
  const userMemories = mockDatabase.memories.filter(m => m.userId === user_id);
  res.json({ memories: userMemories });
});

app.post('/api/memories', authenticateToken, (req, res) => {
  const memory = {
    id: Date.now().toString(),
    ...req.body,
    createdAt: new Date().toISOString()
  };
  
  mockDatabase.memories.push(memory);
  res.json({ success: true, memory });
});

app.delete('/api/memories/:id', authenticateToken, (req, res) => {
  const { id } = req.params;
  const index = mockDatabase.memories.findIndex(m => m.id === id);
  
  if (index !== -1) {
    mockDatabase.memories.splice(index, 1);
    res.json({ success: true });
  } else {
    res.status(404).json({ error: 'Memory not found' });
  }
});

app.get('/api/memories/play/:uid', (req, res) => {
  const { uid } = req.params;
  const memory = mockDatabase.memories.find(m => m.uniqueId === uid);
  
  if (memory) {
    res.json({ success: true, memory });
  } else {
    res.status(404).json({ error: 'Memory not found' });
  }
});

// Orders routes
app.get('/api/orders', authenticateToken, (req, res) => {
  const { user_id } = req.query;
  const userOrders = mockDatabase.orders.filter(o => o.userId === user_id);
  res.json({ orders: userOrders });
});

app.post('/api/orders', authenticateToken, (req, res) => {
  const order = {
    id: Date.now().toString(),
    orderNumber: `MW-${Date.now()}`,
    ...req.body,
    status: 'paid',
    createdAt: new Date().toISOString()
  };
  
  mockDatabase.orders.push(order);
  res.json({ success: true, order });
});

app.put('/api/orders/:id/status', authenticateToken, (req, res) => {
  const { id } = req.params;
  const { status } = req.body;
  const order = mockDatabase.orders.find(o => o.id === id);
  
  if (order) {
    order.status = status;
    res.json({ success: true, order });
  } else {
    res.status(404).json({ error: 'Order not found' });
  }
});

// Voice cloning routes
app.get('/api/voice-clones', authenticateToken, (req, res) => {
  const { user_id } = req.query;
  const userClones = mockDatabase.voiceClones.filter(v => v.userId === user_id);
  res.json({ voiceClones: userClones });
});

app.post('/api/voice-clones', authenticateToken, (req, res) => {
  const voiceClone = {
    id: Date.now().toString(),
    ...req.body,
    status: 'processing',
    createdAt: new Date().toISOString()
  };
  
  mockDatabase.voiceClones.push(voiceClone);
  res.json({ success: true, voiceClone });
});

app.post('/api/voice-clones/generate', authenticateToken, (req, res) => {
  const { voiceId, message } = req.body;
  
  // In production, call AI service to generate voice
  res.json({ 
    success: true, 
    audioUrl: 'https://example.com/generated-audio.wav',
    message: 'Voice message generated successfully'
  });
});

// File upload route
app.post('/api/upload', authenticateToken, upload.single('file'), (req, res) => {
  if (!req.file) {
    return res.status(400).json({ error: 'No file uploaded' });
  }
  
  res.json({
    success: true,
    fileUrl: `/uploads/${req.file.filename}`,
    filename: req.file.filename
  });
});

// Admin routes
app.get('/api/admin/stats', authenticateToken, requireAdmin, (req, res) => {
  const stats = {
    total_users: mockDatabase.users.length,
    total_memories: mockDatabase.memories.length,
    total_orders: mockDatabase.orders.length,
    total_revenue: mockDatabase.orders.reduce((sum, order) => sum + (order.amount || 0), 0),
    active_subscriptions: mockDatabase.subscriptions.filter(s => s.status === 'active').length,
    pending_orders: mockDatabase.orders.filter(o => o.status === 'paid').length
  };
  
  res.json({ stats });
});

app.get('/api/admin/users', authenticateToken, requireAdmin, (req, res) => {
  res.json({ users: mockDatabase.users });
});

app.get('/api/admin/orders', authenticateToken, requireAdmin, (req, res) => {
  res.json({ orders: mockDatabase.orders });
});

app.get('/api/admin/memories', authenticateToken, requireAdmin, (req, res) => {
  res.json({ memories: mockDatabase.memories });
});

// Payment routes (Stripe integration)
app.post('/api/payments/create-intent', authenticateToken, (req, res) => {
  const { amount, currency } = req.body;
  
  // In production, create Stripe payment intent
  res.json({
    success: true,
    clientSecret: 'demo-client-secret',
    paymentIntentId: 'demo-payment-intent-id'
  });
});

// Serve uploaded files
app.use('/uploads', express.static('uploads'));

// Error handling middleware
app.use((error, req, res, next) => {
  console.error('Error:', error);
  res.status(500).json({ error: 'Internal server error' });
});

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Route not found' });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Backend server running on port ${PORT}`);
  console.log(`📊 API available at http://localhost:${PORT}/api`);
});

module.exports = app;