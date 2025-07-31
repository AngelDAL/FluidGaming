<?php
/**
 * Enhanced caching service for performance optimization
 * Task 13.1: Implement cache for leaderboard and static data
 */

class CacheService {
    private $cacheDir;
    private $defaultExpiry;
    
    // Cache keys constants
    const LEADERBOARD_KEY = 'leaderboard';
    const EVENT_STATS_KEY = 'event_stats';
    const TOURNAMENT_LIST_KEY = 'tournament_list';
    const STAND_PRODUCTS_KEY = 'stand_products';
    const USER_RANK_KEY = 'user_rank';
    const DASHBOARD_STATS_KEY = 'dashboard_stats';
    
    public function __construct($cacheDir = null, $defaultExpiry = 300) {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../cache/';
        $this->defaultExpiry = $defaultExpiry; // 5 minutes default
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key, $subkey = null) {
        $cacheFile = $this->getCacheFilePath($key, $subkey);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Invalid JSON, remove cache file
            unlink($cacheFile);
            return null;
        }
        
        // Check if cache is expired
        if (isset($cacheData['expires_at']) && time() > $cacheData['expires_at']) {
            unlink($cacheFile);
            return null;
        }
        
        return $cacheData['data'] ?? null;
    }
    
    /**
     * Set cached data
     */
    public function set($key, $data, $expiry = null, $subkey = null) {
        $cacheFile = $this->getCacheFilePath($key, $subkey);
        $expiry = $expiry ?: $this->defaultExpiry;
        
        $cacheData = [
            'data' => $data,
            'created_at' => time(),
            'expires_at' => time() + $expiry
        ];
        
        $result = file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT), LOCK_EX);
        return $result !== false;
    }
    
    /**
     * Delete cached data
     */
    public function delete($key, $subkey = null) {
        $cacheFile = $this->getCacheFilePath($key, $subkey);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Check if cache exists and is valid
     */
    public function exists($key, $subkey = null) {
        return $this->get($key, $subkey) !== null;
    }
    
    /**
     * Clear all cache or cache by pattern
     */
    public function clear($pattern = '*') {
        $files = glob($this->cacheDir . $pattern . '.json');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.json');
        $totalSize = 0;
        $validFiles = 0;
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            
            $content = json_decode(file_get_contents($file), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($content['expires_at']) && time() > $content['expires_at']) {
                    $expiredFiles++;
                } else {
                    $validFiles++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validFiles,
            'expired_files' => $expiredFiles,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Clean expired cache files
     */
    public function cleanExpired() {
        $files = glob($this->cacheDir . '*.json');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $content = json_decode(file_get_contents($file), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($content['expires_at']) && time() > $content['expires_at']) {
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFilePath($key, $subkey = null) {
        $filename = $key;
        if ($subkey !== null) {
            $filename .= '_' . md5($subkey);
        }
        return $this->cacheDir . $filename . '.json';
    }
    
    /**
     * Cache leaderboard data with different expiry times based on activity
     */
    public function cacheLeaderboard($data, $isHighActivity = false) {
        // Use shorter cache time during high activity periods
        $expiry = $isHighActivity ? 60 : 300; // 1 minute vs 5 minutes
        return $this->set(self::LEADERBOARD_KEY, $data, $expiry);
    }
    
    /**
     * Cache user rank with shorter expiry
     */
    public function cacheUserRank($userId, $rank) {
        return $this->set(self::USER_RANK_KEY, $rank, 180, $userId); // 3 minutes
    }
    
    /**
     * Cache event statistics with longer expiry
     */
    public function cacheEventStats($eventId, $stats) {
        return $this->set(self::EVENT_STATS_KEY, $stats, 900, $eventId); // 15 minutes
    }
    
    /**
     * Cache tournament list by event
     */
    public function cacheTournamentList($eventId, $tournaments) {
        return $this->set(self::TOURNAMENT_LIST_KEY, $tournaments, 600, $eventId); // 10 minutes
    }
    
    /**
     * Cache stand products
     */
    public function cacheStandProducts($standId, $products) {
        return $this->set(self::STAND_PRODUCTS_KEY, $products, 1800, $standId); // 30 minutes
    }
    
    /**
     * Cache dashboard statistics
     */
    public function cacheDashboardStats($stats, $eventId = null) {
        $subkey = $eventId ? "event_$eventId" : 'global';
        return $this->set(self::DASHBOARD_STATS_KEY, $stats, 600, $subkey); // 10 minutes
    }
    
    /**
     * Invalidate related caches when data changes
     */
    public function invalidateLeaderboardCaches() {
        $this->delete(self::LEADERBOARD_KEY);
        // Clear all user rank caches
        $this->clear('user_rank_*');
        $this->delete(self::DASHBOARD_STATS_KEY, 'global');
    }
    
    /**
     * Invalidate event-related caches
     */
    public function invalidateEventCaches($eventId) {
        $this->delete(self::EVENT_STATS_KEY, $eventId);
        $this->delete(self::TOURNAMENT_LIST_KEY, $eventId);
        $this->delete(self::DASHBOARD_STATS_KEY, "event_$eventId");
    }
    
    /**
     * Invalidate stand-related caches
     */
    public function invalidateStandCaches($standId) {
        $this->delete(self::STAND_PRODUCTS_KEY, $standId);
    }
    
    /**
     * Get or set with callback (cache-aside pattern)
     */
    public function remember($key, $callback, $expiry = null, $subkey = null) {
        $data = $this->get($key, $subkey);
        
        if ($data === null) {
            $data = $callback();
            if ($data !== null) {
                $this->set($key, $data, $expiry, $subkey);
            }
        }
        
        return $data;
    }
    
    /**
     * Warm up cache with commonly accessed data
     */
    public function warmUp($db) {
        try {
            // Warm up leaderboard
            $leaderboardService = new LeaderboardService($db);
            $leaderboard = $leaderboardService->getLeaderboard(50, true);
            $this->cacheLeaderboard($leaderboard);
            
            // Warm up active events
            $stmt = $db->prepare("SELECT * FROM events WHERE is_active = 1 ORDER BY start_date DESC");
            $stmt->execute();
            $activeEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($activeEvents as $event) {
                // Cache tournament list for each active event
                $tournamentStmt = $db->prepare("SELECT * FROM tournaments WHERE event_id = ? ORDER BY scheduled_time ASC");
                $tournamentStmt->execute([$event['id']]);
                $tournaments = $tournamentStmt->fetchAll(PDO::FETCH_ASSOC);
                $this->cacheTournamentList($event['id'], $tournaments);
            }
            
            // Warm up dashboard stats
            $reportsService = new ReportsService($db);
            $dashboardStats = $reportsService->getDashboardStatistics();
            $this->cacheDashboardStats($dashboardStats);
            
            return true;
        } catch (Exception $e) {
            error_log("Cache warm-up failed: " . $e->getMessage());
            return false;
        }
    }
}
?>