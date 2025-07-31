<?php
/**
 * Stand Controller
 */

require_once '../models/Stand.php';
require_once '../models/Product.php';
require_once '../models/Event.php';
require_once '../includes/auth.php';

class StandController {
    private $db;
    private $standModel;
    private $productModel;
    private $eventModel;

    public function __construct($database) {
        $this->db = $database;
        $this->standModel = new Stand($this->db);
        $this->productModel = new Product($this->db);
        $this->eventModel = new Event($this->db);
    }

    /**
     * Handle stand creation
     */
    public function createStand() {
        // Check authentication and admin role
        if (!isLoggedIn() || !hasRole('admin')) {
            return ['success' => false, 'errors' => ['No tienes permisos para crear stands']];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Método no permitido']];
        }

        $name = trim($_POST['name'] ?? '');
        $manager_id = $_POST['manager_id'] ?? '';
        $event_id = $_POST['event_id'] ?? '';

        return $this->standModel->create($name, $manager_id, $event_id);
    }

    /**
     * Handle stand update
     */
    public function updateStand($id) {
        // Check authentication and admin role
        if (!isLoggedIn() || !hasRole('admin')) {
            return ['success' => false, 'errors' => ['No tienes permisos para actualizar stands']];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Método no permitido']];
        }

        $name = trim($_POST['name'] ?? '');
        $manager_id = $_POST['manager_id'] ?? '';
        $event_id = $_POST['event_id'] ?? '';

        return $this->standModel->update($id, $name, $manager_id, $event_id);
    }

    /**
     * Handle stand deletion
     */
    public function deleteStand($id) {
        // Check authentication and admin role
        if (!isLoggedIn() || !hasRole('admin')) {
            return ['success' => false, 'errors' => ['No tienes permisos para eliminar stands']];
        }

        return $this->standModel->delete($id);
    }

    /**
     * Get stands with pagination and filters
     */
    public function getStands($page = 1, $limit = 10, $event_id = null, $search = '') {
        return $this->standModel->getAll($page, $limit, $event_id, null, $search);
    }

    /**
     * Get stand by ID
     */
    public function getStandById($id) {
        return $this->standModel->getById($id);
    }

    /**
     * Get available managers
     */
    public function getAvailableManagers() {
        return $this->standModel->getAvailableManagers();
    }

    /**
     * Get active events
     */
    public function getActiveEvents() {
        return $this->eventModel->getActiveEvents();
    }

    /**
     * Handle product creation
     */
    public function createProduct() {
        // Check authentication and appropriate role
        if (!isLoggedIn() || (!hasRole('admin') && !hasRole('stand_manager'))) {
            return ['success' => false, 'errors' => ['No tienes permisos para crear productos']];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Método no permitido']];
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $points_required = $_POST['points_required'] ?? '';
        $stand_id = $_POST['stand_id'] ?? '';

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageResult = $this->productModel->validateProductImage($_FILES['product_image']);
            if (!$imageResult['success']) {
                return ['success' => false, 'errors' => [$imageResult['error']]];
            }
            $image_url = $imageResult['image_url'];
        }

        // If user is stand_manager, verify they manage this stand
        if (hasRole('stand_manager') && !hasRole('admin')) {
            $stand = $this->standModel->getById($stand_id);
            if (!$stand || $stand['manager_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'errors' => ['No tienes permisos para crear productos en este stand']];
            }
        }

        return $this->productModel->create($name, $description, $points_required, $stand_id, $image_url);
    }

    /**
     * Handle product update
     */
    public function updateProduct($id) {
        // Check authentication and appropriate role
        if (!isLoggedIn() || (!hasRole('admin') && !hasRole('stand_manager'))) {
            return ['success' => false, 'errors' => ['No tienes permisos para actualizar productos']];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Método no permitido']];
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $points_required = $_POST['points_required'] ?? '';
        $stand_id = $_POST['stand_id'] ?? '';

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageResult = $this->productModel->validateProductImage($_FILES['product_image']);
            if (!$imageResult['success']) {
                return ['success' => false, 'errors' => [$imageResult['error']]];
            }
            $image_url = $imageResult['image_url'];
        }

        // If user is stand_manager, verify they manage this stand
        if (hasRole('stand_manager') && !hasRole('admin')) {
            $stand = $this->standModel->getById($stand_id);
            if (!$stand || $stand['manager_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'errors' => ['No tienes permisos para actualizar productos en este stand']];
            }
        }

        return $this->productModel->update($id, $name, $description, $points_required, $stand_id, $image_url);
    }

    /**
     * Handle product deletion
     */
    public function deleteProduct($id) {
        // Check authentication and appropriate role
        if (!isLoggedIn() || (!hasRole('admin') && !hasRole('stand_manager'))) {
            return ['success' => false, 'errors' => ['No tienes permisos para eliminar productos']];
        }

        // If user is stand_manager, verify they manage this product's stand
        if (hasRole('stand_manager') && !hasRole('admin')) {
            $product = $this->productModel->getById($id);
            if (!$product || $product['manager_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'errors' => ['No tienes permisos para eliminar este producto']];
            }
        }

        return $this->productModel->delete($id);
    }

    /**
     * Toggle product active status
     */
    public function toggleProductActive($id) {
        // Check authentication and appropriate role
        if (!isLoggedIn() || (!hasRole('admin') && !hasRole('stand_manager'))) {
            return ['success' => false, 'errors' => ['No tienes permisos para cambiar el estado del producto']];
        }

        // If user is stand_manager, verify they manage this product's stand
        if (hasRole('stand_manager') && !hasRole('admin')) {
            $product = $this->productModel->getById($id);
            if (!$product || $product['manager_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'errors' => ['No tienes permisos para cambiar el estado de este producto']];
            }
        }

        return $this->productModel->toggleActive($id);
    }

    /**
     * Get products with pagination and filters
     */
    public function getProducts($page = 1, $limit = 10, $stand_id = null, $search = '') {
        // If user is stand_manager, only show products from their stands
        if (hasRole('stand_manager') && !hasRole('admin')) {
            $userStands = $this->standModel->getByManagerId($_SESSION['user_id']);
            if (empty($userStands)) {
                return [
                    'products' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => 0
                ];
            }
            
            // If stand_id is specified, verify user manages it
            if ($stand_id) {
                $hasAccess = false;
                foreach ($userStands as $stand) {
                    if ($stand['id'] == $stand_id) {
                        $hasAccess = true;
                        break;
                    }
                }
                if (!$hasAccess) {
                    return [
                        'products' => [],
                        'total' => 0,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => 0
                    ];
                }
            }
        }

        return $this->productModel->getAll($page, $limit, $stand_id, false, $search);
    }

    /**
     * Get product by ID
     */
    public function getProductById($id) {
        $product = $this->productModel->getById($id);
        
        // If user is stand_manager, verify they manage this product's stand
        if (hasRole('stand_manager') && !hasRole('admin')) {
            if (!$product || $product['manager_id'] != $_SESSION['user_id']) {
                return false;
            }
        }
        
        return $product;
    }

    /**
     * Get stands managed by current user
     */
    public function getUserStands() {
        if (!isLoggedIn()) {
            return [];
        }

        if (hasRole('admin')) {
            // Admins can see all stands
            $result = $this->standModel->getAll(1, 1000);
            return $result['stands'];
        } elseif (hasRole('stand_manager')) {
            // Stand managers only see their stands
            return $this->standModel->getByManagerId($_SESSION['user_id']);
        }

        return [];
    }
}
?>