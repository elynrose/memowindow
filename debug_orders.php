<?php
// Debug script to test orders page behavior
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Orders</title>
</head>
<body>
    <h1>Debug Orders Page</h1>
    <div id="debug-info"></div>
    
    <script type="module">
        import { auth } from './firebase-config.php';
        
        const debugDiv = document.getElementById('debug-info');
        
        function log(message) {
            console.log(message);
            debugDiv.innerHTML += '<p>' + message + '</p>';
        }
        
        log('🔍 Starting debug...');
        
        // Check if auth object exists
        if (auth) {
            log('✅ Auth object found');
            
            // Check current user
            const currentUser = auth.currentUser;
            if (currentUser) {
                log('✅ Current user found: ' + currentUser.email);
            } else {
                log('❌ No current user');
            }
            
            // Listen for auth state changes
            auth.onAuthStateChanged((user) => {
                if (user) {
                    log('✅ Auth state changed: User logged in - ' + user.email);
                } else {
                    log('❌ Auth state changed: User logged out');
                }
            });
            
        } else {
            log('❌ Auth object not found');
        }
        
        // Check sessionStorage
        const storedUser = sessionStorage.getItem('currentUser');
        if (storedUser) {
            log('✅ Stored user found: ' + storedUser);
        } else {
            log('❌ No stored user in sessionStorage');
        }
        
        // Check window.auth
        if (window.auth) {
            log('✅ window.auth found');
        } else {
            log('❌ window.auth not found');
        }
        
        // Test orders link click
        setTimeout(() => {
            log('🔗 Testing orders link behavior...');
            if (window.auth && window.auth.currentUser) {
                log('✅ Would redirect to orders.php?user_id=' + window.auth.currentUser.uid);
            } else {
                log('❌ Would redirect to login.php');
            }
        }, 2000);
        
    </script>
</body>
</html>
