<?php
/**
 * Setup voice clones database table
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Create voice_clones table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS voice_clones (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            source_memory_id INT NOT NULL,
            voice_id VARCHAR(255) NOT NULL,
            voice_name VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive', 'error') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_voice_id (voice_id),
            INDEX idx_source_memory (source_memory_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Create generated_audio table for storing generated speech
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS generated_audio (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(255) NOT NULL,
            voice_clone_id INT NOT NULL,
            text_content TEXT NOT NULL,
            audio_url VARCHAR(1024) NOT NULL,
            memory_title VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_voice_clone (voice_clone_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✅ Voice clones tables created successfully!\n";
    echo "Tables created:\n";
    echo "- voice_clones: Stores cloned voice information\n";
    echo "- generated_audio: Stores generated speech audio\n";
    
} catch (Exception $e) {
    echo "❌ Error creating tables: " . $e->getMessage() . "\n";
}
?>
