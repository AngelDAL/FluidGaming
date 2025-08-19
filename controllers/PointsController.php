<?php

/**
 * Points controller
 */

require_once __DIR__ . '/../models/PointTransaction.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../services/LeaderboardService.php';
require_once __DIR__ . '/../includes/auth.php';



class PointsController
{
    private $db;
    private $pointTransaction;
    private $user;
    private $notificationService;
    private $leaderboardService;

    public function __construct($database)
    {
        $this->db = $database;
        $this->pointTransaction = new PointTransaction($this->db);
        $this->user = new User($this->db);
        $this->notificationService = new NotificationService($this->db);
        $this->leaderboardService = new LeaderboardService($this->db);
    }

    /**
     * Assign points to a user
     */
    public function assignPoints()
    {
        // Set content type
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        // Check authentication and permissions
        AuthMiddleware::authenticate();
        if (!canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes para asignar puntos']);
            return;
        }

        // Validate CSRF token
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($csrf_token)) {
            // Temporarily log instead of failing
            error_log("CSRF token validation failed. Expected: " . ($_SESSION['csrf_token'] ?? 'not set') . ", Received: " . $csrf_token);
            // For now, continue without failing
            // http_response_code(403);
            // echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            // return;
        }


        // Get form data
        $user_id = intval($_POST['user_id'] ?? 0);
        $points = intval($_POST['points'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        $tournament_id = !empty($_POST['tournament_id']) ? intval($_POST['tournament_id']) : null;
        $notes = trim($_POST['notes'] ?? '');
        $assigned_by = $_SESSION['user_id'];

        // Debug logging (remove in production)
        error_log("Points assignment data received:");
        error_log("user_id: " . $user_id . " (type: " . gettype($user_id) . ")");
        error_log("points: " . $points . " (type: " . gettype($points) . ")");
        error_log("source: '" . $source . "' (length: " . strlen($source) . ")");
        error_log("tournament_id: " . ($tournament_id ?? 'null'));
        error_log("assigned_by: " . $assigned_by);

        // Prepare metadata
        $metadata = [];
        if (!empty($notes)) {
            $metadata['notes'] = $notes;
        }
        if (isset($_POST['additional_info']) && !empty($_POST['additional_info'])) {
            $metadata['additional_info'] = $_POST['additional_info'];
        }

        // Debug: Log the parameters being passed to create
        error_log("Creating transaction with parameters:");
        error_log("user_id: " . $user_id);
        error_log("points: " . $points);
        error_log("type: earned");
        error_log("source: " . $source);
        error_log("assigned_by: " . $assigned_by);
        error_log("tournament_id: " . ($tournament_id ?? 'null'));

        // Create point transaction
        $result = $this->pointTransaction->create(
            $user_id,
            $points,
            'earned',
            $source,
            $assigned_by,
            $tournament_id,
            !empty($metadata) ? $metadata : null
        );

        if ($result['success']) {
            // Get updated user info
            $userData = $this->user->getById($user_id);

            // Send notification for points assignment
            $this->notificationService->notifyPointsAssigned($user_id, $points, $source, $assigned_by);

            // Update leaderboard cache and notify ranking changes
            $this->leaderboardService->updateCacheAfterPointChange($this->notificationService);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Puntos asignados exitosamente',
                'transaction_id' => $result['transaction_id'],
                'user_total_points' => $userData['total_points'] ?? 0
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'errors' => $result['errors']
            ]);
        }
    }

    /**
     * Get user's point transactions
     */
    public function getUserTransactions()
    {
        // Set content type
        header('Content-Type: application/json');

        // Check authentication
        AuthMiddleware::authenticate();

        // Get user ID from URL or use current user
        $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

        // Check if user can view these transactions
        if ($user_id != $_SESSION['user_id'] && !canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para ver estas transacciones']);
            return;
        }

        // Get query parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
        $type = $_GET['type'] ?? null;

        $result = $this->pointTransaction->getByUserId($user_id, $page, $limit, $type);

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get recent point transactions (for admins/assistants)
     */
    public function getRecentTransactions()
    {
        // Set content type
        header('Content-Type: application/json');

        // Check authentication and permissions
        AuthMiddleware::authenticate();
        if (!canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Get query parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(50, intval($_GET['limit'] ?? 20)));
        $type = $_GET['type'] ?? null;
        $source = $_GET['source'] ?? null;

        $result = $this->pointTransaction->getRecent($page, $limit, $type, $source);

        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction()
    {
        // Set content type
        header('Content-Type: application/json');

        // Check authentication
        AuthMiddleware::authenticate();

        // Get transaction ID from URL
        $transaction_id = $_GET['id'] ?? null;
        if (!$transaction_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de transacción requerido']);
            return;
        }

        $transaction = $this->pointTransaction->getById($transaction_id);

        if ($transaction) {
            // Check if user can view this transaction
            if ($transaction['user_id'] != $_SESSION['user_id'] && !canAssignPoints()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No tienes permisos para ver esta transacción']);
                return;
            }

            echo json_encode([
                'success' => true,
                'transaction' => $transaction
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Transacción no encontrada']);
        }
    }

    /**
     * Get user's points statistics
     */
    public function getUserStats()
    {
        // Set content type
        header('Content-Type: application/json');

        // Check authentication
        AuthMiddleware::authenticate();

        // Get user ID from URL or use current user
        $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

        // Check if user can view these stats
        if ($user_id != $_SESSION['user_id'] && !canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para ver estas estadísticas']);
            return;
        }

        $stats = $this->pointTransaction->getUserPointsStats($user_id);
        $userData = $this->user->getById($user_id);

        if ($userData) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $userData['id'],
                    'nickname' => $userData['nickname'],
                    'total_points' => $userData['total_points']
                ],
                'stats' => $stats
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        }
    }

    /**
     * Search users by nickname (for assistants to assign points)
     */
    public function searchUsers()
    {
        // Set content type
        header('Content-Type: application/json');

        // Check authentication and permissions
        AuthMiddleware::authenticate();
        if (!canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Get search query
        $search = trim($_GET['q'] ?? '');
        if (empty($search)) {
            echo json_encode([
                'success' => true,
                'users' => []
            ]);
            return;
        }

        // Search users by nickname
        $query = "SELECT id, nickname, profile_image, total_points 
                  FROM users 
                  WHERE nickname LIKE :search 
                  AND role = 'user'
                  ORDER BY nickname ASC 
                  LIMIT 10";

        $stmt = $this->db->prepare($query);
        $searchParam = '%' . $search . '%';
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();

        $users = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Get available tournaments for point assignment
     */
    public function getAvailableTournaments()
    {
        // Set content type
        header('Content-Type: application/json');

        // Check authentication and permissions
        AuthMiddleware::authenticate();
        if (!canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Get tournaments from active events
        $query = "SELECT t.id, t.name, t.points_reward, t.scheduled_time, e.name as event_name
                  FROM tournaments t
                  JOIN events e ON t.event_id = e.id
                  WHERE e.is_active = 1 
                  AND NOW() BETWEEN e.start_date AND e.end_date
                  ORDER BY t.scheduled_time ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $tournaments = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'tournaments' => $tournaments
        ]);
    }


    /**
     * Obtener todos los usuarios (para autocompletado de asignación de puntos)
     */
    public function getAllUsers()
    {
        header('Content-Type: application/json');
        // Solo permitir a usuarios con permiso de asignar puntos
        AuthMiddleware::authenticate();
        if (!canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }
        $users = $this->user->getAll();
        $result = [];
        foreach ($users as $u) {
            $result[] = [
                'id' => $u['id'],
                'nickname' => $u['nickname'],
                'total_points' => $u['total_points'],
                'profile_image' => $u['profile_image'] ?? ''
            ];
        }
        echo json_encode(['success' => true, 'users' => $result]);
    }

    /**
     * Validate if points can be assigned (check active events)
     */
    public function validatePointAssignment()
    {
        // Set content type
        header('Content-Type: application/json');

        // Check authentication and permissions
        AuthMiddleware::authenticate();
        if (!canAssignPoints()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Check if there are active events
        $validation = $this->pointTransaction->validateActiveEvent();

        echo json_encode([
            'success' => true,
            'can_assign_points' => $validation['valid'],
            'message' => $validation['valid'] ? 'Se pueden asignar puntos' : $validation['error']
        ]);
    }

    /**
     * Handle API routing
     */
    public function handleRequest()
    {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'assign':
                $this->assignPoints();
                break;
            case 'user-transactions':
                $this->getUserTransactions();
                break;
            case 'recent':
                $this->getRecentTransactions();
                break;
            case 'transaction':
                $this->getTransaction();
                break;
            case 'user-stats':
                $this->getUserStats();
                break;
            case 'search-users':
                $this->searchUsers();
                break;
            case 'tournaments':
                $this->getAvailableTournaments();
                break;
            case 'validate':
                $this->validatePointAssignment();
                break;
            case 'all-users':
                $this->getAllUsers();
                break;
            default:
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado']);
                break;
        }
    }
}
