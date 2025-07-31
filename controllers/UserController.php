<?php
/**
 * User controller
 */

require_once '../models/User.php';
require_once '../includes/auth.php';

class UserController {
    private $db;
    private $user;

    public function __construct($database) {
        $this->db = $database;
        $this->user = new User($this->db);
    }

    /**
     * Handle user registration
     */
    public function register() {
        // Set content type
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        // Get form data
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $profile_image = $_FILES['profile_image'] ?? null;

        // Create user
        $result = $this->user->create($nickname, $email, $password, $profile_image);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'user_id' => $result['user_id']
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
     * Handle user login
     */
    public function login() {
        // Set content type
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        // Get form data
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Email y contraseña son requeridos'
            ]);
            return;
        }

        // Check rate limiting
        if (!checkLoginAttempts($email)) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Demasiados intentos de login. Intenta de nuevo en 15 minutos.'
            ]);
            return;
        }

        // Authenticate user
        $result = $this->user->authenticate($email, $password);
        
        if ($result['success']) {
            // Record successful login
            recordLoginAttempt($email, true);
            
            // Start session and store user data
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['user_nickname'] = $result['user']['nickname'];
            $_SESSION['user_role'] = $result['user']['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['created'] = time();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $result['user']['id'],
                    'nickname' => $result['user']['nickname'],
                    'email' => $result['user']['email'],
                    'profile_image' => $result['user']['profile_image'],
                    'role' => $result['user']['role'],
                    'total_points' => $result['user']['total_points']
                ],
                'csrf_token' => generateCSRFToken()
            ]);
        } else {
            // Record failed login attempt
            recordLoginAttempt($email, false);
            
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        }
    }

    /**
     * Handle user logout
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        destroySession();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Get current user profile
     */
    public function getProfile() {
        // Use middleware to check authentication
        AuthMiddleware::authenticate();

        $userData = $this->user->getById($_SESSION['user_id']);
        
        if ($userData) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'user' => $userData,
                'csrf_token' => generateCSRFToken()
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        }
    }

    /**
     * Check session status
     */
    public function checkSession() {
        header('Content-Type: application/json');
        
        if (validateSession()) {
            $user = getCurrentUser();
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'user' => $user,
                'csrf_token' => generateCSRFToken()
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'authenticated' => false
            ]);
        }
    }

    /**
     * Check user permissions
     */
    public function checkPermissions() {
        AuthMiddleware::authenticate();
        
        $user = getCurrentUser();
        $permissions = [
            'can_assign_points' => canAssignPoints(),
            'can_manage_stands' => canManageStands(),
            'is_admin' => isAdmin()
        ];
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'user' => $user,
            'permissions' => $permissions
        ]);
    }

    /**
     * Handle API routing
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'register':
                $this->register();
                break;
            case 'login':
                $this->login();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'profile':
                $this->getProfile();
                break;
            case 'session':
                $this->checkSession();
                break;
            case 'permissions':
                $this->checkPermissions();
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