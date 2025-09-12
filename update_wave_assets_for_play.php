<?php
/**
 * Update wave_assets table to support public play functionality
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "🔄 Updating wave_assets table for public play functionality...\n";
    
    // Add unique_id column if it doesn't exist
    $pdo->exec("
        ALTER TABLE wave_assets 
        ADD COLUMN IF NOT EXISTS unique_id VARCHAR(255) UNIQUE AFTER id
    ");
    echo "✅ Added unique_id column\n";
    
    // Add is_public column if it doesn't exist
    $pdo->exec("
        ALTER TABLE wave_assets 
        ADD COLUMN IF NOT EXISTS is_public BOOLEAN DEFAULT TRUE AFTER unique_id
    ");
    echo "✅ Added is_public column\n";
    
    // Generate unique IDs for existing records that don't have them
    $stmt = $pdo->prepare("SELECT id FROM wave_assets WHERE unique_id IS NULL OR unique_id = ''");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($records)) {
        echo "🔄 Generating unique IDs for " . count($records) . " existing records...\n";
        
        $updateStmt = $pdo->prepare("UPDATE wave_assets SET unique_id = ? WHERE id = ?");
        
        foreach ($records as $record) {
            // Generate a unique ID using the record ID and a random string
            $uniqueId = 'mw_' . $record['id'] . '_' . bin2hex(random_bytes(8));
            $updateStmt->execute([$uniqueId, $record['id']]);
        }
        
        echo "✅ Generated unique IDs for existing records\n";
    }
    
    // Add index for unique_id for better performance
    try {
        $pdo->exec("CREATE INDEX idx_unique_id ON wave_assets(unique_id)");
        echo "✅ Added index for unique_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️ Index for unique_id already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add index for is_public for better performance
    try {
        $pdo->exec("CREATE INDEX idx_is_public ON wave_assets(is_public)");
        echo "✅ Added index for is_public\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️ Index for is_public already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n🎉 Wave assets table updated successfully!\n";
    echo "📋 Summary:\n";
    echo "   - Added unique_id column for public sharing\n";
    echo "   - Added is_public column for privacy control\n";
    echo "   - Generated unique IDs for existing records\n";
    echo "   - Added performance indexes\n";
    echo "\n🔗 Play URLs will now work with format: /play.php?uid=mw_[id]_[random]\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
