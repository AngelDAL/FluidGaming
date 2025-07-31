<?php
/**
 * Environment configuration for production deployment
 * Task 13.2: Configure environment variables for production
 */

// Load environment variables from .env file if it exists
function loadEnvironmentVariables() {
    $envFile = __DIR__ . '/../.env';
    
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load environment variables
loadEnvironmentVariables();

// Environment detection
function getEnvironment() {
    return $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'development';
}

// Database configuration
function getDatabaseConfig() {
    $environment = getEnvironment();
    
    if ($environment === 'production') {
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'dbname' => $_ENV['DB_NAME'] ?? 'tournament_points',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];
    } else {
        // Development configuration
        return [
            'host' => 'localhost',
            'dbname' => 'tournament_points',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];
    }
}

// Application configuration
function getAppConfig() {
    $environment = getEnvironment();
    
    return [
        'name' => $_ENV['APP_NAME'] ?? 'Sistema de Puntos y Torneos',
        'version' => $_ENV['APP_VERSION'] ?? '1.0.0',
        'environment' => $environment,
        'debug' => $environment !== 'production' && ($_ENV['APP_DEBUG'] ?? 'true') === 'true',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City',
        'locale' => $_ENV['APP_LOCALE'] ?? 'es',
        
        // Security settings
        'session_secure' => $environment === 'production',
        'session_httponly' => true,
        'session_samesite' => 'Strict',
        'csrf_protection' => true,
        
        // File upload settings
        'max_file_size' => (int)($_ENV['MAX_FILE_SIZE'] ?? 5242880), // 5MB
        'allowed_image_types' => explode(',', $_ENV['ALLOWED_IMAGE_TYPES'] ?? 'jpg,jpeg,png,gif'),
        'upload_path' => $_ENV['UPLOAD_PATH'] ?? 'uploads/',
        
        // Cache settings
        'cache_enabled' => ($_ENV['CACHE_ENABLED'] ?? 'true') === 'true',
        'cache_default_ttl' => (int)($_ENV['CACHE_DEFAULT_TTL'] ?? 300),
        'cache_path' => $_ENV['CACHE_PATH'] ?? 'cache/',
        
        // Logging settings
        'log_enabled' => ($_ENV['LOG_ENABLED'] ?? 'true') === 'true',
        'log_level' => $_ENV['LOG_LEVEL'] ?? ($environment === 'production' ? 'error' : 'debug'),
        'log_path' => $_ENV['LOG_PATH'] ?? 'logs/',
        'log_max_files' => (int)($_ENV['LOG_MAX_FILES'] ?? 30),
        
        // Email settings (for future notifications)
        'mail_enabled' => ($_ENV['MAIL_ENABLED'] ?? 'false') === 'true',
        'mail_host' => $_ENV['MAIL_HOST'] ?? '',
        'mail_port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'mail_username' => $_ENV['MAIL_USERNAME'] ?? '',
        'mail_password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'mail_encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'mail_from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@tournament.com',
        'mail_from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Torneos',
        
        // Rate limiting
        'rate_limit_enabled' => ($_ENV['RATE_LIMIT_ENABLED'] ?? 'true') === 'true',
        'rate_limit_requests' => (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100),
        'rate_limit_window' => (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 3600), // 1 hour
        
        // Backup settings
        'backup_enabled' => ($_ENV['BACKUP_ENABLED'] ?? 'false') === 'true',
        'backup_path' => $_ENV['BACKUP_PATH'] ?? 'backups/',
        'backup_retention_days' => (int)($_ENV['BACKUP_RETENTION_DAYS'] ?? 30),
    ];
}

// Security headers for production
function setSecurityHeaders() {
    if (getEnvironment() === 'production') {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (basic)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
        
        // HSTS (if using HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// Initialize environment
function initializeEnvironment() {
    $config = getAppConfig();
    
    // Set timezone
    date_default_timezone_set($config['timezone']);
    
    // Configure error reporting
    if ($config['debug']) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
    } else {
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
    }
    
    // Configure session
    ini_set('session.cookie_httponly', $config['session_httponly'] ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', $config['session_secure'] ? 1 : 0);
    ini_set('session.cookie_samesite', $config['session_samesite']);
    
    // Set security headers
    setSecurityHeaders();
    
    // Create necessary directories
    $directories = [
        $config['upload_path'],
        $config['cache_path'],
        $config['log_path']
    ];
    
    if ($config['backup_enabled']) {
        $directories[] = $config['backup_path'];
    }
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Get database connection with environment configuration
function getDatabaseConnection() {
    static $connection = null;
    
    if ($connection === null) {
        $config = getDatabaseConfig();
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            if (getEnvironment() === 'production') {
                error_log("Database connection failed: " . $e->getMessage());
                die('Database connection failed. Please try again later.');
            } else {
                die("Database connection failed: " . $e->getMessage());
            }
        }
    }
    
    return $connection;
}

// Initialize environment on include
initializeEnvironment();
?>