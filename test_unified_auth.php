<?php
/**
 * Test page for unified authentication system
 */

require_once 'unified_auth.php';

// Test the authentication functions
echo "<h1>Unified Auth System Test</h1>";

echo "<h2>Session Information:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "</pre>";

echo "<h2>Authentication Status:</h2>";
echo "<pre>";
echo "Is Authenticated: " . (isAuthenticated() ? 'YES' : 'NO') . "\n";
$user = getCurrentUser();
echo "Current User: " . ($user ? json_encode($user, JSON_PRETTY_PRINT) : 'None') . "\n";
echo "Is Admin: " . (isCurrentUserAdmin() ? 'YES' : 'NO') . "\n";
echo "</pre>";

echo "<h2>API Endpoints:</h2>";
echo "<ul>";
echo "<li><a href='unified_auth.php' target='_blank'>GET /unified_auth.php</a> - Check auth status</li>";
echo "<li><a href='#' onclick='testLogout()'>DELETE /unified_auth.php</a> - Logout</li>";
echo "</ul>";

echo "<h2>Test Functions:</h2>";
echo "<button onclick='testAuth()'>Test Authentication</button>";
echo "<button onclick='testLogout()'>Test Logout</button>";

?>

<script>
async function testAuth() {
    try {
        const response = await fetch('unified_auth.php', {
            method: 'GET',
            credentials: 'include'
        });
        const data = await response.json();
        console.log('Auth test result:', data);
        alert('Auth test result: ' + JSON.stringify(data, null, 2));
    } catch (error) {
        console.error('Auth test error:', error);
        alert('Auth test error: ' + error.message);
    }
}

async function testLogout() {
    try {
        const response = await fetch('unified_auth.php', {
            method: 'DELETE',
            credentials: 'include'
        });
        const data = await response.json();
        console.log('Logout test result:', data);
        alert('Logout test result: ' + JSON.stringify(data, null, 2));
    } catch (error) {
        console.error('Logout test error:', error);
        alert('Logout test error: ' + error.message);
    }
}
</script>
