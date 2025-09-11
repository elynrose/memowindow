<?php
/**
 * Functionality Test Suite for MemoWindow
 * Tests core application functionality
 */

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config.php';
require_once 'secure_auth.php';
require_once 'secure_db.php';

class FunctionalityTestSuite {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    
    public function runTest($name, $testFunction) {
        echo "🧪 Testing: $name\n";
        try {
            $result = $testFunction();
            if ($result) {
                echo "✅ PASSED: $name\n";
                $this->passed++;
            } else {
                echo "❌ FAILED: $name\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "❌ ERROR: $name - " . $e->getMessage() . "\n";
            $this->failed++;
        }
        echo "\n";
    }
    
    public function runAllTests() {
        echo "🔧 MEMOWINDOW FUNCTIONALITY TEST SUITE\n";
        echo "======================================\n\n";
        
        // Database Tests
        $this->runTest("Database - Connection", [$this, 'testDatabaseConnection']);
        $this->runTest("Database - Tables Exist", [$this, 'testTablesExist']);
        $this->runTest("Database - User Operations", [$this, 'testUserOperations']);
        $this->runTest("Database - Memory Operations", [$this, 'testMemoryOperations']);
        $this->runTest("Database - Order Operations", [$this, 'testOrderOperations']);
        
        // API Endpoint Tests
        $this->runTest("API - Get Packages", [$this, 'testGetPackages']);
        $this->runTest("API - Get User Audio Limit", [$this, 'testGetUserAudioLimit']);
        $this->runTest("API - Dashboard Stats", [$this, 'testDashboardStats']);
        
        // File System Tests
        $this->runTest("File System - Upload Directories", [$this, 'testUploadDirectories']);
        $this->runTest("File System - Backup Directories", [$this, 'testBackupDirectories']);
        $this->runTest("File System - Permissions", [$this, 'testFilePermissions']);
        
        // Configuration Tests
        $this->runTest("Configuration - Database Config", [$this, 'testDatabaseConfig']);
        $this->runTest("Configuration - API Keys", [$this, 'testAPIKeys']);
        $this->runTest("Configuration - Base URL", [$this, 'testBaseURL']);
        
        // Authentication Tests
        $this->runTest("Authentication - Session Handling", [$this, 'testSessionHandling']);
        $this->runTest("Authentication - Admin Check", [$this, 'testAdminCheck']);
        
        $this->printResults();
    }
    
    // Database Tests
    public function testDatabaseConnection() {
        try {
            $db = SecureDB::getInstance();
            $result = $db->fetchOne("SELECT 1 as test");
            return $result['test'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function testTablesExist() {
        try {
            $db = SecureDB::getInstance();
            $tables = ['users', 'wave_assets', 'orders', 'subscription_packages', 'admin_users'];
            
            foreach ($tables as $table) {
                $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
                if (!$result) {
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function testUserOperations() {
        try {
            $db = SecureDB::getInstance();
            
            // Test user creation
            $testUserId = 'test_user_' . time();
            $testEmail = 'test_' . time() . '@example.com';
            $userId = createUser($testUserId, $testEmail, 'Test User');
            
            if (!$userId) {
                return false;
            }
            
            // Test user retrieval
            $user = getUserByFirebaseUID($testUserId);
            if (!$user || $user['firebase_uid'] !== $testUserId) {
                return false;
            }
            
            // Test user update
            $updated = updateUserLastLogin($testUserId);
            if (!$updated) {
                return false;
            }
            
            // Clean up test user
            $db->execute("DELETE FROM users WHERE firebase_uid = ?", [$testUserId]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function testMemoryOperations() {
        try {
            $db = SecureDB::getInstance();
            
            // Test memory retrieval
            $memories = getUserMemories('test_user', 5, 0);
            return is_array($memories);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function testOrderOperations() {
        try {
            $db = SecureDB::getInstance();
            
            // Test order retrieval
            $orders = getUserOrders('test_user', 5, 0);
            return is_array($orders);
        } catch (Exception $e) {
            return false;
        }
    }
    
    // API Endpoint Tests
    public function testGetPackages() {
        try {
            $url = BASE_URL . '/get_packages.php';
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            if ($response === false) {
                return false;
            }
            
            $data = json_decode($response, true);
            return is_array($data) && isset($data['packages']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function testGetUserAudioLimit() {
        try {
            $url = BASE_URL . '/get_user_audio_limit.php?user_id=test_user';
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            if ($response === false) {
                return false;
            }
            
            $data = json_decode($response, true);
            return is_array($data) && isset($data['package_name']) && isset($data['max_audio_length_seconds']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function testDashboardStats() {
        try {
            $stats = getDashboardStats();
            return is_array($stats) && 
                   isset($stats['total_users']) && 
                   isset($stats['total_memories']) && 
                   isset($stats['total_orders']) && 
                   isset($stats['total_revenue']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    // File System Tests
    public function testUploadDirectories() {
        $directories = [
            'uploads',
            'uploads/qr',
            'audio_cache',
            'backups',
            'backups/audio',
            'backups/generated-audio',
            'backups/qr-codes',
            'backups/waveforms'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                return false;
            }
        }
        return true;
    }
    
    public function testBackupDirectories() {
        $backupDirs = [
            'backups',
            'backups/audio',
            'backups/generated-audio',
            'backups/qr-codes',
            'backups/waveforms'
        ];
        
        foreach ($backupDirs as $dir) {
            if (!is_dir($dir)) {
                return false;
            }
        }
        return true;
    }
    
    public function testFilePermissions() {
        $directories = ['uploads', 'audio_cache', 'backups'];
        
        foreach ($directories as $dir) {
            if (!is_writable($dir)) {
                return false;
            }
        }
        return true;
    }
    
    // Configuration Tests
    public function testDatabaseConfig() {
        return !empty(DB_HOST) && !empty(DB_NAME) && !empty(DB_USER);
    }
    
    public function testAPIKeys() {
        return !empty(STRIPE_SECRET_KEY) && !empty(PRINTFUL_API_KEY);
    }
    
    public function testBaseURL() {
        return !empty(BASE_URL) && filter_var(BASE_URL, FILTER_VALIDATE_URL);
    }
    
    // Authentication Tests
    public function testSessionHandling() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['test_key'] = 'test_value';
        return $_SESSION['test_key'] === 'test_value';
    }
    
    public function testAdminCheck() {
        try {
            // This should not throw an exception
            $db = SecureDB::getInstance();
            $result = $db->fetchOne("SELECT COUNT(*) as count FROM admin_users");
            return is_array($result) && isset($result['count']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function printResults() {
        echo "📊 FUNCTIONALITY TEST RESULTS\n";
        echo "=============================\n";
        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        echo "📈 Success Rate: " . round(($this->passed / ($this->passed + $this->failed)) * 100, 2) . "%\n\n";
        
        if ($this->failed == 0) {
            echo "🎉 ALL FUNCTIONALITY TESTS PASSED! Application is working correctly.\n";
        } else {
            echo "⚠️  Some functionality tests failed. Please review the application setup.\n";
        }
    }
}

// Run the functionality test suite
$testSuite = new FunctionalityTestSuite();
$testSuite->runAllTests();
?>
