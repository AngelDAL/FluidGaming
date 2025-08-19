<?php
/**
 * General configuration settings
 */

// Environment settings
define('ENVIRONMENT', 'development'); // development, production

// Application settings
define('APP_NAME', 'Sistema de Puntos y Torneos');
define('APP_VERSION', '1.0.0');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', 'uploads/');

// Session settings (solo si la sesión NO está activa)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 en producción con HTTPS
}

// Error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Mexico_City');
?>