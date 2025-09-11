<?php
/**
 * Enable Voice Clone Feature
 * Simple script to enable the voice clone feature in the database
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Enable voice clone feature
    $stmt = $pdo->prepare("
        INSERT INTO voice_clone_settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    
    $stmt->execute(['voice_clone_enabled', '1']);
    
    echo "✅ Voice clone feature enabled successfully!\n";
    echo "The clone and generate buttons should now be visible on memory cards.\n";
    
} catch (Exception $e) {
    echo "❌ Error enabling voice clone feature: " . $e->getMessage() . "\n";
}
?>
