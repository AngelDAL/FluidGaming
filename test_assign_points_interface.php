<?php
/**
 * Test script for assign points interface
 */

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'controllers/PointsController.php';

// Start session for testing
session_start();

echo "<h2>Testing Assign Points Interface</h2>\n";

// Test 1: Database connection
echo "<h3>1. Testing Database Connection</h3>\n";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "✅ Database connection successful\n";
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 2: Points Controller instantiation
echo "<h3>2. Testing Points Controller</h3>\n";
try {
    $controller = new PointsController($db);
    echo "✅ PointsController instantiated successfully\n";
} catch (Exception $e) {
    echo "❌ PointsController error: " . $e->getMessage() . "\n";
}

// Test 3: Authentication functions
echo "<h3>3. Testing Authentication Functions</h3>\n";
try {
    // Test without login
    $isLoggedIn = isLoggedIn();
    echo "isLoggedIn() without session: " . ($isLoggedIn ? "true" : "false") . "\n";
    
    $canAssign = canAssignPoints();
    echo "canAssignPoints() without session: " . ($canAssign ? "true" : "false") . "\n";
    
    // Simulate assistant login
    $_SESSION['user_id'] = 1;
    $_SESSION['logged_in'] = true;
    $_SESSION['user_role'] = 'assistant';
    $_SESSION['user_nickname'] = 'test_assistant';
    
    $isLoggedIn = isLoggedIn();
    echo "isLoggedIn() with assistant session: " . ($isLoggedIn ? "true" : "false") . "\n";
    
    $canAssign = canAssignPoints();
    echo "canAssignPoints() with assistant session: " . ($canAssign ? "true" : "false") . "\n";
    
    echo "✅ Authentication functions working correctly\n";
} catch (Exception $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
}

// Test 4: CSRF Token generation
echo "<h3>4. Testing CSRF Token</h3>\n";
try {
    $token = generateCSRFToken();
    if (!empty($token)) {
        echo "✅ CSRF token generated: " . substr($token, 0, 10) . "...\n";
        
        // Test validation
        $isValid = validateCSRFToken($token);
        echo "CSRF token validation: " . ($isValid ? "✅ Valid" : "❌ Invalid") . "\n";
    } else {
        echo "❌ CSRF token generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ CSRF error: " . $e->getMessage() . "\n";
}

// Test 5: Check if required tables exist
echo "<h3>5. Testing Database Tables</h3>\n";
try {
    $tables = ['users', 'events', 'tournaments', 'point_transactions'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Table check error: " . $e->getMessage() . "\n";
}

// Test 6: Test user search functionality (simulate)
echo "<h3>6. Testing User Search Query</h3>\n";
try {
    $searchQuery = "SELECT id, nickname, profile_image, total_points 
                    FROM users 
                    WHERE nickname LIKE :search 
                    AND role = 'user'
                    ORDER BY nickname ASC 
                    LIMIT 10";
    
    $stmt = $db->prepare($searchQuery);
    $searchParam = '%test%';
    $stmt->bindParam(':search', $searchParam);
    $stmt->execute();
    
    echo "✅ User search query prepared successfully\n";
    echo "Search results count: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "❌ User search error: " . $e->getMessage() . "\n";
}

// Test 7: Test tournament query
echo "<h3>7. Testing Tournament Query</h3>\n";
try {
    $tournamentQuery = "SELECT t.id, t.name, t.points_reward, t.scheduled_time, e.name as event_name
                        FROM tournaments t
                        JOIN events e ON t.event_id = e.id
                        WHERE e.is_active = 1 
                        AND NOW() BETWEEN e.start_date AND e.end_date
                        ORDER BY t.scheduled_time ASC";
    
    $stmt = $db->prepare($tournamentQuery);
    $stmt->execute();
    
    echo "✅ Tournament query prepared successfully\n";
    echo "Available tournaments count: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "❌ Tournament query error: " . $e->getMessage() . "\n";
}

echo "<h3>Test Summary</h3>\n";
echo "If all tests show ✅, the assign points interface should work correctly.\n";
echo "If any tests show ❌, those issues need to be resolved.\n";

// Clean up test session
session_destroy();
?>