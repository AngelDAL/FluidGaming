<?php
/**
 * Points service for business logic
 */

require_once __DIR__ . '/../models/PointTransaction.php';

class PointsService {
    private $conn;
    private $pointTransaction;

    public function __construct($db) {
        $this->conn = $db;
        $this->pointTransaction = new PointTransaction($db);
    }

    /**
     * Assign points to user with business logic validation
     */
    public function assignPointsToUser($user_id, $points, $source, $assigned_by, $tournament_id = null, $metadata = null) {
        // Additional business logic can be added here
        // For example: daily limits, special bonuses, etc.
        
        return $this->pointTransaction->create(
            $user_id, 
            $points, 
            'earned', 
            $source, 
            $assigned_by, 
            $tournament_id, 
            $metadata
        );
    }

    /**
     * Calculate leaderboard rankings
     */
    public function calculateLeaderboard($limit = 50) {
        $query = "SELECT u.id, u.nickname, u.profile_image, u.total_points
                  FROM users u 
                  WHERE u.role = 'user' AND u.total_points > 0
                  ORDER BY u.total_points DESC, u.created_at ASC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll();
        
        // Add rank manually
        foreach ($users as $index => &$user) {
            $user['rank'] = $index + 1;
        }
        
        return $users;
    }

    /**
     * Get user's rank in leaderboard
     */
    public function getUserRank($user_id) {
        // MySQL compatible version without ROW_NUMBER()
        $query = "SELECT COUNT(*) + 1 as user_rank
                  FROM users u1
                  JOIN users u2 ON (u2.total_points > u1.total_points 
                                   OR (u2.total_points = u1.total_points AND u2.created_at < u1.created_at))
                  WHERE u1.id = :user_id 
                  AND u1.role = 'user' 
                  AND u2.role = 'user'
                  AND u2.total_points > 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['user_rank'] : null;
    }

    /**
     * Validate point assignment rules
     */
    public function validatePointAssignmentRules($user_id, $points, $source, $assigned_by) {
        $errors = [];

        // Check daily point limit per user (example: max 1000 points per day)
        $dailyLimit = 1000;
        $todayPoints = $this->getUserPointsToday($user_id);
        
        if (($todayPoints + $points) > $dailyLimit) {
            $errors[] = "El usuario ha alcanzado el límite diario de puntos ($dailyLimit)";
        }

        // Check if assigner hasn't exceeded their daily assignment limit
        $assignerDailyLimit = 5000; // Assistants can assign max 5000 points per day
        $assignerTodayPoints = $this->getAssignerPointsToday($assigned_by);
        
        if (($assignerTodayPoints + $points) > $assignerDailyLimit) {
            $errors[] = "Has alcanzado tu límite diario de asignación de puntos ($assignerDailyLimit)";
        }

        return $errors;
    }

    /**
     * Get points assigned to user today
     */
    private function getUserPointsToday($user_id) {
        $query = "SELECT COALESCE(SUM(points), 0) as total
                  FROM point_transactions 
                  WHERE user_id = :user_id 
                  AND type = 'earned'
                  AND DATE(timestamp) = CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['total'] : 0;
    }

    /**
     * Get points assigned by user today
     */
    private function getAssignerPointsToday($assigned_by) {
        $query = "SELECT COALESCE(SUM(points), 0) as total
                  FROM point_transactions 
                  WHERE assigned_by = :assigned_by 
                  AND type = 'earned'
                  AND DATE(timestamp) = CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':assigned_by', $assigned_by);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['total'] : 0;
    }

    /**
     * Get points statistics for dashboard
     */
    public function getPointsStatistics() {
        $stats = [];

        // Total points distributed today
        $query = "SELECT COALESCE(SUM(points), 0) as total
                  FROM point_transactions 
                  WHERE type = 'earned' 
                  AND DATE(timestamp) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['points_today'] = $result['total'];

        // Total active users (users with points)
        $query = "SELECT COUNT(*) as total
                  FROM users 
                  WHERE role = 'user' AND total_points > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['active_users'] = $result['total'];

        // Average points per user
        $query = "SELECT AVG(total_points) as average
                  FROM users 
                  WHERE role = 'user' AND total_points > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['average_points'] = round($result['average'] ?? 0, 2);

        // Top point source
        $query = "SELECT source, SUM(points) as total
                  FROM point_transactions 
                  WHERE type = 'earned'
                  GROUP BY source 
                  ORDER BY total DESC 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['top_source'] = $result ? $result['source'] : 'N/A';

        return $stats;
    }

    /**
     * Sync user total points (recalculate from transactions)
     */
    public function syncUserTotalPoints($user_id) {
        $totalPoints = $this->pointTransaction->getUserTotalPoints($user_id);
        
        $query = "UPDATE users SET total_points = :total_points WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':total_points', $totalPoints);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    /**
     * Bulk sync all user points
     */
    public function syncAllUserPoints() {
        $query = "UPDATE users u 
                  SET total_points = (
                    SELECT COALESCE(SUM(pt.points), 0)
                    FROM point_transactions pt 
                    WHERE pt.user_id = u.id AND pt.type = 'earned'
                  )
                  WHERE u.role = 'user'";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>