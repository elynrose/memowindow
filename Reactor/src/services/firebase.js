import { initializeApp } from 'firebase/app';
import { getAuth, GoogleAuthProvider, EmailAuthProvider } from 'firebase/auth';
import { getStorage } from 'firebase/storage';

// Firebase configuration - using the same config as the original app
const firebaseConfig = {
  apiKey: "AIzaSyAUTI2-Ab0-ZKaV0kon_60Uoa6SqJuldjk",
  authDomain: "leadlink-ai-api08.firebaseapp.com",
  projectId: "leadlink-ai-api08",
  storageBucket: "leadlink-ai-api08.firebasestorage.app",
  messagingSenderId: "365232756820",
  appId: "1:365232756820:web:55fcb722110cd5480d35c1"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Firebase services
export const auth = getAuth(app);
export const storage = getStorage(app);
export const googleProvider = new GoogleAuthProvider();
export const emailProvider = new EmailAuthProvider();

export default app;