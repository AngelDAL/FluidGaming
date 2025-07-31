<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sistema_puntos_test;charset=utf8', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "Database connection successful\n";
    
    // Try to create a simple test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(50))");
    echo "Test table creation successful\n";
    
    // Clean up
    $pdo->exec("DROP TABLE IF EXISTS test_table");
    echo "Test table cleanup successful\n";
    
} catch (Exception $e) {
    echo "Database test failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>