<?php
// Read Firebase configuration from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return [];
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
    
    return $env;
}

// Load environment variables
$env = loadEnv(__DIR__ . '/.env');

// Set content type to JavaScript
header('Content-Type: application/javascript');

// Output Firebase configuration as JavaScript
echo "// Firebase configuration loaded from .env file\n";
echo "import { initializeApp } from 'firebase/app';\n";
echo "import { getAuth, GoogleAuthProvider, EmailAuthProvider } from 'firebase/auth';\n";
echo "import { getStorage } from 'firebase/storage';\n\n";

echo "const firebaseConfig = {\n";
echo "  apiKey: \"" . ($env['FIREBASE_API_KEY'] ?? '') . "\",\n";
echo "  authDomain: \"" . ($env['FIREBASE_AUTH_DOMAIN'] ?? '') . "\",\n";
echo "  projectId: \"" . ($env['FIREBASE_PROJECT_ID'] ?? '') . "\",\n";
echo "  storageBucket: \"" . ($env['FIREBASE_STORAGE_BUCKET'] ?? '') . "\",\n";
echo "  messagingSenderId: \"" . ($env['FIREBASE_MESSAGING_SENDER_ID'] ?? '') . "\",\n";
echo "  appId: \"" . ($env['FIREBASE_APP_ID'] ?? '') . "\"\n";
echo "};\n\n";

echo "// Initialize Firebase\n";
echo "const app = initializeApp(firebaseConfig);\n";
echo "const auth = getAuth(app);\n";
echo "const googleProvider = new GoogleAuthProvider();\n";
echo "const emailProvider = new EmailAuthProvider();\n";
echo "const storage = getStorage(app);\n\n";

echo "export { auth, googleProvider, emailProvider, storage };\n";
?>
