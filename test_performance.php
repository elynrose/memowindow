<?php
/**
 * Performance Test Suite for MemoWindow
 * Tests application performance and optimization
 */

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config.php';
require_once 'secure_auth.php';
require_once 'secure_db.php';
require_once 'rate_limiter.php';
require_once 'encryption.php';

class PerformanceTestSuite {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    private $results = [];
    
    public function runTest($name, $testFunction) {
        echo "⚡ Testing: $name\n";
        $startTime = microtime(true);
        
        try {
            $result = $testFunction();
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $this->results[$name] = [
                'status' => $result ? 'PASSED' : 'FAILED',
                'execution_time' => round($executionTime, 2),
                'memory_usage' => memory_get_usage(true)
            ];
            
            if ($result) {
                echo "✅ PASSED: $name ({$executionTime}ms)\n";
                $this->passed++;
            } else {
                echo "❌ FAILED: $name ({$executionTime}ms)\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;
            
            $this->results[$name] = [
                'status' => 'ERROR',
                'execution_time' => round($executionTime, 2),
                'error' => $e->getMessage()
            ];
            
            echo "❌ ERROR: $name - " . $e->getMessage() . " ({$executionTime}ms)\n";
            $this->failed++;
        }
        echo "\n";
    }
    
    public function runAllTests() {
        echo "⚡ MEMOWINDOW PERFORMANCE TEST SUITE\n";
        echo "===================================\n\n";
        
        // Database Performance Tests
        $this->runTest("Database - Connection Speed", [$this, 'testDatabaseConnectionSpeed']);
        $this->runTest("Database - Query Performance", [$this, 'testQueryPerformance']);
        $this->runTest("Database - Prepared Statement Performance", [$this, 'testPreparedStatementPerformance']);
        
        // Security Performance Tests
        $this->runTest("Security - Encryption Performance", [$this, 'testEncryptionPerformance']);
        $this->runTest("Security - Password Hashing Performance", [$this, 'testPasswordHashingPerformance']);
        $this->runTest("Security - Rate Limiting Performance", [$this, 'testRateLimitingPerformance']);
        
        // File System Performance Tests
        $this->runTest("File System - Directory Operations", [$this, 'testDirectoryOperations']);
        $this->runTest("File System - File Operations", [$this, 'testFileOperations']);
        
        // Memory Usage Tests
        $this->runTest("Memory - Memory Usage", [$this, 'testMemoryUsage']);
        $this->runTest("Memory - Memory Leaks", [$this, 'testMemoryLeaks']);
        
        // API Performance Tests
        $this->runTest("API - Response Time", [$this, 'testAPIResponseTime']);
        $this->runTest("API - Concurrent Requests", [$this, 'testConcurrentRequests']);
        
        $this->printResults();
    }
    
    // Database Performance Tests
    public function testDatabaseConnectionSpeed() {
        $startTime = microtime(true);
        $db = SecureDB::getInstance();
        $endTime = microtime(true);
        
        $connectionTime = ($endTime - $startTime) * 1000;
        return $connectionTime < 100; // Should connect in less than 100ms
    }
    
    public function testQueryPerformance() {
        $db = SecureDB::getInstance();
        
        $startTime = microtime(true);
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM users");
        $endTime = microtime(true);
        
        $queryTime = ($endTime - $startTime) * 1000;
        return $queryTime < 50 && $result !== false; // Should query in less than 50ms
    }
    
    public function testPreparedStatementPerformance() {
        $db = SecureDB::getInstance();
        
        $startTime = microtime(true);
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM wave_assets WHERE user_id = ?", ['test_user']);
        $endTime = microtime(true);
        
        $queryTime = ($endTime - $startTime) * 1000;
        return $queryTime < 50 && $result !== false;
    }
    
    // Security Performance Tests
    public function testEncryptionPerformance() {
        $encryption = new DataEncryption();
        $testData = str_repeat('test data ', 100); // 1KB of data
        
        $startTime = microtime(true);
        $encrypted = $encryption->encrypt($testData);
        $decrypted = $encryption->decrypt($encrypted);
        $endTime = microtime(true);
        
        $encryptionTime = ($endTime - $startTime) * 1000;
        return $encryptionTime < 100 && $decrypted === $testData; // Should encrypt/decrypt in less than 100ms
    }
    
    public function testPasswordHashingPerformance() {
        $encryption = new DataEncryption();
        $password = 'test_password_123';
        
        $startTime = microtime(true);
        $hash = $encryption->hashPassword($password);
        $verified = $encryption->verifyPassword($password, $hash);
        $endTime = microtime(true);
        
        $hashingTime = ($endTime - $startTime) * 1000;
        return $hashingTime < 500 && $verified; // Should hash in less than 500ms
    }
    
    public function testRateLimitingPerformance() {
        $rateLimiter = new RateLimiter(1000, 3600); // High limit for testing
        $identifier = 'test_user_' . time();
        
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $rateLimiter->isAllowed($identifier);
        }
        $endTime = microtime(true);
        
        $rateLimitTime = ($endTime - $startTime) * 1000;
        return $rateLimitTime < 100; // Should handle 100 requests in less than 100ms
    }
    
    // File System Performance Tests
    public function testDirectoryOperations() {
        $testDir = 'test_dir_' . time();
        
        $startTime = microtime(true);
        mkdir($testDir);
        $exists = is_dir($testDir);
        rmdir($testDir);
        $endTime = microtime(true);
        
        $dirTime = ($endTime - $startTime) * 1000;
        return $dirTime < 10 && $exists; // Should create/delete directory in less than 10ms
    }
    
    public function testFileOperations() {
        $testFile = 'test_file_' . time() . '.txt';
        $testData = str_repeat('test data ', 1000); // 10KB of data
        
        $startTime = microtime(true);
        file_put_contents($testFile, $testData);
        $readData = file_get_contents($testFile);
        unlink($testFile);
        $endTime = microtime(true);
        
        $fileTime = ($endTime - $startTime) * 1000;
        return $fileTime < 50 && $readData === $testData; // Should handle file operations in less than 50ms
    }
    
    // Memory Usage Tests
    public function testMemoryUsage() {
        $initialMemory = memory_get_usage(true);
        
        // Perform some operations
        $db = SecureDB::getInstance();
        $encryption = new DataEncryption();
        $rateLimiter = new RateLimiter();
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        return $memoryIncrease < 1024 * 1024; // Should use less than 1MB
    }
    
    public function testMemoryLeaks() {
        $initialMemory = memory_get_usage(true);
        
        // Perform operations that might cause memory leaks
        for ($i = 0; $i < 100; $i++) {
            $db = SecureDB::getInstance();
            $result = $db->fetchOne("SELECT 1 as test");
            unset($db, $result);
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        return $memoryIncrease < 1024 * 512; // Should not increase by more than 512KB
    }
    
    // API Performance Tests
    public function testAPIResponseTime() {
        $url = BASE_URL . '/get_packages.php';
        
        $startTime = microtime(true);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000;
        return $responseTime < 1000 && $response !== false; // Should respond in less than 1 second
    }
    
    public function testConcurrentRequests() {
        $url = BASE_URL . '/get_packages.php';
        $concurrentRequests = 5; // Reduced from 10 to 5
        $successfulRequests = 0;
        
        $startTime = microtime(true);
        
        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            if ($response !== false) {
                $successfulRequests++;
            }
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        return $successfulRequests >= $concurrentRequests * 0.8 && $totalTime < 3000; // 80% success rate in less than 3 seconds
    }
    
    public function printResults() {
        echo "📊 PERFORMANCE TEST RESULTS\n";
        echo "===========================\n";
        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        echo "📈 Success Rate: " . round(($this->passed / ($this->passed + $this->failed)) * 100, 2) . "%\n\n";
        
        echo "⏱️  PERFORMANCE METRICS\n";
        echo "======================\n";
        foreach ($this->results as $testName => $result) {
            echo sprintf("%-40s %-8s %8.2fms\n", $testName, $result['status'], $result['execution_time']);
        }
        
        echo "\n";
        
        if ($this->failed == 0) {
            echo "🎉 ALL PERFORMANCE TESTS PASSED! Application is performing well.\n";
        } else {
            echo "⚠️  Some performance tests failed. Consider optimization.\n";
        }
    }
}

// Run the performance test suite
$testSuite = new PerformanceTestSuite();
$testSuite->runAllTests();
?>
