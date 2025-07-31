<?php
/**
 * Test script to verify the user points fix
 */

require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'models/User.php';

// Start session for testing
session_start();

echo "<h2>Testing User Points Fix</h2>\n";

// Check if we have a session (simulate logged in user)
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ No user session found. Please log in first.</p>\n";
    echo "<p>You can test this by:</p>\n";
    echo "<ol>\n";
    echo "<li>Log in through the normal login process</li>\n";
    echo "<li>Then run this test script</li>\n";
    echo "</ol>\n";
    exit();
}

echo "<h3>1. Testing getCurrentUser() (session data only)</h3>\n";
$current_user_session = getCurrentUser();
if ($current_user_session) {
    echo "<p>✅ Session user data found:</p>\n";
    echo "<ul>\n";
    echo "<li>ID: " . $current_user_session['id'] . "</li>\n";
    echo "<li>Nickname: " . $current_user_session['nickname'] . "</li>\n";
    echo "<li>Role: " . $current_user_session['role'] . "</li>\n";
    echo "<li>Has total_points: " . (isset($current_user_session['total_points']) ? 'Yes (' . $current_user_session['total_points'] . ')' : 'No') . "</li>\n";
    echo "</ul>\n";
} else {
    echo "<p style='color: red;'>❌ getCurrentUser() returned null</p>\n";
    exit();
}

echo "<h3>2. Testing User model getById() (complete database data)</h3>\n";
try {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $current_user = $user->getById($current_user_session['id']);
    
    if ($current_user) {
        echo "<p>✅ Database user data found:</p>\n";
        echo "<ul>\n";
        echo "<li>ID: " . $current_user['id'] . "</li>\n";
        echo "<li>Nickname: " . $current_user['nickname'] . "</li>\n";
        echo "<li>Role: " . $current_user['role'] . "</li>\n";
        echo "<li>Total Points: " . $current_user['total_points'] . "</li>\n";
        echo "<li>Profile Image: " . ($current_user['profile_image'] ?? 'None') . "</li>\n";
        echo "</ul>\n";
        
        echo "<h3>3. Testing the fix</h3>\n";
        echo "<p>✅ The products_catalog.php fix should work because:</p>\n";
        echo "<ul>\n";
        echo "<li>We can access \$current_user['total_points']: " . $current_user['total_points'] . "</li>\n";
        echo "<li>This value can be used for point comparisons and display</li>\n";
        echo "</ul>\n";
        
    } else {
        echo "<p style='color: red;'>❌ User not found in database</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<h3>4. Summary</h3>\n";
echo "<p>The fix in products_catalog.php:</p>\n";
echo "<ol>\n";
echo "<li>Gets session data with getCurrentUser()</li>\n";
echo "<li>Uses the user ID to fetch complete data from database with User->getById()</li>\n";
echo "<li>Now has access to total_points and other database fields</li>\n";
echo "</ol>\n";

?>