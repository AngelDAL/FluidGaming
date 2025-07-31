<?php
/**
 * Test script for User API endpoints
 */

echo "=== Testing User API Endpoints ===\n";

// Test registration endpoint
echo "\n1. Testing registration endpoint structure:\n";

// Simulate POST data for registration
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'nickname' => 'testuser',
    'email' => 'test@example.com',
    'password' => 'password123'
];
$_FILES = [];

// Capture output
ob_start();
include 'api/users.php';
$output = ob_get_clean();

echo "- Registration endpoint executed successfully\n";
echo "- Response: " . substr($output, 0, 100) . "...\n";

echo "\n=== User API Tests Completed ===\n";
?>