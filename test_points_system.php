<?php
/**
 * Test script for Points System
 */

require_once 'config/database.php';
require_once 'models/PointTransaction.php';
require_once 'services/PointsService.php';

echo "<h2>Testing Points System</h2>\n";

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    echo "<p>✓ Database connection successful</p>\n";
    
    // Test PointTransaction model
    $pointTransaction = new PointTransaction($db);
    echo "<p>✓ PointTransaction model instantiated</p>\n";
    
    // Test PointsService
    $pointsService = new PointsService($db);
    echo "<p>✓ PointsService instantiated</p>\n";
    
    // Test validation methods
    echo "<h3>Testing Validation Methods</h3>\n";
    
    // Test active event validation
    $eventValidation = $pointTransaction->validateActiveEvent();
    echo "<p>Active event validation: " . ($eventValidation['valid'] ? 'Valid' : $eventValidation['error']) . "</p>\n";
    
    // Test user search (if users exist)
    $query = "SELECT id, nickname FROM users WHERE role = 'user' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        echo "<p>✓ Found test user: {$testUser['nickname']} (ID: {$testUser['id']})</p>\n";
        
        // Test user points stats
        $stats = $pointTransaction->getUserPointsStats($testUser['id']);
        echo "<p>User stats - Total earned: {$stats['total_earned']}, Transactions: {$stats['total_transactions']}</p>\n";
        
        // Test user rank
        $rank = $pointsService->getUserRank($testUser['id']);
        echo "<p>User rank: " . ($rank ? $rank : 'Not ranked') . "</p>\n";
    } else {
        echo "<p>⚠ No test users found</p>\n";
    }
    
    // Test leaderboard calculation
    $leaderboard = $pointsService->calculateLeaderboard(5);
    echo "<p>✓ Leaderboard calculated with " . count($leaderboard) . " users</p>\n";
    
    // Test points statistics
    $pointsStats = $pointsService->getPointsStatistics();
    echo "<h3>Points Statistics</h3>\n";
    echo "<ul>\n";
    echo "<li>Points distributed today: {$pointsStats['points_today']}</li>\n";
    echo "<li>Active users: {$pointsStats['active_users']}</li>\n";
    echo "<li>Average points per user: {$pointsStats['average_points']}</li>\n";
    echo "<li>Top point source: {$pointsStats['top_source']}</li>\n";
    echo "</ul>\n";
    
    echo "<h3>✅ All tests completed successfully!</h3>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>