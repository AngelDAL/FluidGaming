<?php
/**
 * Tournament controller
 */

require_once '../models/Tournament.php';
require_once '../models/Event.php';
require_once '../services/NotificationService.php';
require_once '../includes/auth.php';

class TournamentController {
    private $db;
    private $tournament;
    private $event;
    private $notificationService;

    public function __construct($database) {
        $this->db = $database;
        $this->tournament = new Tournament($this->db);
        $this->event = new Event($this->db);
        $this->notificationService = new NotificationService($this->db);
    }

    /**
     * Create new tournament
     */
    public function create() {
        // Set content type
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        // Check authentication and admin permissions
        AuthMiddleware::authenticate();
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        // Get form data
        $name = trim($_POST['name'] ?? '');
        $event_id = $_POST['event_id'] ?? '';
        $scheduled_time = $_POST['scheduled_time'] ?? '';
        $points_reward = $_POST['points_reward'] ?? '';
        
        // Process specifications if provided
        $specifications = null;
        if (!empty($_POST['specifications'])) {
            $specifications = $_POST['specifications'];
        }

        // Handle game image upload
        $game_image = null;
        if (isset($_FILES['game_image'])) {
            $imageResult = $this->tournament->validateGameImage($_FILES['game_image']);
            if (!$imageResult['success']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'errors' => [$imageResult['error']]
                ]);
                return;
            }
            $game_image = $imageResult['image_path'];
        }

        // Create tournament
        $result = $this->tournament->create($name, $event_id, $scheduled_time, $points_reward, $specifications, $game_image);
        
        if ($result['success']) {
            // Send notification for new tournament
            $this->notificationService->notifyNewTournament($result['tournament_id'], $name, $scheduled_time);
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Torneo creado exitosamente',
                'tournament_id' => $result['tournament_id']
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
     * Update existing tournament
     */
    public function update() {
        // Set content type
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        // Check authentication and admin permissions
        AuthMiddleware::authenticate();
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Get tournament ID from URL
        $tournamentId = $_GET['id'] ?? null;
        if (!$tournamentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del torneo requerido']);
            return;
        }

        // Handle different request methods
        $input = [];
        $files = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            // For PUT requests, we need to handle multipart data differently
            $input = $_POST; // This won't work for PUT with files, but keeping for compatibility
            $files = $_FILES;
        } else {
            $input = $_POST;
            $files = $_FILES;
        }

        // Validate CSRF token
        if (!validateCSRFToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        // Get form data
        $name = trim($input['name'] ?? '');
        $event_id = $input['event_id'] ?? '';
        $scheduled_time = $input['scheduled_time'] ?? '';
        $points_reward = $input['points_reward'] ?? '';
        
        // Process specifications if provided
        $specifications = null;
        if (!empty($input['specifications'])) {
            $specifications = $input['specifications'];
        }

        // Handle game image upload
        $game_image = null;
        if (isset($files['game_image']) && $files['game_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageResult = $this->tournament->validateGameImage($files['game_image']);
            if (!$imageResult['success']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'errors' => [$imageResult['error']]
                ]);
                return;
            }
            $game_image = $imageResult['image_path'];
        }

        // Update tournament
        $result = $this->tournament->update($tournamentId, $name, $event_id, $scheduled_time, $points_reward, $specifications, $game_image);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Torneo actualizado exitosamente'
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
     * Delete tournament
     */
    public function delete() {
        // Set content type
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        // Check authentication and admin permissions
        AuthMiddleware::authenticate();
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Get tournament ID from URL
        $tournamentId = $_GET['id'] ?? null;
        if (!$tournamentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del torneo requerido']);
            return;
        }

        // Validate CSRF token
        $csrfToken = '';
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            $csrfToken = $input['csrf_token'] ?? '';
        } else {
            $csrfToken = $_POST['csrf_token'] ?? '';
        }

        if (!validateCSRFToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        // Delete tournament
        $result = $this->tournament->delete($tournamentId);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Torneo eliminado exitosamente'
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
     * Get tournament by ID
     */
    public function getById() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        // Get tournament ID from URL
        $tournamentId = $_GET['id'] ?? null;
        if (!$tournamentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del torneo requerido']);
            return;
        }

        $tournamentData = $this->tournament->getById($tournamentId);
        
        if ($tournamentData) {
            echo json_encode([
                'success' => true,
                'tournament' => $tournamentData
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Torneo no encontrado']);
        }
    }

    /**
     * Get tournaments by event ID
     */
    public function getByEvent() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        // Get event ID from URL
        $eventId = $_GET['event_id'] ?? null;
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del evento requerido']);
            return;
        }

        $status = $_GET['status'] ?? null;
        $tournaments = $this->tournament->getByEventId($eventId, $status);
        
        echo json_encode([
            'success' => true,
            'tournaments' => $tournaments
        ]);
    }

    /**
     * Get all tournaments with pagination and filters
     */
    public function getAll() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        // Get query parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
        $event_id = $_GET['event_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = trim($_GET['search'] ?? '');

        $result = $this->tournament->getAll($page, $limit, $event_id, $status, $search);
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Update tournament status
     */
    public function updateStatus() {
        // Set content type
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        // Check authentication and admin permissions
        AuthMiddleware::authenticate();
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Get tournament ID from URL
        $tournamentId = $_GET['id'] ?? null;
        if (!$tournamentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del torneo requerido']);
            return;
        }

        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        $status = $_POST['status'] ?? '';
        
        // Update tournament status
        $result = $this->tournament->updateStatus($tournamentId, $status);
        
        if ($result['success']) {
            // Send notifications based on status change
            $tournamentData = $this->tournament->getById($tournamentId);
            if ($tournamentData) {
                if ($status === 'active') {
                    $this->notificationService->notifyTournamentStarting($tournamentId, $tournamentData['name']);
                } elseif ($status === 'completed') {
                    $this->notificationService->notifyTournamentEnded($tournamentId, $tournamentData['name']);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Estado del torneo actualizado exitosamente'
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
     * Get upcoming tournaments
     */
    public function getUpcoming() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
        $upcomingTournaments = $this->tournament->getUpcoming($limit);
        
        echo json_encode([
            'success' => true,
            'tournaments' => $upcomingTournaments
        ]);
    }

    /**
     * Get active tournaments
     */
    public function getActive() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        $activeTournaments = $this->tournament->getActive();
        
        echo json_encode([
            'success' => true,
            'tournaments' => $activeTournaments
        ]);
    }

    /**
     * Handle API routing
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $this->create();
                break;
            case 'update':
                $this->update();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'get':
                $this->getById();
                break;
            case 'list':
                $this->getAll();
                break;
            case 'by-event':
                $this->getByEvent();
                break;
            case 'update-status':
                $this->updateStatus();
                break;
            case 'upcoming':
                $this->getUpcoming();
                break;
            case 'active':
                $this->getActive();
                break;
            default:
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado']);
                break;
        }
    }
}
?>