// Production Firebase Configuration
// Copy this to src/firebase-config.js and fill in your actual values

import { initializeApp } from 'firebase/app';
import { getAuth, GoogleAuthProvider, EmailAuthProvider } from 'firebase/auth';
import { getStorage } from 'firebase/storage';

// Your Production Firebase Configuration
const firebaseConfig = {
  apiKey: "your_production_firebase_api_key",
  authDomain: "your-project-id.firebaseapp.com",
  projectId: "your-project-id",
  storageBucket: "your-project-id.firebasestorage.app",
  messagingSenderId: "your_messaging_sender_id",
  appId: "your_app_id"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const googleProvider = new GoogleAuthProvider();
const emailProvider = new EmailAuthProvider();
const storage = getStorage(app);

export { auth, googleProvider, emailProvider, storage };
