<?php
/**
 * Database setup script
 * Run this script to create the database and tables
 */

require_once '../config/config.php';

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server (without specifying database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n";
    
    // Read and execute the schema file
    $schema = file_get_contents('schema.sql');
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\nDatabase setup completed successfully!\n";
    echo "Default admin user created:\n";
    echo "Email: admin@tournament.com\n";
    echo "Password: admin123\n";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>