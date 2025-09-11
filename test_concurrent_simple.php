<?php
/**
 * Simple Concurrent Request Test
 */

require_once 'config.php';

echo "⚡ TESTING CONCURRENT REQUESTS\n";
echo "==============================\n\n";

function makeRequest($url) {
    $startTime = microtime(true);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $endTime = microtime(true);
    
    return [
        'success' => $response !== false,
        'time' => ($endTime - $startTime) * 1000,
        'response' => $response
    ];
}

// Test concurrent requests
$url = BASE_URL . '/get_packages.php';
$concurrentRequests = 5;

echo "🧪 Testing $concurrentRequests concurrent requests to $url\n\n";

$startTime = microtime(true);

// Make requests sequentially (simulating concurrent load)
$results = [];
for ($i = 0; $i < $concurrentRequests; $i++) {
    echo "Making request " . ($i + 1) . "...\n";
    $results[] = makeRequest($url);
}

$endTime = microtime(true);
$totalTime = ($endTime - $startTime) * 1000;

echo "\n📊 RESULTS\n";
echo "==========\n";

$successful = 0;
$totalRequestTime = 0;

foreach ($results as $i => $result) {
    $status = $result['success'] ? '✅' : '❌';
    echo "Request " . ($i + 1) . ": {$result['time']}ms $status\n";
    
    if ($result['success']) {
        $successful++;
        $totalRequestTime += $result['time'];
    }
}

$avgRequestTime = $successful > 0 ? $totalRequestTime / $successful : 0;

echo "\n📈 SUMMARY\n";
echo "==========\n";
echo "Total time: " . round($totalTime, 2) . "ms\n";
echo "Successful requests: $successful/$concurrentRequests\n";
echo "Average request time: " . round($avgRequestTime, 2) . "ms\n";
echo "Success rate: " . round(($successful / $concurrentRequests) * 100, 1) . "%\n";

if ($totalTime < 2000) {
    echo "\n✅ Performance is acceptable (< 2 seconds)\n";
} else {
    echo "\n⚠️  Performance needs improvement (> 2 seconds)\n";
}
?>
