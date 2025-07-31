<?php
/**
 * Authentication and authorization utilities
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        if (isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Autenticación requerida']);
            exit();
        } else {
            header('Location: index.php?page=login');
            exit();
        }
    }
}

/**
 * Check if user has required role
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? 'user';
    
    $role_hierarchy = [
        'user' => 1,
        'assistant' => 2,
        'stand_manager' => 3,
        'admin' => 4
    ];
    
    return isset($role_hierarchy[$user_role]) && 
           isset($role_hierarchy[$required_role]) &&
           $role_hierarchy[$user_role] >= $role_hierarchy[$required_role];
}

/**
 * Require user to have specific role
 */
function requireRole($required_role) {
    requireLogin();
    
    if (!hasRole($required_role)) {
        if (isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            exit();
        } else {
            http_response_code(403);
            echo "Acceso denegado: Permisos insuficientes";
            exit();
        }
    }
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nickname' => $_SESSION['user_nickname'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}

/**
 * Check if user can assign points (assistant or admin)
 */
function canAssignPoints() {
    return hasRole('assistant');
}

/**
 * Check if user can manage stands (stand_manager or admin)
 */
function canManageStands() {
    return hasRole('stand_manager');
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Validate session security
 */
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check session timeout (2 hours)
    $timeout = 2 * 60 * 60; // 2 hours in seconds
    
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > $timeout) {
        destroySession();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    return true;
}

/**
 * Destroy session completely
 */
function destroySession() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Middleware for API endpoints
 */
class AuthMiddleware {
    
    /**
     * Check authentication for API endpoints
     */
    public static function authenticate() {
        if (!validateSession()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Sesión expirada o inválida']);
            exit();
        }
    }
    
    /**
     * Check role authorization for API endpoints
     */
    public static function authorize($required_role) {
        self::authenticate();
        
        if (!hasRole($required_role)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            exit();
        }
    }
    
    /**
     * Check if user can assign points
     */
    public static function requirePointsAssignmentPermission() {
        self::authorize('assistant');
    }
    
    /**
     * Check if user can manage stands
     */
    public static function requireStandManagementPermission() {
        self::authorize('stand_manager');
    }
    
    /**
     * Check if user is admin
     */
    public static function requireAdminPermission() {
        self::authorize('admin');
    }
}

/**
 * CSRF Protection
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting for login attempts
 */
function checkLoginAttempts($identifier) {
    $key = 'login_attempts_' . md5($identifier);
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'last_attempt' => 0];
    
    // Reset attempts after 15 minutes
    if (time() - $attempts['last_attempt'] > 900) {
        $attempts = ['count' => 0, 'last_attempt' => 0];
    }
    
    // Check if too many attempts
    if ($attempts['count'] >= 5) {
        return false;
    }
    
    return true;
}

function recordLoginAttempt($identifier, $success = false) {
    $key = 'login_attempts_' . md5($identifier);
    
    if ($success) {
        // Clear attempts on successful login
        unset($_SESSION[$key]);
    } else {
        // Increment failed attempts
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'last_attempt' => 0];
        $attempts['count']++;
        $attempts['last_attempt'] = time();
        $_SESSION[$key] = $attempts;
    }
}
?>