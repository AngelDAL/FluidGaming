<?php
/**
 * Test script for leaderboard API endpoints
 */

echo "<h1>Testing Leaderboard API</h1>\n";

// Test API endpoints
$baseUrl = 'http://localhost/FluidGaming/api/leaderboard.php';

// Test 1: Get leaderboard
echo "<h2>Test 1: Get Leaderboard</h2>\n";
$url = $baseUrl . '?action=get&limit=10';
$response = file_get_contents($url);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "<p>✅ API Response successful</p>\n";
        echo "<p>Found " . $data['count'] . " users</p>\n";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>\n";
    } else {
        echo "<p>❌ API Error: " . ($data['error'] ?? 'Unknown error') . "</p>\n";
    }
} else {
    echo "<p>❌ Failed to connect to API</p>\n";
}

// Test 2: Get user rank
echo "<h2>Test 2: Get User Rank</h2>\n";
$url = $baseUrl . '?action=user_rank&user_id=3';
$response = file_get_contents($url);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "<p>✅ User rank API successful</p>\n";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>\n";
    } else {
        echo "<p>❌ API Error: " . ($data['error'] ?? 'Unknown error') . "</p>\n";
    }
} else {
    echo "<p>❌ Failed to connect to API</p>\n";
}

// Test 3: Get user context
echo "<h2>Test 3: Get User Context</h2>\n";
$url = $baseUrl . '?action=user_context&user_id=3&context_size=5';
$response = file_get_contents($url);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "<p>✅ User context API successful</p>\n";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>\n";
    } else {
        echo "<p>❌ API Error: " . ($data['error'] ?? 'Unknown error') . "</p>\n";
    }
} else {
    echo "<p>❌ Failed to connect to API</p>\n";
}

// Test 4: Get statistics
echo "<h2>Test 4: Get Statistics</h2>\n";
$url = $baseUrl . '?action=stats';
$response = file_get_contents($url);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "<p>✅ Statistics API successful</p>\n";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>\n";
    } else {
        echo "<p>❌ API Error: " . ($data['error'] ?? 'Unknown error') . "</p>\n";
    }
} else {
    echo "<p>❌ Failed to connect to API</p>\n";
}

echo "<h2>API Tests Completed</h2>\n";
?>