<?php
/**
 * Reports service for generating statistics and analytics
 */

class ReportsService {
    private $conn;
    private $cacheService;

    public function __construct($db, $cacheService = null) {
        $this->conn = $db;
        $this->cacheService = $cacheService ?: new CacheService();
    }

    /**
     * Generate event statistics report with caching
     * Requirement 7.1: Show statistics by event
     */
    public function getEventStatistics($event_id = null, $start_date = null, $end_date = null) {
        // Create cache key based on parameters
        $cacheKey = 'event_stats_' . md5(serialize([$event_id, $start_date, $end_date]));
        
        // Try to get from cache first
        $cachedData = $this->cacheService->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        $whereClause = '';
        $params = [];
        $conditions = [];

        if ($event_id) {
            $conditions[] = "e.id = :event_id";
            $params[':event_id'] = $event_id;
        }

        if ($start_date && $end_date) {
            $conditions[] = "(e.start_date BETWEEN :start_date AND :end_date OR e.end_date BETWEEN :start_date AND :end_date)";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }

        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Query with optimizations
        $query = "SELECT 
                    e.id,
                    e.name,
                    e.start_date,
                    e.end_date,
                    e.is_active,
                    COUNT(DISTINCT t.id) as total_tournaments,
                    COUNT(DISTINCT pt.user_id) as unique_participants,
                    COALESCE(SUM(pt.points), 0) as total_points_distributed,
                    COUNT(DISTINCT c.id) as total_claims,
                    COALESCE(SUM(CASE WHEN c.status = 'completed' THEN p.points_required ELSE 0 END), 0) as total_points_claimed
                  FROM events e
                  LEFT JOIN tournaments t ON e.id = t.event_id
                  LEFT JOIN point_transactions pt ON t.id = pt.tournament_id AND pt.type = 'earned'
                  LEFT JOIN stands s ON e.id = s.event_id
                  LEFT JOIN claims c ON s.id = c.stand_id
                  LEFT JOIN products p ON c.product_id = p.id
                  $whereClause
                  GROUP BY e.id, e.name, e.start_date, e.end_date, e.is_active
                  ORDER BY e.start_date DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cache the result for 15 minutes
        $this->cacheService->set($cacheKey, $result, 900);
        
        return $result;
    }

    /**
     * Generate tournament participation report
     * Requirement 7.2: Show tournament participation data
     */
    public function getTournamentParticipationReport($event_id = null, $tournament_id = null) {
        $whereClause = '';
        $params = [];
        $conditions = [];

        if ($event_id) {
            $conditions[] = "t.event_id = :event_id";
            $params[':event_id'] = $event_id;
        }

        if ($tournament_id) {
            $conditions[] = "t.id = :tournament_id";
            $params[':tournament_id'] = $tournament_id;
        }

        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT 
                    t.id as tournament_id,
                    t.name as tournament_name,
                    t.scheduled_time,
                    t.points_reward,
                    t.status,
                    e.name as event_name,
                    COUNT(DISTINCT pt.user_id) as participants_count,
                    COALESCE(SUM(pt.points), 0) as total_points_awarded,
                    AVG(pt.points) as avg_points_per_participant,
                    MAX(pt.points) as max_points_awarded,
                    MIN(pt.points) as min_points_awarded
                  FROM tournaments t
                  LEFT JOIN events e ON t.event_id = e.id
                  LEFT JOIN point_transactions pt ON t.id = pt.tournament_id AND pt.type = 'earned'
                  $whereClause
                  GROUP BY t.id, t.name, t.scheduled_time, t.points_reward, t.status, e.name
                  ORDER BY t.scheduled_time DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate claims trends analysis
     * Requirement 7.3: Show trends in product claims
     */
    public function getClaimsTrendsReport($start_date = null, $end_date = null, $stand_id = null) {
        $whereClause = '';
        $params = [];
        $conditions = [];

        if ($start_date && $end_date) {
            $conditions[] = "c.timestamp BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }

        if ($stand_id) {
            $conditions[] = "c.stand_id = :stand_id";
            $params[':stand_id'] = $stand_id;
        }

        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Daily claims trend
        $dailyTrendQuery = "SELECT 
                              DATE(c.timestamp) as claim_date,
                              COUNT(*) as total_claims,
                              COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as completed_claims,
                              COUNT(CASE WHEN c.status = 'pending' THEN 1 END) as pending_claims,
                              COALESCE(SUM(CASE WHEN c.status = 'completed' THEN p.points_required ELSE 0 END), 0) as points_claimed
                            FROM claims c
                            LEFT JOIN products p ON c.product_id = p.id
                            $whereClause
                            GROUP BY DATE(c.timestamp)
                            ORDER BY claim_date DESC";

        $stmt = $this->conn->prepare($dailyTrendQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $dailyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Most popular products
        $popularProductsQuery = "SELECT 
                                   p.id,
                                   p.name as product_name,
                                   p.points_required,
                                   s.name as stand_name,
                                   COUNT(c.id) as total_claims,
                                   COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as completed_claims,
                                   (COUNT(CASE WHEN c.status = 'completed' THEN 1 END) * 100.0 / COUNT(c.id)) as completion_rate
                                 FROM products p
                                 LEFT JOIN claims c ON p.id = c.product_id
                                 LEFT JOIN stands s ON p.stand_id = s.id
                                 " . str_replace('c.', 'c.', $whereClause) . "
                                 GROUP BY p.id, p.name, p.points_required, s.name
                                 HAVING total_claims > 0
                                 ORDER BY total_claims DESC
                                 LIMIT 10";

        $stmt = $this->conn->prepare($popularProductsQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $popularProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stand performance
        $standPerformanceQuery = "SELECT 
                                    s.id,
                                    s.name as stand_name,
                                    COUNT(DISTINCT p.id) as total_products,
                                    COUNT(c.id) as total_claims,
                                    COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as completed_claims,
                                    COALESCE(SUM(CASE WHEN c.status = 'completed' THEN p.points_required ELSE 0 END), 0) as total_points_claimed,
                                    AVG(p.points_required) as avg_product_points
                                  FROM stands s
                                  LEFT JOIN products p ON s.id = p.stand_id
                                  LEFT JOIN claims c ON p.id = c.product_id
                                  " . str_replace('c.', 'c.', $whereClause) . "
                                  GROUP BY s.id, s.name
                                  ORDER BY total_claims DESC";

        $stmt = $this->conn->prepare($standPerformanceQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $standPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'daily_trends' => $dailyTrends,
            'popular_products' => $popularProducts,
            'stand_performance' => $standPerformance
        ];
    }

    /**
     * Generate comprehensive dashboard statistics with caching
     */
    public function getDashboardStatistics($event_id = null) {
        // Check cache first
        $cacheSubkey = $event_id ? "event_$event_id" : 'global';
        $cachedStats = $this->cacheService->get(CacheService::DASHBOARD_STATS_KEY, $cacheSubkey);
        if ($cachedStats !== null) {
            return $cachedStats;
        }

        $stats = [];

        // Optimized overall system statistics
        if ($event_id) {
            // Event-specific statistics
            $overallQuery = "SELECT 
                               (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
                               1 as total_events,
                               (SELECT COUNT(*) FROM tournaments WHERE event_id = :event_id) as total_tournaments,
                               (SELECT COUNT(*) FROM stands WHERE event_id = :event_id) as total_stands,
                               (SELECT COUNT(*) FROM products p JOIN stands s ON p.stand_id = s.id WHERE s.event_id = :event_id) as total_products,
                               (SELECT COALESCE(SUM(pt.points), 0) FROM point_transactions pt JOIN tournaments t ON pt.tournament_id = t.id WHERE t.event_id = :event_id AND pt.type = 'earned') as total_points_distributed,
                               (SELECT COUNT(*) FROM claims c JOIN stands s ON c.stand_id = s.id WHERE s.event_id = :event_id) as total_claims";
            
            $stmt = $this->conn->prepare($overallQuery);
            $stmt->bindParam(':event_id', $event_id);
        } else {
            // Global statistics
            $overallQuery = "SELECT 
                               (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
                               (SELECT COUNT(*) FROM events) as total_events,
                               (SELECT COUNT(*) FROM tournaments) as total_tournaments,
                               (SELECT COUNT(*) FROM stands) as total_stands,
                               (SELECT COUNT(*) FROM products) as total_products,
                               (SELECT COALESCE(SUM(points), 0) FROM point_transactions WHERE type = 'earned') as total_points_distributed,
                               (SELECT COUNT(*) FROM claims) as total_claims";
            
            $stmt = $this->conn->prepare($overallQuery);
        }
        
        $stmt->execute();
        $stats['overall'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Recent activity (last 7 days)
        $recentActivityQuery = "SELECT 
                                  COUNT(DISTINCT pt.id) as recent_point_transactions,
                                  COUNT(DISTINCT c.id) as recent_claims,
                                  COUNT(DISTINCT pt.user_id) as active_users_week
                                FROM point_transactions pt
                                LEFT JOIN claims c ON DATE(pt.timestamp) = DATE(c.timestamp)
                                WHERE pt.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

        $stmt = $this->conn->prepare($recentActivityQuery);
        $stmt->execute();
        $stats['recent_activity'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Top performers
        $topPerformersQuery = "SELECT 
                                 u.id,
                                 u.nickname,
                                 u.profile_image,
                                 u.total_points,
                                 COUNT(DISTINCT pt.tournament_id) as tournaments_participated,
                                 COUNT(DISTINCT c.id) as products_claimed
                               FROM users u
                               LEFT JOIN point_transactions pt ON u.id = pt.user_id AND pt.type = 'earned'
                               LEFT JOIN claims c ON u.id = c.user_id AND c.status = 'completed'
                               WHERE u.role = 'user' AND u.total_points > 0
                               GROUP BY u.id, u.nickname, u.profile_image, u.total_points
                               ORDER BY u.total_points DESC
                               LIMIT 5";

        $stmt = $this->conn->prepare($topPerformersQuery);
        $stmt->execute();
        $stats['top_performers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cache the result for 10 minutes
        $this->cacheService->cacheDashboardStats($stats, $event_id);

        return $stats;
    }

    /**
     * Generate user activity report
     */
    public function getUserActivityReport($user_id = null, $start_date = null, $end_date = null) {
        $whereClause = '';
        $params = [];
        $conditions = [];

        if ($user_id) {
            $conditions[] = "u.id = :user_id";
            $params[':user_id'] = $user_id;
        }

        if ($start_date && $end_date) {
            $conditions[] = "pt.timestamp BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }

        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT 
                    u.id,
                    u.nickname,
                    u.profile_image,
                    u.total_points,
                    COUNT(DISTINCT pt.id) as total_point_transactions,
                    COALESCE(SUM(pt.points), 0) as total_points_earned,
                    COUNT(DISTINCT pt.tournament_id) as tournaments_participated,
                    COUNT(DISTINCT c.id) as total_claims,
                    COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as completed_claims,
                    COALESCE(SUM(CASE WHEN c.status = 'completed' THEN p.points_required ELSE 0 END), 0) as points_spent,
                    MIN(pt.timestamp) as first_activity,
                    MAX(pt.timestamp) as last_activity
                  FROM users u
                  LEFT JOIN point_transactions pt ON u.id = pt.user_id AND pt.type = 'earned'
                  LEFT JOIN claims c ON u.id = c.user_id
                  LEFT JOIN products p ON c.product_id = p.id
                  $whereClause
                  AND u.role = 'user'
                  GROUP BY u.id, u.nickname, u.profile_image, u.total_points
                  ORDER BY u.total_points DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate points distribution analysis
     */
    public function getPointsDistributionReport($event_id = null) {
        $whereClause = '';
        $params = [];

        if ($event_id) {
            $whereClause = "WHERE t.event_id = :event_id";
            $params[':event_id'] = $event_id;
        }

        // Points by source
        $sourceQuery = "SELECT 
                          pt.source,
                          COUNT(*) as transaction_count,
                          SUM(pt.points) as total_points,
                          AVG(pt.points) as avg_points,
                          COUNT(DISTINCT pt.user_id) as unique_users
                        FROM point_transactions pt
                        LEFT JOIN tournaments t ON pt.tournament_id = t.id
                        $whereClause
                        AND pt.type = 'earned'
                        GROUP BY pt.source
                        ORDER BY total_points DESC";

        $stmt = $this->conn->prepare($sourceQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $pointsBySource = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Points by tournament
        $tournamentQuery = "SELECT 
                              t.id,
                              t.name as tournament_name,
                              t.points_reward as expected_points,
                              COUNT(pt.id) as actual_transactions,
                              SUM(pt.points) as actual_points_distributed,
                              COUNT(DISTINCT pt.user_id) as participants
                            FROM tournaments t
                            LEFT JOIN point_transactions pt ON t.id = pt.tournament_id AND pt.type = 'earned'
                            " . str_replace('WHERE t.event_id', 'WHERE t.event_id', $whereClause) . "
                            GROUP BY t.id, t.name, t.points_reward
                            ORDER BY actual_points_distributed DESC";

        $stmt = $this->conn->prepare($tournamentQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $pointsByTournament = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Daily points distribution
        $dailyQuery = "SELECT 
                         DATE(pt.timestamp) as distribution_date,
                         COUNT(*) as transactions,
                         SUM(pt.points) as total_points,
                         COUNT(DISTINCT pt.user_id) as active_users,
                         COUNT(DISTINCT pt.assigned_by) as active_assistants
                       FROM point_transactions pt
                       LEFT JOIN tournaments t ON pt.tournament_id = t.id
                       $whereClause
                       AND pt.type = 'earned'
                       GROUP BY DATE(pt.timestamp)
                       ORDER BY distribution_date DESC
                       LIMIT 30";

        $stmt = $this->conn->prepare($dailyQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $dailyDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'points_by_source' => $pointsBySource,
            'points_by_tournament' => $pointsByTournament,
            'daily_distribution' => $dailyDistribution
        ];
    }

    /**
     * Export report data to CSV format
     */
    public function exportToCSV($reportData, $filename, $headers = []) {
        if (empty($reportData)) {
            return ['success' => false, 'error' => 'No data to export'];
        }

        // Create exports directory if it doesn't exist
        $exportDir = __DIR__ . '/../exports/';
        if (!is_dir($exportDir)) {
            if (!mkdir($exportDir, 0755, true)) {
                return ['success' => false, 'error' => 'Cannot create export directory'];
            }
        }

        $filepath = $exportDir . $filename . '_' . date('Y-m-d_H-i-s') . '.csv';
        $file = fopen($filepath, 'w');

        if (!$file) {
            return ['success' => false, 'error' => 'Cannot create export file'];
        }

        // Write headers
        if (empty($headers) && !empty($reportData)) {
            $headers = array_keys($reportData[0]);
        }
        fputcsv($file, $headers);

        // Write data
        foreach ($reportData as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => basename($filepath),
            'download_url' => 'exports/' . basename($filepath)
        ];
    }

    /**
     * Get available report types
     */
    public function getAvailableReports() {
        return [
            'event_statistics' => [
                'name' => 'Estadísticas por Evento',
                'description' => 'Resumen de actividad por evento incluyendo torneos y participación',
                'parameters' => ['event_id', 'start_date', 'end_date']
            ],
            'tournament_participation' => [
                'name' => 'Participación en Torneos',
                'description' => 'Análisis detallado de participación y puntos por torneo',
                'parameters' => ['event_id', 'tournament_id']
            ],
            'claims_trends' => [
                'name' => 'Tendencias de Reclamos',
                'description' => 'Análisis de productos más populares y tendencias de reclamos',
                'parameters' => ['start_date', 'end_date', 'stand_id']
            ],
            'user_activity' => [
                'name' => 'Actividad de Usuarios',
                'description' => 'Reporte de actividad individual de usuarios',
                'parameters' => ['user_id', 'start_date', 'end_date']
            ],
            'points_distribution' => [
                'name' => 'Distribución de Puntos',
                'description' => 'Análisis de cómo se distribuyen los puntos por fuente y tiempo',
                'parameters' => ['event_id']
            ]
        ];
    }
}
?>