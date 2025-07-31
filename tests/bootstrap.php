<?php
/**
 * PHPUnit Bootstrap file
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test environment
define('TESTING', true);

// Include autoloader if using Composer (optional)
// require_once __DIR__ . '/../vendor/autoload.php';

// Include models only (controllers have path dependencies we'll avoid)
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PointTransaction.php';
require_once __DIR__ . '/../models/Claim.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Stand.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Tournament.php';

/**
 * Test Database Configuration
 */
class TestDatabase {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Use a separate test database
        $host = 'localhost';
        $dbname = 'sistema_puntos_test';
        $username = 'root';
        $password = '';
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Test database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function resetDatabase() {
        // Clean all tables for fresh test state
        $tables = [
            'claims',
            'point_transactions', 
            'products',
            'stands',
            'tournaments',
            'events',
            'users'
        ];
        
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        foreach ($tables as $table) {
            $this->connection->exec("TRUNCATE TABLE $table");
        }
        
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    
    public function seedTestData() {
        // Insert test users
        $this->connection->exec("
            INSERT INTO users (id, nickname, email, password_hash, role, total_points) VALUES
            (1, 'testuser1', 'test1@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'user', 100),
            (2, 'testuser2', 'test2@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'user', 50),
            (3, 'assistant1', 'assistant@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'assistant', 0),
            (4, 'admin1', 'admin@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'admin', 0),
            (5, 'manager1', 'manager@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'stand_manager', 0)
        ");
        
        // Insert test event
        $this->connection->exec("
            INSERT INTO events (id, name, description, start_date, end_date, is_active, created_by) VALUES
            (1, 'Test Event', 'Test event description', '2025-01-01 00:00:00', '2025-12-31 23:59:59', 1, 4)
        ");
        
        // Insert test tournament
        $this->connection->exec("
            INSERT INTO tournaments (id, event_id, name, scheduled_time, points_reward, status) VALUES
            (1, 1, 'Test Tournament', '2025-06-01 10:00:00', 50, 'scheduled')
        ");
        
        // Insert test stand
        $this->connection->exec("
            INSERT INTO stands (id, name, manager_id, event_id) VALUES
            (1, 'Test Stand', 5, 1)
        ");
        
        // Insert test product
        $this->connection->exec("
            INSERT INTO products (id, name, description, points_required, stand_id, is_active) VALUES
            (1, 'Test Product', 'Test product description', 75, 1, 1),
            (2, 'Expensive Product', 'Expensive test product', 200, 1, 1)
        ");
    }
}

/**
 * Simple assertion functions for testing
 */
function assertTrue($condition, $message = '') {
    if (!$condition) {
        throw new Exception($message ?: 'Assertion failed: expected true');
    }
}

function assertFalse($condition, $message = '') {
    if ($condition) {
        throw new Exception($message ?: 'Assertion failed: expected false');
    }
}

function assertEquals($expected, $actual, $message = '') {
    if ($expected != $actual) {
        throw new Exception($message ?: "Assertion failed: expected '$expected', got '$actual'");
    }
}

function assertNotEquals($expected, $actual, $message = '') {
    if ($expected == $actual) {
        throw new Exception($message ?: "Assertion failed: expected not '$expected', but got '$actual'");
    }
}

function assertNotFalse($value, $message = '') {
    if ($value === false) {
        throw new Exception($message ?: 'Assertion failed: expected not false');
    }
}

function assertEmpty($value, $message = '') {
    if (!empty($value)) {
        throw new Exception($message ?: 'Assertion failed: expected empty value');
    }
}

function assertNotEmpty($value, $message = '') {
    if (empty($value)) {
        throw new Exception($message ?: 'Assertion failed: expected non-empty value');
    }
}

function assertContains($needle, $haystack, $message = '') {
    if (is_array($haystack)) {
        if (!in_array($needle, $haystack)) {
            throw new Exception($message ?: "Assertion failed: array does not contain '$needle'");
        }
    } else {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "Assertion failed: string does not contain '$needle'");
        }
    }
}

function assertArrayHasKey($key, $array, $message = '') {
    if (!is_array($array) || !array_key_exists($key, $array)) {
        throw new Exception($message ?: "Assertion failed: array does not have key '$key'");
    }
}

function assertIsInt($value, $message = '') {
    if (!is_int($value)) {
        throw new Exception($message ?: 'Assertion failed: expected integer');
    }
}

function assertGreaterThan($expected, $actual, $message = '') {
    if ($actual <= $expected) {
        throw new Exception($message ?: "Assertion failed: expected $actual > $expected");
    }
}

function assertGreaterThanOrEqual($expected, $actual, $message = '') {
    if ($actual < $expected) {
        throw new Exception($message ?: "Assertion failed: expected $actual >= $expected");
    }
}

/**
 * Base Test Case Class
 */
abstract class BaseTestCase {
    protected $db;
    protected $testDb;
    
    public function setUp() {
        $this->testDb = TestDatabase::getInstance();
        $this->db = $this->testDb->getConnection();
        $this->testDb->resetDatabase();
        $this->testDb->seedTestData();
    }
    
    public function tearDown() {
        // Clean up after each test
        $this->testDb->resetDatabase();
    }
    
    /**
     * Helper method to create a test user
     */
    protected function createTestUser($nickname = 'testuser', $email = 'test@example.com', $role = 'user', $points = 0) {
        $stmt = $this->db->prepare("
            INSERT INTO users (nickname, email, password_hash, role, total_points) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nickname,
            $email,
            password_hash('password123', PASSWORD_DEFAULT),
            $role,
            $points
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Helper method to create a test event
     */
    protected function createTestEvent($name = 'Test Event', $isActive = true) {
        $stmt = $this->db->prepare("
            INSERT INTO events (name, description, start_date, end_date, is_active, created_by) 
            VALUES (?, 'Test Description', '2025-01-01 00:00:00', '2025-12-31 23:59:59', ?, 1)
        ");
        $stmt->execute([$name, $isActive ? 1 : 0]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Helper method to create a test product
     */
    protected function createTestProduct($name = 'Test Product', $pointsRequired = 50, $standId = 1) {
        $stmt = $this->db->prepare("
            INSERT INTO products (name, description, points_required, stand_id, is_active) 
            VALUES (?, 'Test Description', ?, ?, 1)
        ");
        $stmt->execute([$name, $pointsRequired, $standId]);
        
        return $this->db->lastInsertId();
    }
}