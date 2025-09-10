<?php
// Firebase configuration - hardcoded values
$firebaseConfig = [
    'FIREBASE_API_KEY' => 'AIzaSyAUTI2-Ab0-ZKaV0kon_60Uoa6SqJuldjk',
    'FIREBASE_AUTH_DOMAIN' => 'leadlink-ai-api08.firebaseapp.com',
    'FIREBASE_PROJECT_ID' => 'leadlink-ai-api08',
    'FIREBASE_STORAGE_BUCKET' => 'leadlink-ai-api08.firebasestorage.app',
    'FIREBASE_MESSAGING_SENDER_ID' => '365232756820',
    'FIREBASE_APP_ID' => '1:365232756820:web:55fcb722110cd5480d35c1'
];

// Set content type to JavaScript
header('Content-Type: application/javascript');

// Output Firebase configuration as JavaScript
echo "// Firebase configuration loaded from PHP\n";
echo "import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';\n";
echo "import { getAuth, GoogleAuthProvider, EmailAuthProvider } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';\n";
echo "import { getStorage } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-storage.js';\n\n";

echo "const firebaseConfig = {\n";
echo "  apiKey: \"" . $firebaseConfig['FIREBASE_API_KEY'] . "\",\n";
echo "  authDomain: \"" . $firebaseConfig['FIREBASE_AUTH_DOMAIN'] . "\",\n";
echo "  projectId: \"" . $firebaseConfig['FIREBASE_PROJECT_ID'] . "\",\n";
echo "  storageBucket: \"" . $firebaseConfig['FIREBASE_STORAGE_BUCKET'] . "\",\n";
echo "  messagingSenderId: \"" . $firebaseConfig['FIREBASE_MESSAGING_SENDER_ID'] . "\",\n";
echo "  appId: \"" . $firebaseConfig['FIREBASE_APP_ID'] . "\"\n";
echo "};\n\n";

echo "// Initialize Firebase\n";
echo "const app = initializeApp(firebaseConfig);\n";
echo "const auth = getAuth(app);\n";
echo "const googleProvider = new GoogleAuthProvider();\n";
echo "const emailProvider = new EmailAuthProvider();\n";
echo "const storage = getStorage(app);\n\n";

echo "export { auth, googleProvider, emailProvider, storage };\n";
?>
