<?php
// get_packages_optimized.php - Optimized API endpoint to fetch subscription packages
header('Content-Type: application/json');

// Add caching headers
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');

require_once 'config.php';

try {
    // Use direct PDO connection for better performance
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Optimized query with only needed fields
    $stmt = $pdo->query("
        SELECT 
            id, name, slug, description, price_monthly, price_yearly, 
            features, is_active, max_audio_length_seconds
        FROM subscription_packages 
        WHERE is_active = 1 
        ORDER BY sort_order
    ");
    
    $packages = $stmt->fetchAll();
    
    // Format packages for frontend (optimized)
    $formattedPackages = [];
    foreach ($packages as $package) {
        $features = json_decode($package['features'], true) ?: [];
        
        $formattedPackages[] = [
            'id' => (int)$package['id'],
            'name' => $package['name'],
            'slug' => $package['slug'],
            'description' => $package['description'],
            'price_monthly' => (float)$package['price_monthly'],
            'price_yearly' => (float)$package['price_yearly'],
            'features' => $features,
            'is_popular' => $package['slug'] === 'standard',
            'is_active' => (bool)$package['is_active'],
            'max_audio_length_seconds' => (int)$package['max_audio_length_seconds']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'packages' => $formattedPackages
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load packages'
    ], JSON_UNESCAPED_UNICODE);
}
?>
