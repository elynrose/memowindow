# MemoWindow - Complete React Application

A production-ready React application for creating beautiful waveform memories from voice recordings, with full e-commerce capabilities, voice cloning, and admin management.

## 🚀 Features

### Core Functionality
- **Audio Upload & Recording**: Upload audio files or record directly in the browser
- **Waveform Generation**: Create beautiful waveform visualizations with Canvas API
- **Firebase Integration**: User authentication and file storage
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **PWA Support**: Progressive Web App capabilities with offline support

### E-commerce Features
- **Print Ordering**: Order physical prints of waveform memories
- **Stripe Payment Integration**: Secure payment processing
- **Order Management**: Track orders from creation to delivery
- **Shipping Integration**: Real-time shipping and tracking

### Advanced Features
- **Voice Cloning**: AI-powered voice synthesis from uploaded audio
- **Subscription Management**: Multiple subscription tiers with usage limits
- **Admin Dashboard**: Comprehensive admin panel for user and order management
- **Analytics**: Detailed analytics and reporting
- **QR Code Sharing**: Share memories via QR codes

### Legal & Compliance
- **Privacy Policy**: Comprehensive privacy policy
- **Terms of Service**: Detailed terms and conditions
- **Refund Policy**: Clear refund and cancellation policies

## 🏗️ Architecture

### Frontend (React)
```
src/
├── components/          # Reusable React components
│   ├── Header.js       # Navigation header with auth
│   ├── MemoryCreator.js # Main memory creation interface
│   ├── WaveformList.js # List of user's memories
│   ├── VoiceClone.js   # Voice cloning functionality
│   └── Checkout.js     # Payment and checkout process
├── pages/              # Page components
│   ├── LandingPage.js  # Public landing page
│   ├── AppPage.js      # Main application page
│   ├── MemoriesPage.js # Dedicated memories management
│   ├── OrdersPage.js   # Order tracking and management
│   ├── PlayPage.js     # Public memory sharing page
│   ├── AdminDashboard.js # Admin management panel
│   ├── PrivacyPolicy.js # Privacy policy page
│   ├── TermsOfService.js # Terms of service page
│   ├── RefundPolicy.js # Refund policy page
│   └── OrderSuccess.js # Order confirmation page
├── hooks/              # Custom React hooks
│   └── useAuth.js      # Authentication state management
├── services/           # External service integrations
│   ├── firebase.js     # Firebase configuration
│   ├── storage.js      # File storage service
│   └── api.js          # Backend API service
├── utils/              # Utility functions
│   └── audioProcessor.js # Audio processing utilities
└── App.js              # Main application with routing
```

### Backend (Express.js)
```
backend/
├── server.js           # Main Express server
├── package.json        # Backend dependencies
└── uploads/            # File upload directory
```

## 🛠️ Getting Started

### Prerequisites

- Node.js (v16 or higher)
- npm or yarn
- Firebase project (for authentication and storage)
- Stripe account (for payments)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Reactor
   ```

2. **Install frontend dependencies**
   ```bash
   npm install
   ```

3. **Install backend dependencies**
   ```bash
   cd backend
   npm install
   cd ..
   ```

4. **Environment Setup**
   Create a `.env` file in the root directory:
   ```env
   REACT_APP_FIREBASE_API_KEY=your_firebase_api_key
   REACT_APP_FIREBASE_AUTH_DOMAIN=your_firebase_auth_domain
   REACT_APP_FIREBASE_PROJECT_ID=your_firebase_project_id
   REACT_APP_FIREBASE_STORAGE_BUCKET=your_firebase_storage_bucket
   REACT_APP_FIREBASE_MESSAGING_SENDER_ID=your_firebase_messaging_sender_id
   REACT_APP_FIREBASE_APP_ID=your_firebase_app_id
   REACT_APP_STRIPE_PUBLISHABLE_KEY=your_stripe_publishable_key
   REACT_APP_API_URL=http://localhost:3001/api
   ```

5. **Start the development servers**
   
   **Terminal 1 - Backend:**
   ```bash
   cd backend
   npm run dev
   ```
   
   **Terminal 2 - Frontend:**
   ```bash
   npm start
   ```

6. **Access the application**
   - Frontend: [http://localhost:3000](http://localhost:3000)
   - Backend API: [http://localhost:3001/api](http://localhost:3001/api)

## 📱 Available Scripts

### Frontend
- `npm start` - Runs the app in development mode
- `npm test` - Launches the test runner
- `npm run build` - Builds the app for production
- `npm run eject` - Ejects from Create React App (one-way operation)

### Backend
- `npm start` - Runs the backend server
- `npm run dev` - Runs the backend with nodemon for development

## 🎯 User Flows

### 1. Memory Creation Flow
1. User signs up/logs in
2. Navigates to memory creation
3. Uploads or records audio
4. Generates waveform preview
5. Creates memory with title
6. Optionally creates voice clone
7. Memory saved to user's collection

### 2. Order Flow
1. User views their memories
2. Selects memory for printing
3. Proceeds to checkout
4. Enters shipping information
5. Processes payment via Stripe
6. Receives order confirmation
7. Tracks order status

### 3. Voice Cloning Flow
1. User creates a memory with audio
2. Accesses voice cloning feature
3. Creates voice clone from audio
4. Generates new messages using cloned voice
5. Downloads or shares generated audio

## 🔧 Technologies Used

### Frontend
- **React 18** - Modern React with hooks
- **React Router 6** - Client-side routing
- **Firebase 9** - Authentication and storage
- **Web Audio API** - Audio processing
- **Canvas API** - Waveform rendering
- **Stripe Elements** - Payment processing
- **CSS3** - Modern styling with flexbox/grid

### Backend
- **Express.js** - Web framework
- **Multer** - File upload handling
- **CORS** - Cross-origin resource sharing
- **Helmet** - Security headers
- **Rate Limiting** - API protection
- **JWT** - Authentication tokens

### External Services
- **Firebase Auth** - User authentication
- **Firebase Storage** - File storage
- **Stripe** - Payment processing
- **AI Voice Services** - Voice cloning (integrated)

## 🚀 Deployment

### Frontend Deployment (Netlify/Vercel)
1. Build the production version:
   ```bash
   npm run build
   ```
2. Deploy the `build` folder to your hosting service
3. Configure environment variables in your hosting platform

### Backend Deployment (Heroku/Railway)
1. Set up your backend repository
2. Configure environment variables
3. Deploy using your preferred platform
4. Update frontend API URL to production backend

## 📊 Production Features

### Security
- JWT token authentication
- Rate limiting on API endpoints
- CORS protection
- Helmet security headers
- Input validation and sanitization

### Performance
- Code splitting and lazy loading
- Image optimization
- Caching strategies
- CDN integration ready

### Monitoring
- Error tracking (Sentry ready)
- Analytics integration
- Performance monitoring
- User behavior tracking

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, email support@memowindow.com or join our Slack channel.

## 🗺️ Roadmap

- [ ] Mobile app (React Native)
- [ ] Advanced AI features
- [ ] Social sharing integration
- [ ] Bulk memory processing
- [ ] Advanced analytics dashboard
- [ ] Multi-language support
- [ ] API rate limiting improvements
- [ ] Advanced voice cloning models