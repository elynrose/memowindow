<?php
// check_table_structure.php - Check what columns exist in print_products table
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Database Table Structure Check</h1>";

try {
    require_once 'config.php';
    
    // Test database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<p>✅ Database connected successfully</p>";
    
    // Check if print_products table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'print_products'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p>✅ Print products table exists</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE print_products");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Current Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show sample data
        $stmt = $pdo->query("SELECT * FROM print_products LIMIT 3");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Data:</h3>";
        if (empty($products)) {
            echo "<p>❌ No products found in table</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            $first = true;
            foreach ($products as $product) {
                if ($first) {
                    echo "<tr>";
                    foreach (array_keys($product) as $key) {
                        echo "<th>{$key}</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($product as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p>❌ Print products table does not exist</p>";
        
        // Show what tables do exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<h3>Available Tables:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
