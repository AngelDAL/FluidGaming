<?php
/**
 * Notifications API endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../services/NotificationService.php';
require_once '../includes/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $notificationService = new NotificationService($db);
    
    // Check authentication
    AuthMiddleware::authenticate();
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            getUserNotifications($notificationService);
            break;
        case 'unread-count':
            getUnreadCount($notificationService);
            break;
        case 'mark-read':
            markAsRead($notificationService);
            break;
        case 'mark-all-read':
            markAllAsRead($notificationService);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}

/**
 * Get user notifications
 */
function getUserNotifications($notificationService) {
    $userId = $_SESSION['user_id'];
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(50, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    try {
        $notifications = $notificationService->getUserNotifications($userId, $limit, $offset);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'has_more' => count($notifications) === $limit
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener notificaciones',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Get unread notifications count
 */
function getUnreadCount($notificationService) {
    $userId = $_SESSION['user_id'];
    
    try {
        $count = $notificationService->getUnreadCount($userId);
        
        echo json_encode([
            'success' => true,
            'unread_count' => $count
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener contador de notificaciones',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Mark notification as read
 */
function markAsRead($notificationService) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $notificationId = $_POST['notification_id'] ?? null;
    
    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de notificación requerido']);
        return;
    }
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
        return;
    }
    
    try {
        $result = $notificationService->markAsRead($notificationId, $userId);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No se pudo marcar la notificación como leída'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al marcar notificación como leída',
            'details' => $e->getMessage()
        ]);
    }
}

/**
 * Mark all notifications as read
 */
function markAllAsRead($notificationService) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
        return;
    }
    
    try {
        $result = $notificationService->markAllAsRead($userId);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No se pudieron marcar las notificaciones como leídas'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al marcar notificaciones como leídas',
            'details' => $e->getMessage()
        ]);
    }
}
?>