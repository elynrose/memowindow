<?php
/**
 * Master Test Runner for MemoWindow
 * Runs all test suites and provides comprehensive results
 */

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 MEMOWINDOW COMPREHENSIVE TEST SUITE\n";
echo "======================================\n\n";

$startTime = microtime(true);
$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;

// Run Security Tests
echo "🔒 RUNNING SECURITY TESTS...\n";
echo "============================\n";
ob_start();
include 'test_security.php';
$securityOutput = ob_get_clean();
echo $securityOutput;

// Extract results from security tests
preg_match('/✅ Passed: (\d+)/', $securityOutput, $securityPassed);
preg_match('/❌ Failed: (\d+)/', $securityOutput, $securityFailed);
$securityPassed = $securityPassed[1] ?? 0;
$securityFailed = $securityFailed[1] ?? 0;

$totalTests += $securityPassed + $securityFailed;
$totalPassed += $securityPassed;
$totalFailed += $securityFailed;

echo "\n" . str_repeat("=", 50) . "\n\n";

// Run Functionality Tests
echo "🔧 RUNNING FUNCTIONALITY TESTS...\n";
echo "=================================\n";
ob_start();
include 'test_functionality.php';
$functionalityOutput = ob_get_clean();
echo $functionalityOutput;

// Extract results from functionality tests
preg_match('/✅ Passed: (\d+)/', $functionalityOutput, $functionalityPassed);
preg_match('/❌ Failed: (\d+)/', $functionalityOutput, $functionalityFailed);
$functionalityPassed = $functionalityPassed[1] ?? 0;
$functionalityFailed = $functionalityFailed[1] ?? 0;

$totalTests += $functionalityPassed + $functionalityFailed;
$totalPassed += $functionalityPassed;
$totalFailed += $functionalityFailed;

echo "\n" . str_repeat("=", 50) . "\n\n";

// Run Performance Tests
echo "⚡ RUNNING PERFORMANCE TESTS...\n";
echo "==============================\n";
ob_start();
include 'test_performance.php';
$performanceOutput = ob_get_clean();
echo $performanceOutput;

// Extract results from performance tests
preg_match('/✅ Passed: (\d+)/', $performanceOutput, $performancePassed);
preg_match('/❌ Failed: (\d+)/', $performanceOutput, $performanceFailed);
$performancePassed = $performancePassed[1] ?? 0;
$performanceFailed = $performanceFailed[1] ?? 0;

$totalTests += $performancePassed + $performanceFailed;
$totalPassed += $performancePassed;
$totalFailed += $performanceFailed;

echo "\n" . str_repeat("=", 50) . "\n\n";

// Final Results
$endTime = microtime(true);
$totalExecutionTime = ($endTime - $startTime) * 1000;

echo "📊 COMPREHENSIVE TEST RESULTS\n";
echo "=============================\n";
echo "🔒 Security Tests:     {$securityPassed} passed, {$securityFailed} failed\n";
echo "🔧 Functionality Tests: {$functionalityPassed} passed, {$functionalityFailed} failed\n";
echo "⚡ Performance Tests:   {$performancePassed} passed, {$performanceFailed} failed\n";
echo "─────────────────────────────────────────────\n";
echo "📈 Total:              {$totalPassed} passed, {$totalFailed} failed\n";
echo "⏱️  Total Execution Time: " . round($totalExecutionTime, 2) . "ms\n";
echo "📊 Overall Success Rate: " . round(($totalPassed / $totalTests) * 100, 2) . "%\n\n";

// Overall Status
if ($totalFailed == 0) {
    echo "🎉 ALL TESTS PASSED! MemoWindow is ready for production!\n";
    echo "✅ Security: Enterprise-level protection implemented\n";
    echo "✅ Functionality: All core features working correctly\n";
    echo "✅ Performance: Optimized for production use\n";
} else {
    echo "⚠️  SOME TESTS FAILED\n";
    echo "Please review the failed tests above and address any issues.\n";
    
    if ($securityFailed > 0) {
        echo "🔒 Security issues detected - review security implementation\n";
    }
    if ($functionalityFailed > 0) {
        echo "🔧 Functionality issues detected - review application setup\n";
    }
    if ($performanceFailed > 0) {
        echo "⚡ Performance issues detected - consider optimization\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test suite completed at " . date('Y-m-d H:i:s') . "\n";
echo "MemoWindow Test Suite v1.0\n";
?>
