<?php
/**
 * Test API Performance
 */

require_once 'config.php';

echo "⚡ TESTING API PERFORMANCE\n";
echo "=========================\n\n";

function testAPI($url, $name) {
    echo "🧪 Testing: $name\n";
    
    $times = [];
    $successful = 0;
    
    for ($i = 0; $i < 5; $i++) {
        $startTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $endTime = microtime(true);
        
        $executionTime = ($endTime - $startTime) * 1000;
        $times[] = $executionTime;
        
        if ($response !== false) {
            $successful++;
            echo "  Request " . ($i + 1) . ": " . round($executionTime, 2) . "ms ✅\n";
        } else {
            echo "  Request " . ($i + 1) . ": FAILED ❌\n";
        }
    }
    
    $avgTime = array_sum($times) / count($times);
    $minTime = min($times);
    $maxTime = max($times);
    
    echo "  📊 Average: " . round($avgTime, 2) . "ms\n";
    echo "  📊 Min: " . round($minTime, 2) . "ms\n";
    echo "  📊 Max: " . round($maxTime, 2) . "ms\n";
    echo "  📊 Success Rate: " . round(($successful / 5) * 100, 1) . "%\n\n";
    
    return [
        'name' => $name,
        'avg_time' => $avgTime,
        'min_time' => $minTime,
        'max_time' => $maxTime,
        'success_rate' => ($successful / 5) * 100
    ];
}

// Test original API
$originalResults = testAPI(BASE_URL . '/get_packages.php', 'Original API');

// Test optimized API
$optimizedResults = testAPI(BASE_URL . '/get_packages_optimized.php', 'Optimized API');

echo "📊 PERFORMANCE COMPARISON\n";
echo "=========================\n";
echo sprintf("%-15s %-10s %-10s %-10s %-10s\n", "API", "Avg (ms)", "Min (ms)", "Max (ms)", "Success %");
echo str_repeat("-", 60) . "\n";
echo sprintf("%-15s %-10.2f %-10.2f %-10.2f %-10.1f\n", 
    $originalResults['name'], 
    $originalResults['avg_time'], 
    $originalResults['min_time'], 
    $originalResults['max_time'], 
    $originalResults['success_rate']
);
echo sprintf("%-15s %-10.2f %-10.2f %-10.2f %-10.1f\n", 
    $optimizedResults['name'], 
    $optimizedResults['avg_time'], 
    $optimizedResults['min_time'], 
    $optimizedResults['max_time'], 
    $optimizedResults['success_rate']
);

$improvement = (($originalResults['avg_time'] - $optimizedResults['avg_time']) / $originalResults['avg_time']) * 100;

echo "\n🎯 PERFORMANCE IMPROVEMENT\n";
echo "==========================\n";
if ($improvement > 0) {
    echo "✅ Optimized API is " . round($improvement, 1) . "% faster\n";
} else {
    echo "❌ Optimized API is " . round(abs($improvement), 1) . "% slower\n";
}

echo "\n💡 RECOMMENDATIONS\n";
echo "==================\n";
if ($optimizedResults['avg_time'] < 1000) {
    echo "✅ API performance is acceptable (< 1 second)\n";
} else {
    echo "⚠️  API performance needs improvement (> 1 second)\n";
}

if ($optimizedResults['success_rate'] == 100) {
    echo "✅ API reliability is excellent (100% success rate)\n";
} else {
    echo "⚠️  API reliability needs improvement\n";
}
?>
