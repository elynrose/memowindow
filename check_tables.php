<?php
// check_tables.php - Check what tables exist in the database
header('Content-Type: text/plain');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "=== CHECKING DATABASE TABLES ===\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\n=== CHECKING FOR MEMORY-RELATED TABLES ===\n";
    
    // Look for tables that might contain memory data
    $memoryTables = array_filter($tables, function($table) {
        return stripos($table, 'memory') !== false || 
               stripos($table, 'waveform') !== false || 
               stripos($table, 'audio') !== false;
    });
    
    if (!empty($memoryTables)) {
        echo "Found potential memory tables:\n";
        foreach ($memoryTables as $table) {
            echo "- $table\n";
            
            // Show structure of each table
            echo "  Structure:\n";
            $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "    {$column['Field']} - {$column['Type']}\n";
            }
            echo "\n";
        }
    } else {
        echo "No obvious memory tables found.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
