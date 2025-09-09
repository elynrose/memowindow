<?php
// fix_admin_table.php - Fix admin_users table structure
header('Content-Type: text/plain');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== FIXING ADMIN_USERS TABLE ===\n\n";
    
    // Check current table structure
    echo "Current table structure:\n";
    $columns = $pdo->query("DESCRIBE admin_users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n=== ADDING MISSING COLUMNS ===\n";
    
    // Add firebase_uid column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN firebase_uid VARCHAR(255) UNIQUE");
        echo "✅ Added firebase_uid column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ firebase_uid column already exists\n";
        } else {
            echo "❌ Error adding firebase_uid: " . $e->getMessage() . "\n";
        }
    }
    
    // Add email column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN email VARCHAR(255)");
        echo "✅ Added email column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ email column already exists\n";
        } else {
            echo "❌ Error adding email: " . $e->getMessage() . "\n";
        }
    }
    
    // Add name column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN name VARCHAR(255)");
        echo "✅ Added name column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ name column already exists\n";
        } else {
            echo "❌ Error adding name: " . $e->getMessage() . "\n";
        }
    }
    
    // Add is_admin column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
        echo "✅ Added is_admin column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ is_admin column already exists\n";
        } else {
            echo "❌ Error adding is_admin: " . $e->getMessage() . "\n";
        }
    }
    
    // Add permissions column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN permissions JSON DEFAULT NULL");
        echo "✅ Added permissions column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ permissions column already exists\n";
        } else {
            echo "❌ Error adding permissions: " . $e->getMessage() . "\n";
        }
    }
    
    // Add created_at column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Added created_at column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ created_at column already exists\n";
        } else {
            echo "❌ Error adding created_at: " . $e->getMessage() . "\n";
        }
    }
    
    // Add last_login column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN last_login TIMESTAMP NULL");
        echo "✅ Added last_login column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ last_login column already exists\n";
        } else {
            echo "❌ Error adding last_login: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== ADDING INDEXES ===\n";
    
    // Add indexes if they don't exist
    try {
        $pdo->exec("ALTER TABLE admin_users ADD INDEX idx_firebase_uid (firebase_uid)");
        echo "✅ Added firebase_uid index\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ firebase_uid index already exists\n";
        } else {
            echo "❌ Error adding firebase_uid index: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE admin_users ADD INDEX idx_is_admin (is_admin)");
        echo "✅ Added is_admin index\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ is_admin index already exists\n";
        } else {
            echo "❌ Error adding is_admin index: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== FINAL TABLE STRUCTURE ===\n";
    $columns = $pdo->query("DESCRIBE admin_users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
    echo "\n✅ Admin users table structure fixed!\n";
    echo "Now run setup_admin_user.php to add yourself as admin.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
