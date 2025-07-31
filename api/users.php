<?php
/**
 * User API endpoints
 */

// Include required files
require_once '../config/database.php';
require_once '../controllers/UserController.php';
require_once '../includes/middleware.php';

try {
    // Apply basic middleware
    Middleware::cors();
    Middleware::json();
    
    // Apply rate limiting for login attempts
    $action = $_GET['action'] ?? '';
    if ($action === 'login') {
        Middleware::rateLimit(10, 900); // 10 attempts per 15 minutes
    }
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Initialize controller
    $userController = new UserController($db);
    
    // Handle request
    $userController->handleRequest();
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>