<?php
// test_database.php - Test database connection and tables
header('Content-Type: text/plain');

require_once 'config.php';

echo "=== DATABASE CONNECTION TEST ===\n\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Database connection successful!\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n\n";
    
    // Test basic query
    $result = $pdo->query("SELECT 1 as test")->fetch();
    echo "✅ Basic query test: " . $result['test'] . "\n\n";
    
    // Check if admin_users table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'admin_users'")->fetchAll();
    
    if (empty($tables)) {
        echo "❌ admin_users table does not exist\n";
        echo "Run setup_admin_user.php to create it\n\n";
    } else {
        echo "✅ admin_users table exists\n";
        
        // Check if you're in the admin_users table
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE firebase_uid = ?");
        $stmt->execute(['FG8w39qVEySCnzotJDYBWQ30g5J2']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "✅ You are in admin_users table\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Name: " . $admin['name'] . "\n";
            echo "Is Admin: " . ($admin['is_admin'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "❌ You are NOT in admin_users table\n";
            echo "Run setup_admin_user.php to add yourself\n";
        }
    }
    
    // Check other important tables
    echo "\n=== CHECKING OTHER TABLES ===\n";
    $importantTables = ['orders', 'wave_assets', 'print_products'];
    
    foreach ($importantTables as $table) {
        $tables = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
        if (empty($tables)) {
            echo "❌ $table table does not exist\n";
        } else {
            echo "✅ $table table exists\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "Check your database credentials in config.php\n";
}
?>
