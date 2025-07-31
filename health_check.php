<?php
/**
 * System health check script for monitoring
 * Task 13.2: Production monitoring and health checks
 */

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/services/LoggingService.php';
require_once __DIR__ . '/services/CacheService.php';

class HealthChecker {
    private $logger;
    private $config;
    private $results = [];
    
    public function __construct() {
        $this->config = getAppConfig();
        $this->logger = new LoggingService();
    }
    
    /**
     * Run all health checks
     */
    public function runAllChecks() {
        $this->results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => getEnvironment(),
            'overall_status' => 'healthy',
            'checks' => []
        ];
        
        // Run individual checks
        $this->checkDatabase();
        $this->checkFileSystem();
        $this->checkCache();
        $this->checkLogs();
        $this->checkPerformance();
        $this->checkSecurity();
        $this->checkDiskSpace();
        $this->checkMemoryUsage();
        
        // Determine overall status
        $this->determineOverallStatus();
        
        return $this->results;
    }
    
    /**
     * Check database connectivity and performance
     */
    private function checkDatabase() {
        $check = [
            'name' => 'Database',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        try {
            $startTime = microtime(true);
            $db = getDatabaseConnection();
            $connectionTime = (microtime(true) - $startTime) * 1000;
            
            // Test basic query
            $startTime = microtime(true);
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            $queryTime = (microtime(true) - $startTime) * 1000;
            
            $check['details'][] = "Connected successfully";
            $check['details'][] = "Users count: " . $result['count'];
            $check['metrics']['connection_time_ms'] = round($connectionTime, 2);
            $check['metrics']['query_time_ms'] = round($queryTime, 2);
            
            // Check for slow queries
            if ($connectionTime > 1000) {
                $check['status'] = 'warning';
                $check['details'][] = "Slow database connection";
            }
            
            if ($queryTime > 500) {
                $check['status'] = 'warning';
                $check['details'][] = "Slow query performance";
            }
            
            // Check database size
            $stmt = $db->query("SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()");
            $sizeResult = $stmt->fetch();
            $check['metrics']['database_size_mb'] = $sizeResult['size_mb'];
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['details'][] = "Database error: " . $e->getMessage();
            $this->logger->error("Health check database error", ['error' => $e->getMessage()]);
        }
        
        $this->results['checks']['database'] = $check;
    }
    
    /**
     * Check file system permissions and disk space
     */
    private function checkFileSystem() {
        $check = [
            'name' => 'File System',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        // Check writable directories
        $writableDirs = ['uploads', 'cache', 'logs'];
        if ($this->config['backup_enabled']) {
            $writableDirs[] = 'backups';
        }
        
        foreach ($writableDirs as $dir) {
            if (!is_dir($dir)) {
                $check['status'] = 'warning';
                $check['details'][] = "Directory missing: $dir";
            } elseif (!is_writable($dir)) {
                $check['status'] = 'critical';
                $check['details'][] = "Directory not writable: $dir";
            } else {
                $check['details'][] = "Directory OK: $dir";
            }
        }
        
        // Check critical files
        $criticalFiles = ['.env', 'config/database.php'];
        foreach ($criticalFiles as $file) {
            if (!file_exists($file)) {
                $check['status'] = 'critical';
                $check['details'][] = "Critical file missing: $file";
            } else {
                $check['details'][] = "File OK: $file";
            }
        }
        
        $this->results['checks']['filesystem'] = $check;
    }
    
    /**
     * Check cache system
     */
    private function checkCache() {
        $check = [
            'name' => 'Cache System',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        try {
            $cache = new CacheService();
            
            // Test cache write/read
            $testKey = 'health_check_' . time();
            $testValue = 'test_data';
            
            $startTime = microtime(true);
            $writeSuccess = $cache->set($testKey, $testValue, 60);
            $writeTime = (microtime(true) - $startTime) * 1000;
            
            $startTime = microtime(true);
            $readValue = $cache->get($testKey);
            $readTime = (microtime(true) - $startTime) * 1000;
            
            $cache->delete($testKey);
            
            if ($writeSuccess && $readValue === $testValue) {
                $check['details'][] = "Cache read/write test passed";
                $check['metrics']['write_time_ms'] = round($writeTime, 2);
                $check['metrics']['read_time_ms'] = round($readTime, 2);
            } else {
                $check['status'] = 'critical';
                $check['details'][] = "Cache read/write test failed";
            }
            
            // Get cache statistics
            $stats = $cache->getStats();
            $check['metrics']['total_files'] = $stats['total_files'];
            $check['metrics']['valid_files'] = $stats['valid_files'];
            $check['metrics']['expired_files'] = $stats['expired_files'];
            $check['metrics']['total_size_mb'] = $stats['total_size_mb'];
            
            if ($stats['expired_files'] > 10) {
                $check['status'] = 'warning';
                $check['details'][] = "Many expired cache files: " . $stats['expired_files'];
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['details'][] = "Cache error: " . $e->getMessage();
        }
        
        $this->results['checks']['cache'] = $check;
    }
    
    /**
     * Check logging system
     */
    private function checkLogs() {
        $check = [
            'name' => 'Logging System',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        try {
            // Test log write
            $testMessage = "Health check test log entry";
            $logSuccess = $this->logger->info($testMessage);
            
            if ($logSuccess) {
                $check['details'][] = "Log write test passed";
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "Log write test failed";
            }
            
            // Check log file sizes
            $logDir = $this->config['log_path'];
            $logFiles = glob($logDir . '*.log');
            $totalSize = 0;
            
            foreach ($logFiles as $file) {
                $size = filesize($file);
                $totalSize += $size;
                
                // Check for very large log files (>50MB)
                if ($size > 50 * 1024 * 1024) {
                    $check['status'] = 'warning';
                    $check['details'][] = "Large log file: " . basename($file) . " (" . round($size / 1024 / 1024, 2) . "MB)";
                }
            }
            
            $check['metrics']['log_files_count'] = count($logFiles);
            $check['metrics']['total_log_size_mb'] = round($totalSize / 1024 / 1024, 2);
            
            // Get recent error count
            $stats = $this->logger->getLogStats(1);
            $errorCount = $stats[LoggingService::TYPE_ERROR]['levels']['error'] ?? 0;
            $check['metrics']['recent_errors'] = $errorCount;
            
            if ($errorCount > 10) {
                $check['status'] = 'warning';
                $check['details'][] = "High error count in last 24h: $errorCount";
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['details'][] = "Logging error: " . $e->getMessage();
        }
        
        $this->results['checks']['logging'] = $check;
    }
    
    /**
     * Check system performance
     */
    private function checkPerformance() {
        $check = [
            'name' => 'Performance',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        // Check PHP configuration
        $check['metrics']['php_version'] = PHP_VERSION;
        $check['metrics']['memory_limit'] = ini_get('memory_limit');
        $check['metrics']['max_execution_time'] = ini_get('max_execution_time');
        
        // Check OPcache
        if (function_exists('opcache_get_status')) {
            $opcacheStatus = opcache_get_status();
            if ($opcacheStatus && $opcacheStatus['opcache_enabled']) {
                $check['details'][] = "OPcache enabled";
                $check['metrics']['opcache_hit_rate'] = round($opcacheStatus['opcache_statistics']['opcache_hit_rate'], 2);
            } else {
                $check['status'] = 'warning';
                $check['details'][] = "OPcache not enabled";
            }
        }
        
        // Check memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $check['metrics']['memory_usage_mb'] = round($memoryUsage / 1024 / 1024, 2);
        $check['metrics']['memory_peak_mb'] = round($memoryPeak / 1024 / 1024, 2);
        
        // Check load average (Linux only)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $check['metrics']['load_average'] = $load[0];
            
            if ($load[0] > 2.0) {
                $check['status'] = 'warning';
                $check['details'][] = "High system load: " . $load[0];
            }
        }
        
        $this->results['checks']['performance'] = $check;
    }
    
    /**
     * Check security configuration
     */
    private function checkSecurity() {
        $check = [
            'name' => 'Security',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        // Check PHP security settings
        if (ini_get('display_errors')) {
            $check['status'] = 'warning';
            $check['details'][] = "display_errors is enabled (should be off in production)";
        }
        
        if (!ini_get('session.cookie_httponly')) {
            $check['status'] = 'warning';
            $check['details'][] = "session.cookie_httponly is disabled";
        }
        
        if (getEnvironment() === 'production' && !ini_get('session.cookie_secure')) {
            $check['status'] = 'warning';
            $check['details'][] = "session.cookie_secure is disabled in production";
        }
        
        // Check file permissions
        if (file_exists('.env')) {
            $perms = fileperms('.env') & 0777;
            if ($perms !== 0600) {
                $check['status'] = 'warning';
                $check['details'][] = ".env file permissions too permissive: " . decoct($perms);
            }
        }
        
        // Check for exposed sensitive files
        $sensitiveFiles = ['.env', 'config/database.php'];
        foreach ($sensitiveFiles as $file) {
            if (file_exists($file)) {
                // Try to access via HTTP (basic check)
                $url = $this->config['url'] . '/' . $file;
                $headers = @get_headers($url);
                if ($headers && strpos($headers[0], '200') !== false) {
                    $check['status'] = 'critical';
                    $check['details'][] = "Sensitive file accessible via web: $file";
                }
            }
        }
        
        $check['details'][] = "Security configuration checked";
        
        $this->results['checks']['security'] = $check;
    }
    
    /**
     * Check disk space
     */
    private function checkDiskSpace() {
        $check = [
            'name' => 'Disk Space',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        $freeBytes = disk_free_space('.');
        $totalBytes = disk_total_space('.');
        
        if ($freeBytes !== false && $totalBytes !== false) {
            $freeGB = round($freeBytes / 1024 / 1024 / 1024, 2);
            $totalGB = round($totalBytes / 1024 / 1024 / 1024, 2);
            $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
            
            $check['metrics']['free_space_gb'] = $freeGB;
            $check['metrics']['total_space_gb'] = $totalGB;
            $check['metrics']['used_percent'] = $usedPercent;
            
            if ($usedPercent > 90) {
                $check['status'] = 'critical';
                $check['details'][] = "Disk space critically low: {$usedPercent}% used";
            } elseif ($usedPercent > 80) {
                $check['status'] = 'warning';
                $check['details'][] = "Disk space low: {$usedPercent}% used";
            } else {
                $check['details'][] = "Disk space OK: {$freeGB}GB free ({$usedPercent}% used)";
            }
        } else {
            $check['status'] = 'warning';
            $check['details'][] = "Could not determine disk space";
        }
        
        $this->results['checks']['disk_space'] = $check;
    }
    
    /**
     * Check memory usage
     */
    private function checkMemoryUsage() {
        $check = [
            'name' => 'Memory Usage',
            'status' => 'healthy',
            'details' => [],
            'metrics' => []
        ];
        
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        // Convert memory limit to bytes
        $limitBytes = $this->convertToBytes($memoryLimit);
        
        if ($limitBytes > 0) {
            $usagePercent = round(($memoryUsage / $limitBytes) * 100, 2);
            $check['metrics']['memory_usage_percent'] = $usagePercent;
            $check['metrics']['memory_limit'] = $memoryLimit;
            
            if ($usagePercent > 90) {
                $check['status'] = 'critical';
                $check['details'][] = "Memory usage critically high: {$usagePercent}%";
            } elseif ($usagePercent > 80) {
                $check['status'] = 'warning';
                $check['details'][] = "Memory usage high: {$usagePercent}%";
            } else {
                $check['details'][] = "Memory usage OK: {$usagePercent}%";
            }
        } else {
            $check['details'][] = "Memory limit: $memoryLimit";
        }
        
        $this->results['checks']['memory'] = $check;
    }
    
    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    /**
     * Determine overall system status
     */
    private function determineOverallStatus() {
        $hasCritical = false;
        $hasWarning = false;
        
        foreach ($this->results['checks'] as $check) {
            if ($check['status'] === 'critical') {
                $hasCritical = true;
                break;
            } elseif ($check['status'] === 'warning') {
                $hasWarning = true;
            }
        }
        
        if ($hasCritical) {
            $this->results['overall_status'] = 'critical';
        } elseif ($hasWarning) {
            $this->results['overall_status'] = 'warning';
        } else {
            $this->results['overall_status'] = 'healthy';
        }
    }
    
    /**
     * Get results as JSON
     */
    public function getResultsAsJson() {
        return json_encode($this->results, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get results as HTML
     */
    public function getResultsAsHtml() {
        $html = "<h1>System Health Check</h1>\n";
        $html .= "<p><strong>Timestamp:</strong> {$this->results['timestamp']}</p>\n";
        $html .= "<p><strong>Environment:</strong> {$this->results['environment']}</p>\n";
        $html .= "<p><strong>Overall Status:</strong> <span class='status-{$this->results['overall_status']}'>" . 
                 strtoupper($this->results['overall_status']) . "</span></p>\n";
        
        $html .= "<h2>Individual Checks</h2>\n";
        
        foreach ($this->results['checks'] as $check) {
            $html .= "<div class='check-result'>\n";
            $html .= "<h3>{$check['name']} - <span class='status-{$check['status']}'>" . 
                     strtoupper($check['status']) . "</span></h3>\n";
            
            if (!empty($check['details'])) {
                $html .= "<ul>\n";
                foreach ($check['details'] as $detail) {
                    $html .= "<li>$detail</li>\n";
                }
                $html .= "</ul>\n";
            }
            
            if (!empty($check['metrics'])) {
                $html .= "<h4>Metrics:</h4>\n<ul>\n";
                foreach ($check['metrics'] as $key => $value) {
                    $html .= "<li><strong>$key:</strong> $value</li>\n";
                }
                $html .= "</ul>\n";
            }
            
            $html .= "</div>\n";
        }
        
        return $html;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $format = $argv[1] ?? 'text';
    
    $checker = new HealthChecker();
    $results = $checker->runAllChecks();
    
    switch ($format) {
        case 'json':
            echo $checker->getResultsAsJson();
            break;
        case 'html':
            echo $checker->getResultsAsHtml();
            break;
        default:
            echo "System Health Check - " . $results['timestamp'] . "\n";
            echo "Environment: " . $results['environment'] . "\n";
            echo "Overall Status: " . strtoupper($results['overall_status']) . "\n\n";
            
            foreach ($results['checks'] as $check) {
                echo $check['name'] . ": " . strtoupper($check['status']) . "\n";
                foreach ($check['details'] as $detail) {
                    echo "  - $detail\n";
                }
                echo "\n";
            }
    }
    
    // Exit with appropriate code
    exit($results['overall_status'] === 'critical' ? 1 : 0);
}
?>