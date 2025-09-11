# MemoWindow React App

A React version of the MemoWindow application that transforms voice recordings into beautiful waveform art.

## Features

- **Voice to Waveform Conversion**: Upload audio files or record directly to create stunning waveform visualizations
- **Firebase Authentication**: Secure user authentication with Google and email/password options
- **Real-time Preview**: See your waveform creation in real-time as you work
- **High-Quality Output**: Generate print-ready waveform images with QR codes
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Modern UI/UX**: Clean, intuitive interface with smooth animations

## Technology Stack

- **React 18**: Modern React with hooks and functional components
- **React Router**: Client-side routing for navigation
- **Firebase**: Authentication and cloud storage
- **Canvas API**: Audio processing and waveform generation
- **CSS3**: Modern styling with gradients and animations

## Project Structure

```
src/
├── components/          # Reusable UI components
│   ├── Header.js       # Navigation header
│   ├── Login.js        # Authentication form
│   ├── MemoryCreator.js # Main memory creation interface
│   └── WaveformList.js # Display user's memories
├── pages/              # Page components
│   ├── LandingPage.js  # Marketing landing page
│   └── AppPage.js      # Main application page
├── hooks/              # Custom React hooks
│   └── useAuth.js      # Authentication state management
├── services/           # External service integrations
│   ├── firebase.js     # Firebase configuration
│   └── storage.js      # File upload utilities
├── utils/              # Utility functions
│   └── audioProcessor.js # Audio processing and waveform generation
└── App.js              # Main application component
```

## Getting Started

### Prerequisites

- Node.js (v14 or higher)
- npm or yarn
- Firebase project with Authentication and Storage enabled

### Installation

1. Clone the repository:
```bash
cd /workspace/Reactor
```

2. Install dependencies:
```bash
npm install
```

3. Configure Firebase:
   - Update `src/services/firebase.js` with your Firebase configuration
   - Enable Authentication and Storage in your Firebase console

4. Start the development server:
```bash
npm start
```

5. Open [http://localhost:3000](http://localhost:3000) to view it in the browser.

## Key Features Implemented

### Audio Processing
- Real-time audio file processing using Web Audio API
- Waveform generation with customizable parameters
- Support for multiple audio formats (MP3, WAV, M4A, etc.)
- High-resolution canvas rendering for print quality

### User Authentication
- Firebase Authentication integration
- Google Sign-In and email/password authentication
- Protected routes and user session management
- Automatic redirects based on authentication state

### File Management
- Drag-and-drop file upload interface
- Real-time recording capabilities
- Firebase Storage integration for file persistence
- QR code generation for sharing memories

### Responsive Design
- Mobile-first approach with responsive breakpoints
- Touch-friendly interface elements
- Optimized for various screen sizes
- Modern CSS with gradients and animations

## Available Scripts

- `npm start`: Runs the app in development mode
- `npm build`: Builds the app for production
- `npm test`: Launches the test runner
- `npm eject`: Ejects from Create React App (one-way operation)

## Deployment

The app can be deployed to any static hosting service:

1. Build the production version:
```bash
npm run build
```

2. Deploy the `build` folder to your hosting service

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge

Note: Some features require modern browser APIs (Web Audio API, Canvas, etc.)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License.