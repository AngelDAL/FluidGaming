<?php
/**
 * Production deployment script
 * Task 13.2: Deployment automation for production
 */

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/services/LoggingService.php';

class DeploymentManager {
    private $logger;
    private $config;
    private $startTime;
    
    public function __construct() {
        $this->config = getAppConfig();
        $this->logger = new LoggingService();
        $this->startTime = microtime(true);
    }
    
    /**
     * Run full deployment process
     */
    public function deploy($options = []) {
        $this->log("Starting deployment process...");
        
        try {
            // Pre-deployment checks
            $this->preDeploymentChecks();
            
            // Create backup
            if (!isset($options['skip-backup']) || !$options['skip-backup']) {
                $this->createBackup();
            }
            
            // Update database schema
            $this->updateDatabase();
            
            // Apply performance optimizations
            $this->applyPerformanceOptimizations();
            
            // Clear and warm up cache
            $this->manageCaches();
            
            // Set proper permissions
            $this->setPermissions();
            
            // Run post-deployment tests
            $this->runTests();
            
            // Log successful deployment
            $this->logDeploymentSuccess();
            
            $this->log("Deployment completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->logger->critical("Deployment failed: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            
            echo "❌ Deployment failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Pre-deployment checks
     */
    private function preDeploymentChecks() {
        $this->log("Running pre-deployment checks...");
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            throw new Exception("PHP 7.4.0 or higher is required. Current version: " . PHP_VERSION);
        }
        
        // Check required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'gd'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("Required PHP extension not found: $ext");
            }
        }
        
        // Check database connection
        try {
            $db = getDatabaseConnection();
            $stmt = $db->query("SELECT 1");
            $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        // Check write permissions
        $writableDirs = ['uploads', 'cache', 'logs'];
        if ($this->config['backup_enabled']) {
            $writableDirs[] = 'backups';
        }
        
        foreach ($writableDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            
            if (!is_writable($dir)) {
                throw new Exception("Directory is not writable: $dir");
            }
        }
        
        // Check .env file
        if (!file_exists('.env')) {
            throw new Exception(".env file not found. Copy .env.example and configure it.");
        }
        
        $this->log("✅ Pre-deployment checks passed");
    }
    
    /**
     * Create backup before deployment
     */
    private function createBackup() {
        $this->log("Creating backup...");
        
        if (!$this->config['backup_enabled']) {
            $this->log("⚠️  Backup is disabled in configuration");
            return;
        }
        
        $backupDir = $this->config['backup_path'];
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "deployment-backup-$timestamp.sql";
        
        // Database backup
        $dbConfig = getDatabaseConfig();
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['dbname']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Database backup failed");
        }
        
        // Compress backup
        if (function_exists('gzopen')) {
            $data = file_get_contents($backupFile);
            $compressed = gzencode($data);
            file_put_contents($backupFile . '.gz', $compressed);
            unlink($backupFile);
            $backupFile .= '.gz';
        }
        
        $this->log("✅ Backup created: " . basename($backupFile));
    }
    
    /**
     * Update database schema and apply migrations
     */
    private function updateDatabase() {
        $this->log("Updating database...");
        
        try {
            // Apply performance indexes
            $this->log("Applying performance indexes...");
            $output = shell_exec('php database/apply_performance_indexes.php 2>&1');
            
            if (strpos($output, 'completed') === false) {
                throw new Exception("Failed to apply performance indexes: $output");
            }
            
            $this->log("✅ Database updated successfully");
            
        } catch (Exception $e) {
            throw new Exception("Database update failed: " . $e->getMessage());
        }
    }
    
    /**
     * Apply performance optimizations
     */
    private function applyPerformanceOptimizations() {
        $this->log("Applying performance optimizations...");
        
        // Optimize PHP settings for production
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $this->log("✅ OPcache reset");
        }
        
        // Set production PHP settings
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        
        $this->log("✅ Performance optimizations applied");
    }
    
    /**
     * Manage caches
     */
    private function manageCaches() {
        $this->log("Managing caches...");
        
        // Clear old cache
        $output = shell_exec('php cache_manager.php clear 2>&1');
        $this->log("Cache cleared: $output");
        
        // Warm up cache
        $output = shell_exec('php cache_manager.php warmup 2>&1');
        $this->log("Cache warmed up: $output");
        
        $this->log("✅ Cache management completed");
    }
    
    /**
     * Set proper file permissions
     */
    private function setPermissions() {
        $this->log("Setting file permissions...");
        
        // Set general permissions
        chmod(__DIR__, 0755);
        
        // Set writable directories
        $writableDirs = ['uploads', 'cache', 'logs'];
        if ($this->config['backup_enabled']) {
            $writableDirs[] = 'backups';
        }
        
        foreach ($writableDirs as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0775);
                
                // Set permissions for files in directory
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        chmod($file->getPathname(), 0664);
                    } elseif ($file->isDir()) {
                        chmod($file->getPathname(), 0775);
                    }
                }
            }
        }
        
        // Protect sensitive files
        if (file_exists('.env')) {
            chmod('.env', 0600);
        }
        
        $this->log("✅ File permissions set");
    }
    
    /**
     * Run post-deployment tests
     */
    private function runTests() {
        $this->log("Running post-deployment tests...");
        
        // Test database connection
        try {
            $db = getDatabaseConnection();
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            $this->log("Database test passed - Users count: " . $result['count']);
        } catch (Exception $e) {
            throw new Exception("Database test failed: " . $e->getMessage());
        }
        
        // Test cache functionality
        try {
            require_once 'services/CacheService.php';
            $cache = new CacheService();
            $cache->set('deployment_test', 'success', 60);
            $result = $cache->get('deployment_test');
            
            if ($result !== 'success') {
                throw new Exception("Cache test failed");
            }
            
            $cache->delete('deployment_test');
            $this->log("Cache test passed");
        } catch (Exception $e) {
            throw new Exception("Cache test failed: " . $e->getMessage());
        }
        
        // Test logging
        try {
            $this->logger->info("Deployment test log entry");
            $this->log("Logging test passed");
        } catch (Exception $e) {
            throw new Exception("Logging test failed: " . $e->getMessage());
        }
        
        $this->log("✅ All tests passed");
    }
    
    /**
     * Log deployment success
     */
    private function logDeploymentSuccess() {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);
        
        $this->logger->info("Deployment completed successfully", [
            'duration_ms' => $duration,
            'environment' => getEnvironment(),
            'php_version' => PHP_VERSION,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Log message to console and logger
     */
    private function log($message) {
        echo date('Y-m-d H:i:s') . " - $message\n";
        $this->logger->info("Deployment: $message");
    }
    
    /**
     * Rollback deployment
     */
    public function rollback($backupFile = null) {
        $this->log("Starting rollback process...");
        
        try {
            if (!$backupFile) {
                // Find latest backup
                $backupDir = $this->config['backup_path'];
                $backups = glob($backupDir . 'deployment-backup-*.sql*');
                
                if (empty($backups)) {
                    throw new Exception("No backup files found for rollback");
                }
                
                // Sort by modification time, get latest
                usort($backups, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                $backupFile = $backups[0];
            }
            
            $this->log("Rolling back to: " . basename($backupFile));
            
            // Restore database
            $dbConfig = getDatabaseConfig();
            
            // Handle compressed backups
            if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
                $tempFile = tempnam(sys_get_temp_dir(), 'rollback_');
                $compressed = file_get_contents($backupFile);
                $decompressed = gzdecode($compressed);
                file_put_contents($tempFile, $decompressed);
                $backupFile = $tempFile;
            }
            
            $command = sprintf(
                'mysql -h%s -u%s -p%s %s < %s',
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['dbname']),
                escapeshellarg($backupFile)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Database rollback failed");
            }
            
            // Clear cache after rollback
            shell_exec('php cache_manager.php clear 2>&1');
            
            $this->logger->warning("Deployment rolled back", [
                'backup_file' => basename($backupFile),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $this->log("✅ Rollback completed successfully");
            
            // Clean up temp file if created
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->critical("Rollback failed: " . $e->getMessage());
            echo "❌ Rollback failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $options = [];
    
    // Parse command line arguments
    for ($i = 1; $i < $argc; $i++) {
        switch ($argv[$i]) {
            case '--skip-backup':
                $options['skip-backup'] = true;
                break;
            case '--rollback':
                $options['rollback'] = true;
                $options['backup-file'] = $argv[$i + 1] ?? null;
                break;
            case '--help':
                echo "Deployment Manager\n";
                echo "Usage: php deploy.php [options]\n\n";
                echo "Options:\n";
                echo "  --skip-backup    Skip backup creation\n";
                echo "  --rollback [file] Rollback to backup (latest if no file specified)\n";
                echo "  --help           Show this help message\n";
                exit(0);
        }
    }
    
    $deployer = new DeploymentManager();
    
    if (isset($options['rollback'])) {
        $success = $deployer->rollback($options['backup-file'] ?? null);
    } else {
        $success = $deployer->deploy($options);
    }
    
    exit($success ? 0 : 1);
}
?>