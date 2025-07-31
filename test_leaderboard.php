<?php
/**
 * Test script for leaderboard functionality
 */

require_once 'config/database.php';
require_once 'services/LeaderboardService.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize leaderboard service
$leaderboardService = new LeaderboardService($db);

echo "<h1>Testing Leaderboard Service</h1>\n";

// Test 1: Get leaderboard
echo "<h2>Test 1: Get Leaderboard</h2>\n";
try {
    $leaderboard = $leaderboardService->getLeaderboard(10);
    echo "<p>✅ Leaderboard loaded successfully</p>\n";
    echo "<p>Found " . count($leaderboard) . " users in leaderboard</p>\n";
    
    if (!empty($leaderboard)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
        echo "<tr><th>Rank</th><th>User ID</th><th>Nickname</th><th>Points</th><th>First Point Date</th></tr>\n";
        foreach (array_slice($leaderboard, 0, 5) as $entry) {
            echo "<tr>";
            echo "<td>" . $entry['rank'] . "</td>";
            echo "<td>" . $entry['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($entry['nickname']) . "</td>";
            echo "<td>" . number_format($entry['total_points']) . "</td>";
            echo "<td>" . ($entry['first_point_date'] ?? 'N/A') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 2: Get user rank
echo "<h2>Test 2: Get User Rank</h2>\n";
try {
    // Get first user from leaderboard to test
    $leaderboard = $leaderboardService->getLeaderboard(5);
    if (!empty($leaderboard)) {
        $testUserId = $leaderboard[0]['user_id'];
        $rank = $leaderboardService->getUserRank($testUserId);
        echo "<p>✅ User rank retrieved successfully</p>\n";
        echo "<p>User ID {$testUserId} has rank: " . ($rank ?? 'Not found') . "</p>\n";
    } else {
        echo "<p>⚠️ No users in leaderboard to test rank</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Get user context
echo "<h2>Test 3: Get User Context</h2>\n";
try {
    if (!empty($leaderboard)) {
        $testUserId = $leaderboard[0]['user_id'];
        $context = $leaderboardService->getUserLeaderboardContext($testUserId, 3);
        echo "<p>✅ User context retrieved successfully</p>\n";
        
        if ($context) {
            echo "<p>User rank: " . $context['user_rank'] . "</p>\n";
            echo "<p>Context entries: " . count($context['context']) . "</p>\n";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
            echo "<tr><th>Rank</th><th>Nickname</th><th>Points</th><th>Is Current User</th></tr>\n";
            foreach ($context['context'] as $entry) {
                echo "<tr>";
                echo "<td>" . $entry['rank'] . "</td>";
                echo "<td>" . htmlspecialchars($entry['nickname']) . "</td>";
                echo "<td>" . number_format($entry['total_points']) . "</td>";
                echo "<td>" . ($entry['is_current_user'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 4: Get leaderboard statistics
echo "<h2>Test 4: Get Leaderboard Statistics</h2>\n";
try {
    $stats = $leaderboardService->getLeaderboardStats();
    echo "<p>✅ Statistics retrieved successfully</p>\n";
    echo "<ul>\n";
    echo "<li>Total Users: " . $stats['total_users'] . "</li>\n";
    echo "<li>Highest Points: " . number_format($stats['highest_points']) . "</li>\n";
    echo "<li>Average Points: " . $stats['average_points'] . "</li>\n";
    echo "<li>Lowest Points: " . number_format($stats['lowest_points']) . "</li>\n";
    echo "</ul>\n";
    
    if (!empty($stats['top_users'])) {
        echo "<p><strong>Top 3 Users:</strong></p>\n";
        echo "<ol>\n";
        foreach ($stats['top_users'] as $user) {
            echo "<li>" . htmlspecialchars($user['nickname']) . " - " . number_format($user['total_points']) . " points</li>\n";
        }
        echo "</ol>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 5: Cache functionality
echo "<h2>Test 5: Cache Functionality</h2>\n";
try {
    // Clear cache first
    $leaderboardService->clearCache();
    echo "<p>✅ Cache cleared</p>\n";
    
    // Load leaderboard (should create cache)
    $start = microtime(true);
    $leaderboard1 = $leaderboardService->getLeaderboard(10);
    $time1 = microtime(true) - $start;
    echo "<p>✅ First load (no cache): " . round($time1 * 1000, 2) . "ms</p>\n";
    
    // Load again (should use cache)
    $start = microtime(true);
    $leaderboard2 = $leaderboardService->getLeaderboard(10);
    $time2 = microtime(true) - $start;
    echo "<p>✅ Second load (with cache): " . round($time2 * 1000, 2) . "ms</p>\n";
    
    if ($time2 < $time1) {
        echo "<p>✅ Cache is working - second load was faster</p>\n";
    } else {
        echo "<p>⚠️ Cache might not be working optimally</p>\n";
    }
    
    // Verify data consistency
    if (count($leaderboard1) === count($leaderboard2)) {
        echo "<p>✅ Cached data is consistent</p>\n";
    } else {
        echo "<p>❌ Cached data inconsistency detected</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 6: Period leaderboard
echo "<h2>Test 6: Period Leaderboard</h2>\n";
try {
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
    
    $periodLeaderboard = $leaderboardService->getLeaderboardForPeriod($startDate, $endDate, 5);
    echo "<p>✅ Period leaderboard retrieved successfully</p>\n";
    echo "<p>Period: {$startDate} to {$endDate}</p>\n";
    echo "<p>Found " . count($periodLeaderboard) . " users with points in this period</p>\n";
    
    if (!empty($periodLeaderboard)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
        echo "<tr><th>Rank</th><th>Nickname</th><th>Period Points</th></tr>\n";
        foreach ($periodLeaderboard as $entry) {
            echo "<tr>";
            echo "<td>" . $entry['rank'] . "</td>";
            echo "<td>" . htmlspecialchars($entry['nickname']) . "</td>";
            echo "<td>" . number_format($entry['period_points']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>All Tests Completed</h2>\n";
echo "<p>Check the results above to verify leaderboard functionality.</p>\n";
?>