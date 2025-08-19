<?php
require_once __DIR__ . '/CacheService.php';

class LeaderboardService {
    private $conn;
    private $cacheService;

    public function __construct($db, $cacheService = null) {
        $this->conn = $db;
        $this->cacheService = $cacheService ?: new CacheService();
    }

    /**
     * Get leaderboard with enhanced caching
     */
    public function getLeaderboard($limit = 50, $forceRefresh = false) {
        if (!$forceRefresh) {
            $cachedData = $this->cacheService->get(CacheService::LEADERBOARD_KEY);
            if ($cachedData !== null) {
                return array_slice($cachedData, 0, $limit);
            }
        }

        // Calculate fresh leaderboard with optimized query
        $leaderboard = $this->calculateLeaderboardOptimized($limit);
        
        // Cache the result with activity-based expiry
        $isHighActivity = $this->isHighActivityPeriod();
        $this->cacheService->cacheLeaderboard($leaderboard, $isHighActivity);
        
        return $leaderboard;
    }

    /**
     * Calculate leaderboard rankings with optimized query
     */
    private function calculateLeaderboardOptimized($limit = 50) {
        // Optimized query (will use indexes when available)
        $query = "SELECT 
                    u.id, 
                    u.nickname, 
                    u.profile_image, 
                    u.total_points,
                    MIN(pt.timestamp) as first_point_date
                  FROM users u
                  LEFT JOIN point_transactions pt 
                    ON u.id = pt.user_id AND pt.type = 'earned'
                  WHERE u.role = 'user' AND u.total_points > 0
                  GROUP BY u.id, u.nickname, u.profile_image, u.total_points
                  ORDER BY u.total_points DESC, first_point_date ASC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add rank and format data
        $leaderboard = [];
        foreach ($users as $index => $user) {
            $leaderboard[] = [
                'rank' => $index + 1,
                'user_id' => $user['id'],
                'nickname' => $user['nickname'],
                'profile_image' => $user['profile_image'],
                'total_points' => (int)$user['total_points'],
                'first_point_date' => $user['first_point_date']
            ];
        }
        
        return $leaderboard;
    }

    /**
     * Check if it's a high activity period (during events)
     */
    private function isHighActivityPeriod() {
        $query = "SELECT COUNT(*) as active_events 
                  FROM events 
                  WHERE is_active = 1 
                    AND NOW() BETWEEN start_date AND end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['active_events'] > 0;
    }

    /**
     * Get user's rank in leaderboard with caching
     */
    public function getUserRank($user_id) {
        // Check cache first
        $cachedRank = $this->cacheService->get(CacheService::USER_RANK_KEY, $user_id);
        if ($cachedRank !== null) {
            return $cachedRank;
        }

        // Get user's points and first point date
        $userQuery = "SELECT 
                        u.total_points,
                        MIN(pt.timestamp) as first_point_date
                      FROM users u
                      LEFT JOIN point_transactions pt 
                        ON u.id = pt.user_id AND pt.type = 'earned'
                      WHERE u.id = :user_id AND u.role = 'user'
                      GROUP BY u.id, u.total_points";
        
        $userStmt = $this->conn->prepare($userQuery);
        $userStmt->bindParam(':user_id', $user_id);
        $userStmt->execute();
        
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$userData || $userData['total_points'] <= 0) {
            return null; // User has no points or doesn't exist
        }

        // Calculate rank
        $rankQuery = "SELECT COUNT(*) + 1 as user_rank
                      FROM (
                        SELECT 
                          u.id,
                          u.total_points,
                          MIN(pt.timestamp) as first_point_date
                        FROM users u
                        LEFT JOIN point_transactions pt ON u.id = pt.user_id AND pt.type = 'earned'
                        WHERE u.role = 'user' AND u.total_points > 0 AND u.id != ?
                        GROUP BY u.id, u.total_points
                        HAVING (u.total_points > ?) 
                            OR (u.total_points = ? AND first_point_date < ?)
                      ) as better_users";
        
        $rankStmt = $this->conn->prepare($rankQuery);
        $rankStmt->execute([
            $user_id,
            $userData['total_points'],
            $userData['total_points'],
            $userData['first_point_date']
        ]);
        
        $result = $rankStmt->fetch(PDO::FETCH_ASSOC);
        $rank = $result ? (int)$result['user_rank'] : null;
        
        // Cache the result
        if ($rank !== null) {
            $this->cacheService->cacheUserRank($user_id, $rank);
        }
        
        return $rank;
    }

    /**
     * Get user's leaderboard position with context (users around them)
     */
    public function getUserLeaderboardContext($user_id, $contextSize = 5) {
        $userRank = $this->getUserRank($user_id);
        if ($userRank === null) {
            return null;
        }

        // Get users around the user's position
        $startRank = max(1, $userRank - $contextSize);
        $endRank = $userRank + $contextSize;
        $limit = $endRank - $startRank + 1;
        $offset = $startRank - 1;

        $query = "SELECT 
                    u.id, 
                    u.nickname, 
                    u.profile_image, 
                    u.total_points,
                    MIN(pt.timestamp) as first_point_date
                  FROM users u 
                  LEFT JOIN point_transactions pt ON u.id = pt.user_id AND pt.type = 'earned'
                  WHERE u.role = 'user' AND u.total_points > 0
                  GROUP BY u.id, u.nickname, u.profile_image, u.total_points
                  ORDER BY u.total_points DESC, first_point_date ASC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add rank and format data
        $context = [];
        foreach ($users as $index => $user) {
            $context[] = [
                'rank' => $startRank + $index,
                'user_id' => $user['id'],
                'nickname' => $user['nickname'],
                'profile_image' => $user['profile_image'],
                'total_points' => (int)$user['total_points'],
                'first_point_date' => $user['first_point_date'],
                'is_current_user' => $user['id'] == $user_id
            ];
        }
        
        return [
            'user_rank' => $userRank,
            'context' => $context
        ];
    }

    /**
     * Get leaderboard statistics
     */
    public function getLeaderboardStats() {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    MAX(total_points) as highest_points,
                    AVG(total_points) as average_points,
                    MIN(total_points) as lowest_points
                  FROM users 
                  WHERE role = 'user' AND total_points > 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get top 3 users
        $topUsersQuery = "SELECT 
                            u.id, 
                            u.nickname, 
                            u.profile_image, 
                            u.total_points
                          FROM users u 
                          WHERE u.role = 'user' AND u.total_points > 0
                          ORDER BY u.total_points DESC, u.created_at ASC
                          LIMIT 3";
        
        $topStmt = $this->conn->prepare($topUsersQuery);
        $topStmt->execute();
        $topUsers = $topStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_users' => (int)$stats['total_users'],
            'highest_points' => (int)$stats['highest_points'],
            'average_points' => round($stats['average_points'], 2),
            'lowest_points' => (int)$stats['lowest_points'],
            'top_users' => $topUsers
        ];
    }

    /**
     * Clear leaderboard cache
     */
    public function clearCache() {
        $this->cacheService->invalidateLeaderboardCaches();
    }

    /**
     * Update cache after point changes and notify ranking changes
     */
    public function updateCacheAfterPointChange($notificationService = null) {
        // Get previous leaderboard before update
        $previousLeaderboard = $this->cacheService->get(CacheService::LEADERBOARD_KEY) ?? [];
        
        // Invalidate caches
        $this->cacheService->invalidateLeaderboardCaches();
        
        // Force refresh the leaderboard and cache it
        $currentLeaderboard = $this->getLeaderboard(100, true);
        
        // Send notifications for ranking changes if notification service is provided
        if ($notificationService && !empty($previousLeaderboard)) {
            $this->notifyRankingChanges($previousLeaderboard, $currentLeaderboard, $notificationService);
        }
    }
    
    /**
     * Notify users about ranking changes
     */
    private function notifyRankingChanges($previousLeaderboard, $currentLeaderboard, $notificationService) {
        $previousRanks = [];
        
        // Create lookup for previous ranks
        foreach ($previousLeaderboard as $entry) {
            $previousRanks[$entry['user_id']] = $entry['rank'];
        }
        
        // Check for significant ranking changes (top 10 or major changes)
        foreach ($currentLeaderboard as $entry) {
            $userId = $entry['user_id'];
            $currentRank = $entry['rank'];
            $previousRank = $previousRanks[$userId] ?? null;
            
            // Only notify for significant changes
            if ($previousRank !== null && $previousRank !== $currentRank) {
                $rankDifference = abs($previousRank - $currentRank);
                
                // Notify if:
                // 1. User moved into top 10
                // 2. User moved out of top 10
                // 3. Rank change is significant (5+ positions)
                if ($currentRank <= 10 || $previousRank <= 10 || $rankDifference >= 5) {
                    $notificationService->notifyLeaderboardChange($userId, $currentRank, $previousRank);
                }
            }
        }
    }

    /**
     * Get leaderboard changes since last update
     */
    public function getLeaderboardChanges($previousLeaderboard) {
        $currentLeaderboard = $this->getLeaderboard(100);
        
        $changes = [];
        $previousRanks = [];
        
        // Create lookup for previous ranks
        foreach ($previousLeaderboard as $entry) {
            $previousRanks[$entry['user_id']] = $entry['rank'];
        }
        
        // Compare current with previous
        foreach ($currentLeaderboard as $entry) {
            $userId = $entry['user_id'];
            $currentRank = $entry['rank'];
            $previousRank = $previousRanks[$userId] ?? null;
            
            if ($previousRank === null) {
                // New entry
                $changes[] = [
                    'user_id' => $userId,
                    'nickname' => $entry['nickname'],
                    'change_type' => 'new',
                    'current_rank' => $currentRank,
                    'previous_rank' => null,
                    'rank_change' => null
                ];
            } elseif ($previousRank !== $currentRank) {
                // Rank changed
                $changes[] = [
                    'user_id' => $userId,
                    'nickname' => $entry['nickname'],
                    'change_type' => 'rank_change',
                    'current_rank' => $currentRank,
                    'previous_rank' => $previousRank,
                    'rank_change' => $previousRank - $currentRank // Positive = moved up
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Get leaderboard for specific time period
     */
    public function getLeaderboardForPeriod($startDate, $endDate, $limit = 50) {
        $query = "SELECT 
                    u.id, 
                    u.nickname, 
                    u.profile_image, 
                    COALESCE(SUM(pt.points), 0) as period_points,
                    MIN(pt.timestamp) as first_point_date
                  FROM users u 
                  LEFT JOIN point_transactions pt ON u.id = pt.user_id 
                    AND pt.type = 'earned' 
                    AND pt.timestamp BETWEEN :start_date AND :end_date
                  WHERE u.role = 'user'
                  GROUP BY u.id, u.nickname, u.profile_image
                  HAVING period_points > 0
                  ORDER BY period_points DESC, first_point_date ASC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add rank and format data
        $leaderboard = [];
        foreach ($users as $index => $user) {
            $leaderboard[] = [
                'rank' => $index + 1,
                'user_id' => $user['id'],
                'nickname' => $user['nickname'],
                'profile_image' => $user['profile_image'],
                'period_points' => (int)$user['period_points'],
                'first_point_date' => $user['first_point_date']
            ];
        }
        
        return $leaderboard;
    }
}
?>