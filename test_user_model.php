<?php
/**
 * Test script for User model
 */

require_once 'config/database.php';
require_once 'models/User.php';

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Initialize User model
    $user = new User($db);
    
    echo "=== Testing User Model ===\n";
    
    // Test validation
    echo "\n1. Testing validation with empty data:\n";
    $errors = $user->validateRegistration('', '', '');
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    
    // Test validation with valid data
    echo "\n2. Testing validation with valid data:\n";
    $errors = $user->validateRegistration('testuser', 'test@example.com', 'password123');
    if (empty($errors)) {
        echo "- Validation passed!\n";
    } else {
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }
    
    // Test nickname existence check
    echo "\n3. Testing nickname existence check:\n";
    $exists = $user->nicknameExists('nonexistent');
    echo "- Nickname 'nonexistent' exists: " . ($exists ? 'Yes' : 'No') . "\n";
    
    // Test email existence check
    echo "\n4. Testing email existence check:\n";
    $exists = $user->emailExists('nonexistent@example.com');
    echo "- Email 'nonexistent@example.com' exists: " . ($exists ? 'Yes' : 'No') . "\n";
    
    echo "\n=== User Model Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>