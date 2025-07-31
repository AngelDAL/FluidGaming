<?php
/**
 * Reports API endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/environment.php';
require_once '../includes/auth.php';
require_once '../services/CacheService.php';
require_once '../services/ReportsService.php';

// Start session and check authentication
session_start();

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit();
}

// Initialize database and service
$db = getDatabaseConnection();
$reportsService = new ReportsService($db);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'event_statistics':
            $event_id = $_GET['event_id'] ?? null;
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            
            $data = $reportsService->getEventStatistics($event_id, $start_date, $end_date);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'tournament_participation':
            $event_id = $_GET['event_id'] ?? null;
            $tournament_id = $_GET['tournament_id'] ?? null;
            
            $data = $reportsService->getTournamentParticipationReport($event_id, $tournament_id);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'claims_trends':
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            $stand_id = $_GET['stand_id'] ?? null;
            
            $data = $reportsService->getClaimsTrendsReport($start_date, $end_date, $stand_id);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'user_activity':
            $user_id = $_GET['user_id'] ?? null;
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            
            $data = $reportsService->getUserActivityReport($user_id, $start_date, $end_date);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'points_distribution':
            $event_id = $_GET['event_id'] ?? null;
            
            $data = $reportsService->getPointsDistributionReport($event_id);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'dashboard_stats':
            $event_id = $_GET['event_id'] ?? null;
            
            $data = $reportsService->getDashboardStatistics($event_id);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'available_reports':
            $data = $reportsService->getAvailableReports();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'export_csv':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $report_type = $input['report_type'] ?? '';
            $filename = $input['filename'] ?? 'report';
            $filters = $input['filters'] ?? [];

            // Generate report data based on type
            $reportData = [];
            switch ($report_type) {
                case 'event_statistics':
                    $reportData = $reportsService->getEventStatistics(
                        $filters['event_id'] ?? null,
                        $filters['start_date'] ?? null,
                        $filters['end_date'] ?? null
                    );
                    break;

                case 'tournament_participation':
                    $reportData = $reportsService->getTournamentParticipationReport(
                        $filters['event_id'] ?? null,
                        $filters['tournament_id'] ?? null
                    );
                    break;

                case 'claims_trends':
                    $trendsData = $reportsService->getClaimsTrendsReport(
                        $filters['start_date'] ?? null,
                        $filters['end_date'] ?? null,
                        $filters['stand_id'] ?? null
                    );
                    // Use daily trends for CSV export
                    $reportData = $trendsData['daily_trends'];
                    break;

                case 'user_activity':
                    $reportData = $reportsService->getUserActivityReport(
                        $filters['user_id'] ?? null,
                        $filters['start_date'] ?? null,
                        $filters['end_date'] ?? null
                    );
                    break;

                case 'points_distribution':
                    $distributionData = $reportsService->getPointsDistributionReport(
                        $filters['event_id'] ?? null
                    );
                    // Use points by source for CSV export
                    $reportData = $distributionData['points_by_source'];
                    break;

                default:
                    throw new Exception('Tipo de reporte no válido');
            }

            $result = $reportsService->exportToCSV($reportData, $filename);
            echo json_encode($result);
            break;

        case 'get_events':
            // Helper endpoint to get events for filters
            $query = "SELECT id, name, start_date, end_date FROM events ORDER BY start_date DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $events]);
            break;

        case 'get_stands':
            // Helper endpoint to get stands for filters
            $query = "SELECT id, name FROM stands ORDER BY name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $stands]);
            break;

        case 'get_tournaments':
            // Helper endpoint to get tournaments for filters
            $event_id = $_GET['event_id'] ?? null;
            $whereClause = $event_id ? "WHERE event_id = :event_id" : "";
            
            $query = "SELECT id, name, scheduled_time FROM tournaments $whereClause ORDER BY scheduled_time DESC";
            $stmt = $db->prepare($query);
            
            if ($event_id) {
                $stmt->bindParam(':event_id', $event_id);
            }
            
            $stmt->execute();
            $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $tournaments]);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>