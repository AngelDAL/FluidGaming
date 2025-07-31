<?php
/**
 * Database reset script
 * WARNING: This will drop all tables and recreate them
 */

require_once '../config/config.php';

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'tournament_points';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n";
    
    // Drop database if exists and recreate
    $pdo->exec("DROP DATABASE IF EXISTS $database");
    echo "Dropped existing database (if it existed).\n";
    
    $pdo->exec("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Created new database: $database\n";
    
    // Now run the setup script
    include 'setup.php';
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>