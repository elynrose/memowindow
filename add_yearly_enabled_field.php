<?php
require_once 'config.php';

echo "<h1>Add yearly_enabled field to subscription_packages</h1>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "<p>Connected to database: " . DB_NAME . "</p>";
    
    // Check if yearly_enabled field exists
    $stmt = $pdo->query("SHOW COLUMNS FROM subscription_packages LIKE 'yearly_enabled'");
    $fieldExists = $stmt->fetch();
    
    if (!$fieldExists) {
        echo "<p>Adding yearly_enabled field...</p>";
        
        $pdo->exec("ALTER TABLE subscription_packages ADD COLUMN yearly_enabled TINYINT(1) DEFAULT 1");
        
        echo "<p>✅ yearly_enabled field added successfully!</p>";
    } else {
        echo "<p>✅ yearly_enabled field already exists</p>";
    }
    
    // Show current table structure
    echo "<h2>Current subscription_packages table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE subscription_packages");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(nullable)' : '(not null)') . 
             ($column['Default'] ? " - Default: {$column['Default']}" : '') . "</li>";
    }
    echo "</ul>";
    
    // Show current packages and their yearly_enabled status
    echo "<h2>Current packages:</h2>";
    $stmt = $pdo->query("SELECT id, name, yearly_enabled FROM subscription_packages ORDER BY sort_order");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($packages as $package) {
        $enabled = $package['yearly_enabled'] ? 'Enabled' : 'Disabled';
        echo "<li><strong>{$package['name']}</strong> (ID: {$package['id']}) - Yearly: $enabled</li>";
    }
    echo "</ul>";
    
    echo "<h2>✅ Database update completed!</h2>";
    echo "<p>The yearly_enabled field has been added to control yearly payment options.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
