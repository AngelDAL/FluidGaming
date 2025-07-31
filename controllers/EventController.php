<?php
/**
 * Event controller
 */

require_once '../models/Event.php';
require_once '../includes/auth.php';

class EventController {
    private $db;
    private $event;

    public function __construct($database) {
        $this->db = $database;
        $this->event = new Event($this->db);
    }

    /**
     * Create new event
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
        $description = trim($_POST['description'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $created_by = $_SESSION['user_id'];

        // Create event
        $result = $this->event->create($name, $description, $start_date, $end_date, $created_by);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Evento creado exitosamente',
                'event_id' => $result['event_id']
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
     * Update existing event
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

        // Get event ID from URL
        $eventId = $_GET['id'] ?? null;
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del evento requerido']);
            return;
        }

        // Get input data
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }

        // Validate CSRF token
        if (!validateCSRFToken($input['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        // Get form data
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $start_date = $input['start_date'] ?? '';
        $end_date = $input['end_date'] ?? '';

        // Update event
        $result = $this->event->update($eventId, $name, $description, $start_date, $end_date);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Evento actualizado exitosamente'
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
     * Delete event
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

        // Get event ID from URL
        $eventId = $_GET['id'] ?? null;
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del evento requerido']);
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

        // Delete event
        $result = $this->event->delete($eventId);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Evento eliminado exitosamente'
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
     * Get event by ID
     */
    public function getById() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        // Get event ID from URL
        $eventId = $_GET['id'] ?? null;
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del evento requerido']);
            return;
        }

        $eventData = $this->event->getById($eventId);
        
        if ($eventData) {
            echo json_encode([
                'success' => true,
                'event' => $eventData
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Evento no encontrado']);
        }
    }

    /**
     * Get all events with pagination and search
     */
    public function getAll() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        // Get query parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
        $search = trim($_GET['search'] ?? '');

        $result = $this->event->getAll($page, $limit, $search);
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get active events
     */
    public function getActive() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        $activeEvents = $this->event->getActiveEvents();
        
        echo json_encode([
            'success' => true,
            'events' => $activeEvents
        ]);
    }

    /**
     * Check if event is active
     */
    public function checkActive() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        // Get event ID from URL
        $eventId = $_GET['id'] ?? null;
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del evento requerido']);
            return;
        }

        $isActive = $this->event->isEventActive($eventId);
        
        echo json_encode([
            'success' => true,
            'is_active' => $isActive
        ]);
    }

    /**
     * Toggle event active status
     */
    public function toggleActive() {
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

        // Get event ID from URL
        $eventId = $_GET['id'] ?? null;
        if (!$eventId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del evento requerido']);
            return;
        }

        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        // Toggle active status
        $result = $this->event->toggleActive($eventId);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Estado del evento actualizado exitosamente'
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
     * Get upcoming events
     */
    public function getUpcoming() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        $limit = max(1, min(20, intval($_GET['limit'] ?? 5)));
        $upcomingEvents = $this->event->getUpcomingEvents($limit);
        
        echo json_encode([
            'success' => true,
            'events' => $upcomingEvents
        ]);
    }

    /**
     * Get events by date range
     */
    public function getByDateRange() {
        // Set content type
        header('Content-Type: application/json');
        
        // Check authentication
        AuthMiddleware::authenticate();

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        if (empty($startDate) || empty($endDate)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Fechas de inicio y fin requeridas']);
            return;
        }

        $events = $this->event->getEventsByDateRange($startDate, $endDate);
        
        echo json_encode([
            'success' => true,
            'events' => $events
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
            case 'active':
                $this->getActive();
                break;
            case 'check-active':
                $this->checkActive();
                break;
            case 'toggle-active':
                $this->toggleActive();
                break;
            case 'upcoming':
                $this->getUpcoming();
                break;
            case 'date-range':
                $this->getByDateRange();
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