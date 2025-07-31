<?php
/**
 * Leaderboard API endpoints
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../services/CacheService.php';
require_once __DIR__ . '/../services/LeaderboardService.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialize database connection
$db = getDatabaseConnection();

// Initialize services
$leaderboardService = new LeaderboardService($db);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $leaderboardService);
            break;
        case 'POST':
            handlePostRequest($action, $leaderboardService);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($action, $leaderboardService) {
    switch ($action) {
        case 'get':
            getLeaderboard($leaderboardService);
            break;
        case 'user_rank':
            getUserRank($leaderboardService);
            break;
        case 'user_context':
            getUserContext($leaderboardService);
            break;
        case 'stats':
            getLeaderboardStats($leaderboardService);
            break;
        case 'period':
            getLeaderboardForPeriod($leaderboardService);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($action, $leaderboardService) {
    switch ($action) {
        case 'clear_cache':
            clearLeaderboardCache($leaderboardService);
            break;
        case 'refresh':
            refreshLeaderboard($leaderboardService);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
}

/**
 * Get leaderboard
 */
function getLeaderboard($leaderboardService) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';
    
    // Validate limit
    if ($limit < 1 || $limit > 100) {
        $limit = 50;
    }
    
    $leaderboard = $leaderboardService->getLeaderboard($limit, $forceRefresh);
    
    echo json_encode([
        'success' => true,
        'data' => $leaderboard,
        'count' => count($leaderboard),
        'limit' => $limit
    ]);
}

/**
 * Get user's rank
 */
function getUserRank($leaderboardService) {
    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario requerido']);
        return;
    }
    
    $userId = (int)$_GET['user_id'];
    $rank = $leaderboardService->getUserRank($userId);
    
    if ($rank === null) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'Usuario no encontrado en el leaderboard'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'rank' => $rank
            ]
        ]);
    }
}

/**
 * Get user's leaderboard context
 */
function getUserContext($leaderboardService) {
    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario requerido']);
        return;
    }
    
    $userId = (int)$_GET['user_id'];
    $contextSize = isset($_GET['context_size']) ? (int)$_GET['context_size'] : 5;
    
    // Validate context size
    if ($contextSize < 1 || $contextSize > 20) {
        $contextSize = 5;
    }
    
    $context = $leaderboardService->getUserLeaderboardContext($userId, $contextSize);
    
    if ($context === null) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'Usuario no encontrado en el leaderboard'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $context
        ]);
    }
}

/**
 * Get leaderboard statistics
 */
function getLeaderboardStats($leaderboardService) {
    $stats = $leaderboardService->getLeaderboardStats();
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

/**
 * Get leaderboard for specific period
 */
function getLeaderboardForPeriod($leaderboardService) {
    if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Fechas de inicio y fin requeridas']);
        return;
    }
    
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Validate dates
    if (!strtotime($startDate) || !strtotime($endDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato de fecha inválido']);
        return;
    }
    
    // Validate limit
    if ($limit < 1 || $limit > 100) {
        $limit = 50;
    }
    
    $leaderboard = $leaderboardService->getLeaderboardForPeriod($startDate, $endDate, $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $leaderboard,
        'count' => count($leaderboard),
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]
    ]);
}

/**
 * Clear leaderboard cache
 */
function clearLeaderboardCache($leaderboardService) {
    // Verify admin authentication
    $authResult = verifyAuth();
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        return;
    }
    
    if ($authResult['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Permisos insuficientes']);
        return;
    }
    
    $leaderboardService->clearCache();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache del leaderboard limpiado'
    ]);
}

/**
 * Refresh leaderboard
 */
function refreshLeaderboard($leaderboardService) {
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 50;
    
    // Validate limit
    if ($limit < 1 || $limit > 100) {
        $limit = 50;
    }
    
    $leaderboard = $leaderboardService->getLeaderboard($limit, true);
    
    echo json_encode([
        'success' => true,
        'data' => $leaderboard,
        'message' => 'Leaderboard actualizado'
    ]);
}
?>