<?php
/**
 * Claim Controller
 */

require_once '../config/database.php';
require_once '../models/Claim.php';
require_once '../models/User.php';
require_once '../models/Product.php';
require_once '../models/Stand.php';
require_once '../includes/auth.php';

class ClaimController {
    private $db;
    private $claim;
    private $user;
    private $product;
    private $stand;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->claim = new Claim($this->db);
        $this->user = new User($this->db);
        $this->product = new Product($this->db);
        $this->stand = new Stand($this->db);
    }

    /**
     * Create a new claim
     */
    public function createClaim() {
        // Check authentication
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        // Validate required fields
        $required_fields = ['user_id', 'product_id', 'stand_id'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Campo requerido: $field"]);
                return;
            }
        }

        $user_id = $_POST['user_id'];
        $product_id = $_POST['product_id'];
        $stand_id = $_POST['stand_id'];
        $processed_by = isset($_POST['processed_by']) ? $_POST['processed_by'] : null;

        // Create claim
        $result = $this->claim->create($user_id, $product_id, $stand_id, $processed_by);

        if ($result['success']) {
            http_response_code(201);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * Process a claim (mark as completed)
     */
    public function processClaim() {
        // Check authentication and authorization
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        if (!in_array($current_user['role'], ['stand_manager', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Validate required fields
        if (!isset($_POST['claim_id']) || empty($_POST['claim_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del reclamo es requerido']);
            return;
        }

        $claim_id = $_POST['claim_id'];
        $processed_by = $current_user['id'];

        // Verify the claim belongs to a stand managed by current user (unless admin)
        if ($current_user['role'] !== 'admin') {
            $claim_details = $this->claim->getById($claim_id);
            if (!$claim_details) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Reclamo no encontrado']);
                return;
            }

            $stand_details = $this->stand->getById($claim_details['stand_id']);
            if (!$stand_details || $stand_details['manager_id'] != $current_user['id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No tienes permisos para procesar este reclamo']);
                return;
            }
        }

        // Process claim
        $result = $this->claim->processClaim($claim_id, $processed_by);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * Verify user points for a product
     */
    public function verifyUserPoints() {
        // Check authentication and authorization
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        if (!in_array($current_user['role'], ['stand_manager', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        // Validate required fields
        $required_fields = ['user_id', 'product_id'];
        foreach ($required_fields as $field) {
            if (!isset($_GET[$field]) || empty($_GET[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Campo requerido: $field"]);
                return;
            }
        }

        $user_id = $_GET['user_id'];
        $product_id = $_GET['product_id'];

        // Check if user has already claimed this product
        if ($this->claim->hasUserClaimedProduct($user_id, $product_id)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'El usuario ya ha reclamado este producto',
                'already_claimed' => true
            ]);
            return;
        }

        // Verify points
        $points_info = $this->claim->verifyUserPoints($user_id, $product_id);

        if ($points_info) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'points_info' => $points_info
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Usuario o producto no encontrado']);
        }
    }

    /**
     * Get claim by ID
     */
    public function getClaim() {
        // Check authentication
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        if (!isset($_GET['id']) || empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del reclamo es requerido']);
            return;
        }

        $claim_id = $_GET['id'];
        $claim = $this->claim->getById($claim_id);

        if ($claim) {
            http_response_code(200);
            echo json_encode(['success' => true, 'claim' => $claim]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Reclamo no encontrado']);
        }
    }

    /**
     * Get claims by user
     */
    public function getUserClaims() {
        // Check authentication
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $current_user['id'];
        $status = isset($_GET['status']) ? $_GET['status'] : null;

        // Users can only see their own claims unless they're admin
        if ($current_user['role'] !== 'admin' && $user_id != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para ver estos reclamos']);
            return;
        }

        $claims = $this->claim->getByUserId($user_id, $status);

        http_response_code(200);
        echo json_encode(['success' => true, 'claims' => $claims]);
    }

    /**
     * Get claims by stand
     */
    public function getStandClaims() {
        // Check authentication and authorization
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        if (!in_array($current_user['role'], ['stand_manager', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        if (!isset($_GET['stand_id']) || empty($_GET['stand_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del stand es requerido']);
            return;
        }

        $stand_id = $_GET['stand_id'];
        $status = isset($_GET['status']) ? $_GET['status'] : null;

        // Verify the stand is managed by current user (unless admin)
        if ($current_user['role'] !== 'admin') {
            $stand_details = $this->stand->getById($stand_id);
            if (!$stand_details || $stand_details['manager_id'] != $current_user['id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No tienes permisos para ver los reclamos de este stand']);
                return;
            }
        }

        $claims = $this->claim->getByStandId($stand_id, $status);

        http_response_code(200);
        echo json_encode(['success' => true, 'claims' => $claims]);
    }

    /**
     * Get all claims with pagination
     */
    public function getAllClaims() {
        // Check authentication and authorization
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        if ($current_user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo los administradores pueden ver todos los reclamos']);
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $stand_id = isset($_GET['stand_id']) ? $_GET['stand_id'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        $result = $this->claim->getAll($page, $limit, $stand_id, $status, $user_id);

        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $result]);
    }

    /**
     * Get pending claims for current stand manager
     */
    public function getPendingClaims() {
        // Check authentication and authorization
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        if (!in_array($current_user['role'], ['stand_manager', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        $claims = $this->claim->getPendingClaimsForManager($current_user['id']);

        http_response_code(200);
        echo json_encode(['success' => true, 'claims' => $claims]);
    }

    /**
     * Get claim statistics
     */
    public function getClaimStats() {
        // Check authentication
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        $stand_id = isset($_GET['stand_id']) ? $_GET['stand_id'] : null;
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

        // Authorization checks
        if ($stand_id && $current_user['role'] !== 'admin') {
            $stand_details = $this->stand->getById($stand_id);
            if (!$stand_details || $stand_details['manager_id'] != $current_user['id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No tienes permisos para ver las estadísticas de este stand']);
                return;
            }
        }

        if ($user_id && $current_user['role'] !== 'admin' && $user_id != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para ver las estadísticas de este usuario']);
            return;
        }

        $stats = $this->claim->getClaimStats($stand_id, $user_id);

        http_response_code(200);
        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    /**
     * Cancel a claim
     */
    public function cancelClaim() {
        // Check authentication
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        if (!isset($_POST['claim_id']) || empty($_POST['claim_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID del reclamo es requerido']);
            return;
        }

        $claim_id = $_POST['claim_id'];
        $current_user = getCurrentUser();

        $result = $this->claim->cancelClaim($claim_id, $current_user['id']);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * Search users by nickname for claim creation
     */
    public function searchUsers() {
        // Check authentication and authorization
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $current_user = getCurrentUser();
        if (!in_array($current_user['role'], ['stand_manager', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        if (strlen($search) < 2) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Mínimo 2 caracteres para buscar']);
            return;
        }

        $query = "SELECT id, nickname, total_points, profile_image 
                  FROM users 
                  WHERE nickname LIKE :search AND role = 'user'
                  ORDER BY nickname ASC 
                  LIMIT 10";
        
        $stmt = $this->db->prepare($query);
        $searchParam = '%' . $search . '%';
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        
        $users = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode(['success' => true, 'users' => $users]);
    }
}

// Handle the request
$controller = new ClaimController();

// Get the action from the request
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($action) {
            case 'get':
                $controller->getClaim();
                break;
            case 'user_claims':
                $controller->getUserClaims();
                break;
            case 'stand_claims':
                $controller->getStandClaims();
                break;
            case 'all':
                $controller->getAllClaims();
                break;
            case 'pending':
                $controller->getPendingClaims();
                break;
            case 'stats':
                $controller->getClaimStats();
                break;
            case 'verify_points':
                $controller->verifyUserPoints();
                break;
            case 'search_users':
                $controller->searchUsers();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
                break;
        }
        break;
    
    case 'POST':
        switch ($action) {
            case 'create':
                $controller->createClaim();
                break;
            case 'process':
                $controller->processClaim();
                break;
            case 'cancel':
                $controller->cancelClaim();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
                break;
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        break;
}
?>