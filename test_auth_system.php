<?php
/**
 * Test script for authentication and authorization system
 */

require_once 'includes/auth.php';

echo "=== Testing Authentication and Authorization System ===\n";

// Start session for testing
session_start();

// Test 1: Check initial state (not logged in)
echo "\n1. Testing initial state (not logged in):\n";
echo "- Is logged in: " . (isLoggedIn() ? 'Yes' : 'No') . "\n";
echo "- Has user role: " . (hasRole('user') ? 'Yes' : 'No') . "\n";
echo "- Has admin role: " . (hasRole('admin') ? 'Yes' : 'No') . "\n";

// Test 2: Simulate login
echo "\n2. Simulating user login:\n";
$_SESSION['user_id'] = 1;
$_SESSION['user_nickname'] = 'testuser';
$_SESSION['user_role'] = 'user';
$_SESSION['logged_in'] = true;
$_SESSION['last_activity'] = time();
$_SESSION['created'] = time();

echo "- Is logged in: " . (isLoggedIn() ? 'Yes' : 'No') . "\n";
echo "- Has user role: " . (hasRole('user') ? 'Yes' : 'No') . "\n";
echo "- Has assistant role: " . (hasRole('assistant') ? 'Yes' : 'No') . "\n";
echo "- Has admin role: " . (hasRole('admin') ? 'Yes' : 'No') . "\n";

// Test 3: Test role hierarchy
echo "\n3. Testing role hierarchy:\n";
$roles = ['user', 'assistant', 'stand_manager', 'admin'];

foreach ($roles as $role) {
    $_SESSION['user_role'] = $role;
    echo "- As {$role}:\n";
    echo "  - Can assign points: " . (canAssignPoints() ? 'Yes' : 'No') . "\n";
    echo "  - Can manage stands: " . (canManageStands() ? 'Yes' : 'No') . "\n";
    echo "  - Is admin: " . (isAdmin() ? 'Yes' : 'No') . "\n";
}

// Test 4: Test session validation
echo "\n4. Testing session validation:\n";
$_SESSION['user_role'] = 'user';
echo "- Session valid: " . (validateSession() ? 'Yes' : 'No') . "\n";

// Test 5: Test CSRF token generation
echo "\n5. Testing CSRF token:\n";
$token = generateCSRFToken();
echo "- Token generated: " . (!empty($token) ? 'Yes' : 'No') . "\n";
echo "- Token valid: " . (validateCSRFToken($token) ? 'Yes' : 'No') . "\n";

// Test 6: Test login attempts
echo "\n6. Testing login attempts:\n";
$email = 'test@example.com';
echo "- Can attempt login: " . (checkLoginAttempts($email) ? 'Yes' : 'No') . "\n";

// Simulate failed attempts
for ($i = 0; $i < 6; $i++) {
    recordLoginAttempt($email, false);
}

echo "- Can attempt after 6 failures: " . (checkLoginAttempts($email) ? 'Yes' : 'No') . "\n";

echo "\n=== Authentication System Tests Completed ===\n";
?>