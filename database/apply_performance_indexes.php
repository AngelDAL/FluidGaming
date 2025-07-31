<?php
/**
 * Script to apply performance optimization indexes
 * Task 13.1: Add database indexes for frequent queries
 */

require_once __DIR__ . '/../config/environment.php';

try {
    $db = getDatabaseConnection();
    
    echo "Applying performance optimization indexes...\n";
    
    // Read and execute the performance indexes SQL
    $sql = file_get_contents(__DIR__ . '/performance_indexes.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $db->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Check if it's a "duplicate key" error (index already exists)
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠ Index already exists: " . substr($statement, 0, 50) . "...\n";
            } else {
                echo "✗ Error executing: " . substr($statement, 0, 50) . "...\n";
                echo "  Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nPerformance indexes application completed!\n";
    
    // Show index information
    echo "\nCurrent indexes on main tables:\n";
    $tables = ['users', 'events', 'tournaments', 'point_transactions', 'claims', 'products', 'stands', 'notifications'];
    
    foreach ($tables as $table) {
        echo "\n$table:\n";
        $stmt = $db->prepare("SHOW INDEX FROM $table");
        $stmt->execute();
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($indexes as $index) {
            echo "  - {$index['Key_name']} on {$index['Column_name']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>