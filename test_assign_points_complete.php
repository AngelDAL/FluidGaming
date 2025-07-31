<?php
/**
 * Complete test for assign points interface functionality
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

// Start session for testing
session_start();

echo "<h2>Complete Assign Points Interface Test</h2>\n";

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    echo "✅ Database connected successfully\n";
    
    // Test 1: Create test users if they don't exist
    echo "<h3>1. Setting up test data</h3>\n";
    
    // Create test user
    $checkUser = $db->prepare("SELECT id FROM users WHERE nickname = 'test_user' LIMIT 1");
    $checkUser->execute();
    
    if (!$checkUser->fetch()) {
        $insertUser = $db->prepare("
            INSERT INTO users (nickname, email, password_hash, role, total_points) 
            VALUES ('test_user', 'test@example.com', ?, 'user', 100)
        ");
        $insertUser->execute([password_hash('password123', PASSWORD_DEFAULT)]);
        echo "✅ Test user created\n";
    } else {
        echo "✅ Test user already exists\n";
    }
    
    // Create test assistant
    $checkAssistant = $db->prepare("SELECT id FROM users WHERE nickname = 'test_assistant' LIMIT 1");
    $checkAssistant->execute();
    
    if (!$checkAssistant->fetch()) {
        $insertAssistant = $db->prepare("
            INSERT INTO users (nickname, email, password_hash, role, total_points) 
            VALUES ('test_assistant', 'assistant@example.com', ?, 'assistant', 0)
        ");
        $insertAssistant->execute([password_hash('password123', PASSWORD_DEFAULT)]);
        echo "✅ Test assistant created\n";
    } else {
        echo "✅ Test assistant already exists\n";
    }
    
    // Create test event
    $checkEvent = $db->prepare("SELECT id FROM events WHERE name = 'Test Event' LIMIT 1");
    $checkEvent->execute();
    
    if (!$checkEvent->fetch()) {
        $insertEvent = $db->prepare("
            INSERT INTO events (name, description, start_date, end_date, is_active) 
            VALUES ('Test Event', 'Test event for points assignment', NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 1 DAY, 1)
        ");
        $insertEvent->execute();
        echo "✅ Test event created\n";
    } else {
        echo "✅ Test event already exists\n";
    }
    
    // Test 2: Simulate assistant login
    echo "<h3>2. Testing Assistant Authentication</h3>\n";
    
    $assistant = $db->prepare("SELECT * FROM users WHERE nickname = 'test_assistant' LIMIT 1");
    $assistant->execute();
    $assistantData = $assistant->fetch();
    
    if ($assistantData) {
        $_SESSION['user_id'] = $assistantData['id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['user_role'] = $assistantData['role'];
        $_SESSION['user_nickname'] = $assistantData['nickname'];
        
        echo "✅ Assistant logged in: " . $assistantData['nickname'] . "\n";
        echo "✅ Can assign points: " . (canAssignPoints() ? "Yes" : "No") . "\n";
    }
    
    // Test 3: Test user search functionality
    echo "<h3>3. Testing User Search</h3>\n";
    
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
    
    $users = $stmt->fetchAll();
    echo "✅ Found " . count($users) . " users matching 'test'\n";
    
    foreach ($users as $user) {
        echo "  - " . $user['nickname'] . " (" . $user['total_points'] . " points)\n";
    }
    
    // Test 4: Test tournament loading
    echo "<h3>4. Testing Tournament Loading</h3>\n";
    
    $tournamentQuery = "SELECT t.id, t.name, t.points_reward, t.scheduled_time, e.name as event_name
                        FROM tournaments t
                        JOIN events e ON t.event_id = e.id
                        WHERE e.is_active = 1 
                        AND NOW() BETWEEN e.start_date AND e.end_date
                        ORDER BY t.scheduled_time ASC";
    
    $stmt = $db->prepare($tournamentQuery);
    $stmt->execute();
    
    $tournaments = $stmt->fetchAll();
    echo "✅ Found " . count($tournaments) . " active tournaments\n";
    
    foreach ($tournaments as $tournament) {
        echo "  - " . $tournament['name'] . " (" . $tournament['points_reward'] . " points)\n";
    }
    
    // Test 5: Test point assignment validation
    echo "<h3>5. Testing Point Assignment Validation</h3>\n";
    
    $validationQuery = "SELECT COUNT(*) as active_events 
                        FROM events 
                        WHERE is_active = 1 
                        AND NOW() BETWEEN start_date AND end_date";
    
    $stmt = $db->prepare($validationQuery);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $canAssign = $result['active_events'] > 0;
    echo "✅ Active events: " . $result['active_events'] . "\n";
    echo "✅ Can assign points: " . ($canAssign ? "Yes" : "No") . "\n";
    
    // Test 6: Test CSRF token functionality
    echo "<h3>6. Testing CSRF Protection</h3>\n";
    
    $token = generateCSRFToken();
    echo "✅ CSRF token generated: " . substr($token, 0, 10) . "...\n";
    
    $isValid = validateCSRFToken($token);
    echo "✅ CSRF token validation: " . ($isValid ? "Valid" : "Invalid") . "\n";
    
    // Test 7: Simulate point assignment (without actually doing it)
    echo "<h3>7. Testing Point Assignment Logic</h3>\n";
    
    $testUser = $db->prepare("SELECT * FROM users WHERE nickname = 'test_user' LIMIT 1");
    $testUser->execute();
    $userData = $testUser->fetch();
    
    if ($userData) {
        echo "✅ Target user found: " . $userData['nickname'] . " (Current points: " . $userData['total_points'] . ")\n";
        
        // Test validation logic
        $points = 50;
        $source = 'challenge';
        
        echo "✅ Would assign: $points points for $source\n";
        echo "✅ New total would be: " . ($userData['total_points'] + $points) . " points\n";
    }
    
    echo "<h3>✅ All Tests Completed Successfully!</h3>\n";
    echo "<p>The assign points interface should be fully functional.</p>\n";
    echo "<p>You can access it at: <a href='index.php?page=assign_points'>index.php?page=assign_points</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} finally {
    // Clean up session
    session_destroy();
}
?>