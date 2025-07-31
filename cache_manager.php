<?php
/**
 * Cache management script for performance optimization
 * Task 13.1: Cache management utilities
 */

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/services/CacheService.php';
require_once __DIR__ . '/services/LeaderboardService.php';
require_once __DIR__ . '/services/ReportsService.php';

function showUsage() {
    echo "Cache Manager - Tournament Points System\n";
    echo "Usage: php cache_manager.php [command]\n\n";
    echo "Commands:\n";
    echo "  stats     - Show cache statistics\n";
    echo "  clear     - Clear all cache\n";
    echo "  clean     - Clean expired cache files\n";
    echo "  warmup    - Warm up cache with common data\n";
    echo "  test      - Test cache performance\n";
    echo "  help      - Show this help message\n";
}

function connectDatabase() {
    try {
        return getDatabaseConnection();
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function showCacheStats($cacheService) {
    echo "Cache Statistics:\n";
    echo "================\n";
    
    $stats = $cacheService->getStats();
    echo "Total files: {$stats['total_files']}\n";
    echo "Valid files: {$stats['valid_files']}\n";
    echo "Expired files: {$stats['expired_files']}\n";
    echo "Total size: {$stats['total_size_mb']} MB\n";
    
    if ($stats['expired_files'] > 0) {
        echo "\nRecommendation: Run 'php cache_manager.php clean' to remove expired files.\n";
    }
}

function clearCache($cacheService) {
    echo "Clearing all cache...\n";
    $cleared = $cacheService->clear();
    echo "Cleared $cleared cache files.\n";
}

function cleanExpiredCache($cacheService) {
    echo "Cleaning expired cache files...\n";
    $cleaned = $cacheService->cleanExpired();
    echo "Cleaned $cleaned expired cache files.\n";
}

function warmUpCache($cacheService, $db) {
    echo "Warming up cache...\n";
    
    $startTime = microtime(true);
    $success = $cacheService->warmUp($db);
    $endTime = microtime(true);
    
    if ($success) {
        $duration = round(($endTime - $startTime) * 1000, 2);
        echo "Cache warm-up completed successfully in {$duration}ms.\n";
    } else {
        echo "Cache warm-up failed. Check error logs for details.\n";
    }
}

function testCachePerformance($cacheService, $db) {
    echo "Testing cache performance...\n";
    echo "===========================\n";
    
    $leaderboardService = new LeaderboardService($db, $cacheService);
    $reportsService = new ReportsService($db, $cacheService);
    
    // Test 1: Leaderboard without cache
    echo "Test 1: Leaderboard (no cache)\n";
    $cacheService->clear('leaderboard*');
    
    $startTime = microtime(true);
    $leaderboard = $leaderboardService->getLeaderboard(50);
    $endTime = microtime(true);
    $noCacheDuration = ($endTime - $startTime) * 1000;
    echo "  Duration: " . round($noCacheDuration, 2) . "ms\n";
    
    // Test 2: Leaderboard with cache
    echo "Test 2: Leaderboard (with cache)\n";
    $startTime = microtime(true);
    $leaderboard = $leaderboardService->getLeaderboard(50);
    $endTime = microtime(true);
    $cacheDuration = ($endTime - $startTime) * 1000;
    echo "  Duration: " . round($cacheDuration, 2) . "ms\n";
    
    $improvement = round((($noCacheDuration - $cacheDuration) / $noCacheDuration) * 100, 1);
    echo "  Cache improvement: {$improvement}%\n";
    
    // Test 3: Dashboard stats
    echo "Test 3: Dashboard Statistics\n";
    $cacheService->clear('dashboard_stats*');
    
    $startTime = microtime(true);
    $stats = $reportsService->getDashboardStatistics();
    $endTime = microtime(true);
    $noCacheDuration = ($endTime - $startTime) * 1000;
    echo "  Duration (no cache): " . round($noCacheDuration, 2) . "ms\n";
    
    $startTime = microtime(true);
    $stats = $reportsService->getDashboardStatistics();
    $endTime = microtime(true);
    $cacheDuration = ($endTime - $startTime) * 1000;
    echo "  Duration (with cache): " . round($cacheDuration, 2) . "ms\n";
    
    $improvement = round((($noCacheDuration - $cacheDuration) / $noCacheDuration) * 100, 1);
    echo "  Cache improvement: {$improvement}%\n";
    
    // Test 4: Multiple user rank lookups
    echo "Test 4: User Rank Lookups (10 users)\n";
    $cacheService->clear('user_rank*');
    
    // Get some user IDs
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'user' AND total_points > 0 LIMIT 10");
    $stmt->execute();
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($userIds)) {
        $startTime = microtime(true);
        foreach ($userIds as $userId) {
            $leaderboardService->getUserRank($userId);
        }
        $endTime = microtime(true);
        $noCacheDuration = ($endTime - $startTime) * 1000;
        echo "  Duration (no cache): " . round($noCacheDuration, 2) . "ms\n";
        
        $startTime = microtime(true);
        foreach ($userIds as $userId) {
            $leaderboardService->getUserRank($userId);
        }
        $endTime = microtime(true);
        $cacheDuration = ($endTime - $startTime) * 1000;
        echo "  Duration (with cache): " . round($cacheDuration, 2) . "ms\n";
        
        $improvement = round((($noCacheDuration - $cacheDuration) / $noCacheDuration) * 100, 1);
        echo "  Cache improvement: {$improvement}%\n";
    } else {
        echo "  No users with points found for testing.\n";
    }
    
    echo "\nPerformance test completed.\n";
}

// Main execution
if ($argc < 2) {
    showUsage();
    exit(1);
}

$command = $argv[1];
$cacheService = new CacheService();

switch ($command) {
    case 'stats':
        showCacheStats($cacheService);
        break;
        
    case 'clear':
        clearCache($cacheService);
        break;
        
    case 'clean':
        cleanExpiredCache($cacheService);
        break;
        
    case 'warmup':
        $db = connectDatabase();
        warmUpCache($cacheService, $db);
        break;
        
    case 'test':
        $db = connectDatabase();
        testCachePerformance($cacheService, $db);
        break;
        
    case 'help':
        showUsage();
        break;
        
    default:
        echo "Unknown command: $command\n\n";
        showUsage();
        exit(1);
}
?>