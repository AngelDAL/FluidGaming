<?php
/**
 * Direct test of leaderboard API functionality
 */

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get';
$_GET['limit'] = '10';

echo "<h1>Testing Leaderboard API Direct</h1>\n";

// Capture output
ob_start();

try {
    include 'api/leaderboard.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "<h2>API Response:</h2>\n";
    echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
    
    // Try to decode JSON
    $data = json_decode($output, true);
    if ($data) {
        echo "<h2>Parsed Response:</h2>\n";
        echo "<p>Success: " . ($data['success'] ? 'Yes' : 'No') . "</p>\n";
        if (isset($data['data'])) {
            echo "<p>Data count: " . count($data['data']) . "</p>\n";
        }
        if (isset($data['error'])) {
            echo "<p>Error: " . $data['error'] . "</p>\n";
        }
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test user rank
echo "<h2>Testing User Rank API</h2>\n";
$_GET['action'] = 'user_rank';
$_GET['user_id'] = '3';

ob_start();
try {
    include 'api/leaderboard.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "<h3>User Rank API Response:</h3>\n";
    echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Direct API Tests Completed</h2>\n";
?>