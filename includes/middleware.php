<?php
/**
 * Middleware utilities for API endpoints
 */

require_once 'auth.php';

/**
 * Base middleware class
 */
class Middleware {
    
    /**
     * Apply multiple middlewares
     */
    public static function apply($middlewares) {
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                call_user_func($middleware);
            } elseif (method_exists('Middleware', $middleware)) {
                call_user_func(['Middleware', $middleware]);
            }
        }
    }
    
    /**
     * CORS middleware
     */
    public static function cors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * JSON response middleware
     */
    public static function json() {
        header('Content-Type: application/json');
    }
    
    /**
     * Method validation middleware
     */
    public static function method($allowed_methods) {
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit();
        }
    }
    
    /**
     * CSRF protection middleware
     */
    public static function csrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!validateCSRFToken($token)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
                exit();
            }
        }
    }
    
    /**
     * Rate limiting middleware
     */
    public static function rateLimit($max_requests = 60, $window = 3600) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'rate_limit_' . md5($ip);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $requests = $_SESSION[$key] ?? ['count' => 0, 'window_start' => time()];
        
        // Reset window if expired
        if (time() - $requests['window_start'] > $window) {
            $requests = ['count' => 0, 'window_start' => time()];
        }
        
        $requests['count']++;
        $_SESSION[$key] = $requests;
        
        if ($requests['count'] > $max_requests) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Límite de solicitudes excedido']);
            exit();
        }
    }
}

/**
 * Role-based middleware functions
 */
function requireUser() {
    AuthMiddleware::authenticate();
}

function requireAssistant() {
    AuthMiddleware::requirePointsAssignmentPermission();
}

function requireStandManager() {
    AuthMiddleware::requireStandManagementPermission();
}

function requireAdmin() {
    AuthMiddleware::requireAdminPermission();
}

/**
 * Event-based middleware
 */
function requireActiveEvent() {
    // This will be implemented when Event model is available
    // For now, just ensure user is authenticated
    AuthMiddleware::authenticate();
}

/**
 * Validation middleware
 */
function validateInput($rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $_POST[$field] ?? '';
        
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[] = "El campo {$field} es requerido";
            continue;
        }
        
        if (!empty($value)) {
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[] = "El campo {$field} debe tener al menos {$rule['min_length']} caracteres";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[] = "El campo {$field} no puede tener más de {$rule['max_length']} caracteres";
            }
            
            if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "El campo {$field} debe ser un email válido";
            }
            
            if (isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
                $errors[] = "El campo {$field} debe ser numérico";
            }
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit();
    }
}
?>