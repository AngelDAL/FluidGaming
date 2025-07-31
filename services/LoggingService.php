<?php
/**
 * Logging service for error tracking and activity monitoring
 * Task 13.2: Implement logging of errors and critical activities
 */

class LoggingService {
    private $config;
    private $logPath;
    
    // Log levels
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    // Log types
    const TYPE_ERROR = 'error';
    const TYPE_SECURITY = 'security';
    const TYPE_ACTIVITY = 'activity';
    const TYPE_PERFORMANCE = 'performance';
    const TYPE_SYSTEM = 'system';
    
    private static $levelPriority = [
        self::EMERGENCY => 8,
        self::ALERT => 7,
        self::CRITICAL => 6,
        self::ERROR => 5,
        self::WARNING => 4,
        self::NOTICE => 3,
        self::INFO => 2,
        self::DEBUG => 1
    ];
    
    public function __construct($config = null) {
        $this->config = $config ?: getAppConfig();
        $this->logPath = $this->config['log_path'];
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Log a message with specified level and type
     */
    public function log($level, $message, $context = [], $type = self::TYPE_SYSTEM) {
        if (!$this->config['log_enabled']) {
            return false;
        }
        
        // Check if we should log this level
        if (!$this->shouldLog($level)) {
            return false;
        }
        
        $logEntry = $this->formatLogEntry($level, $message, $context, $type);
        $filename = $this->getLogFilename($type);
        
        return $this->writeToFile($filename, $logEntry);
    }
    
    /**
     * Log emergency messages
     */
    public function emergency($message, $context = [], $type = self::TYPE_ERROR) {
        return $this->log(self::EMERGENCY, $message, $context, $type);
    }
    
    /**
     * Log alert messages
     */
    public function alert($message, $context = [], $type = self::TYPE_ERROR) {
        return $this->log(self::ALERT, $message, $context, $type);
    }
    
    /**
     * Log critical messages
     */
    public function critical($message, $context = [], $type = self::TYPE_ERROR) {
        return $this->log(self::CRITICAL, $message, $context, $type);
    }
    
    /**
     * Log error messages
     */
    public function error($message, $context = [], $type = self::TYPE_ERROR) {
        return $this->log(self::ERROR, $message, $context, $type);
    }
    
    /**
     * Log warning messages
     */
    public function warning($message, $context = [], $type = self::TYPE_SYSTEM) {
        return $this->log(self::WARNING, $message, $context, $type);
    }
    
    /**
     * Log notice messages
     */
    public function notice($message, $context = [], $type = self::TYPE_SYSTEM) {
        return $this->log(self::NOTICE, $message, $context, $type);
    }
    
    /**
     * Log info messages
     */
    public function info($message, $context = [], $type = self::TYPE_ACTIVITY) {
        return $this->log(self::INFO, $message, $context, $type);
    }
    
    /**
     * Log debug messages
     */
    public function debug($message, $context = [], $type = self::TYPE_SYSTEM) {
        return $this->log(self::DEBUG, $message, $context, $type);
    }
    
    /**
     * Log security events
     */
    public function security($level, $message, $context = []) {
        return $this->log($level, $message, $context, self::TYPE_SECURITY);
    }
    
    /**
     * Log user activity
     */
    public function activity($message, $context = []) {
        return $this->log(self::INFO, $message, $context, self::TYPE_ACTIVITY);
    }
    
    /**
     * Log performance metrics
     */
    public function performance($message, $context = []) {
        return $this->log(self::INFO, $message, $context, self::TYPE_PERFORMANCE);
    }
    
    /**
     * Log database operations
     */
    public function database($level, $message, $context = []) {
        $context['type'] = 'database';
        return $this->log($level, $message, $context, self::TYPE_SYSTEM);
    }
    
    /**
     * Log API requests
     */
    public function apiRequest($method, $endpoint, $userId = null, $responseTime = null, $statusCode = null) {
        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'user_id' => $userId,
            'response_time_ms' => $responseTime,
            'status_code' => $statusCode,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        return $this->log(self::INFO, "API Request: $method $endpoint", $context, self::TYPE_ACTIVITY);
    }
    
    /**
     * Log authentication events
     */
    public function authentication($event, $userId = null, $success = true, $details = []) {
        $level = $success ? self::INFO : self::WARNING;
        $context = array_merge([
            'event' => $event,
            'user_id' => $userId,
            'success' => $success,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $details);
        
        return $this->log($level, "Authentication: $event", $context, self::TYPE_SECURITY);
    }
    
    /**
     * Log point transactions
     */
    public function pointTransaction($action, $userId, $points, $tournamentId = null, $assignedBy = null) {
        $context = [
            'action' => $action,
            'user_id' => $userId,
            'points' => $points,
            'tournament_id' => $tournamentId,
            'assigned_by' => $assignedBy
        ];
        
        return $this->log(self::INFO, "Point Transaction: $action", $context, self::TYPE_ACTIVITY);
    }
    
    /**
     * Log product claims
     */
    public function productClaim($action, $userId, $productId, $standId, $processedBy = null) {
        $context = [
            'action' => $action,
            'user_id' => $userId,
            'product_id' => $productId,
            'stand_id' => $standId,
            'processed_by' => $processedBy
        ];
        
        return $this->log(self::INFO, "Product Claim: $action", $context, self::TYPE_ACTIVITY);
    }
    
    /**
     * Check if we should log this level
     */
    private function shouldLog($level) {
        $configLevel = $this->config['log_level'];
        $configPriority = self::$levelPriority[$configLevel] ?? 1;
        $messagePriority = self::$levelPriority[$level] ?? 1;
        
        return $messagePriority >= $configPriority;
    }
    
    /**
     * Format log entry
     */
    private function formatLogEntry($level, $message, $context, $type) {
        $timestamp = date('Y-m-d H:i:s');
        $pid = getmypid();
        $memory = memory_get_usage(true);
        
        $entry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'meta' => [
                'pid' => $pid,
                'memory_usage' => $memory,
                'request_id' => $this->getRequestId()
            ]
        ];
        
        return json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    /**
     * Get log filename based on type and date
     */
    private function getLogFilename($type) {
        $date = date('Y-m-d');
        return $this->logPath . "/{$type}-{$date}.log";
    }
    
    /**
     * Write log entry to file
     */
    private function writeToFile($filename, $logEntry) {
        try {
            $result = file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
            
            // Rotate logs if needed
            $this->rotateLogsIfNeeded($filename);
            
            return $result !== false;
        } catch (Exception $e) {
            // If we can't write to log file, try to write to system error log
            error_log("Failed to write to log file $filename: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rotate logs if they get too large or old
     */
    private function rotateLogsIfNeeded($filename) {
        if (!file_exists($filename)) {
            return;
        }
        
        // Check file size (rotate if > 10MB)
        if (filesize($filename) > 10 * 1024 * 1024) {
            $rotatedName = $filename . '.' . time();
            rename($filename, $rotatedName);
            
            // Compress the rotated file
            if (function_exists('gzopen')) {
                $this->compressLogFile($rotatedName);
            }
        }
        
        // Clean old log files
        $this->cleanOldLogs();
    }
    
    /**
     * Compress log file
     */
    private function compressLogFile($filename) {
        try {
            $data = file_get_contents($filename);
            $compressed = gzencode($data);
            file_put_contents($filename . '.gz', $compressed);
            unlink($filename);
        } catch (Exception $e) {
            error_log("Failed to compress log file $filename: " . $e->getMessage());
        }
    }
    
    /**
     * Clean old log files
     */
    private function cleanOldLogs() {
        $maxFiles = $this->config['log_max_files'];
        $files = glob($this->logPath . '/*.log*');
        
        if (count($files) <= $maxFiles) {
            return;
        }
        
        // Sort by modification time
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Remove oldest files
        $filesToRemove = array_slice($files, 0, count($files) - $maxFiles);
        foreach ($filesToRemove as $file) {
            unlink($file);
        }
    }
    
    /**
     * Get unique request ID for tracking
     */
    private function getRequestId() {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = uniqid('req_', true);
        }
        
        return $requestId;
    }
    
    /**
     * Get log statistics
     */
    public function getLogStats($days = 7) {
        $stats = [];
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $types = [self::TYPE_ERROR, self::TYPE_SECURITY, self::TYPE_ACTIVITY, self::TYPE_PERFORMANCE, self::TYPE_SYSTEM];
        
        foreach ($types as $type) {
            $stats[$type] = [
                'total_entries' => 0,
                'levels' => []
            ];
            
            for ($i = 0; $i < $days; $i++) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $filename = $this->logPath . "/{$type}-{$date}.log";
                
                if (file_exists($filename)) {
                    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $stats[$type]['total_entries'] += count($lines);
                    
                    foreach ($lines as $line) {
                        $entry = json_decode($line, true);
                        if ($entry && isset($entry['level'])) {
                            $level = strtolower($entry['level']);
                            $stats[$type]['levels'][$level] = ($stats[$type]['levels'][$level] ?? 0) + 1;
                        }
                    }
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Search logs
     */
    public function searchLogs($query, $type = null, $level = null, $days = 7) {
        $results = [];
        $types = $type ? [$type] : [self::TYPE_ERROR, self::TYPE_SECURITY, self::TYPE_ACTIVITY, self::TYPE_PERFORMANCE, self::TYPE_SYSTEM];
        
        foreach ($types as $logType) {
            for ($i = 0; $i < $days; $i++) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $filename = $this->logPath . "/{$logType}-{$date}.log";
                
                if (file_exists($filename)) {
                    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    
                    foreach ($lines as $lineNumber => $line) {
                        $entry = json_decode($line, true);
                        
                        if (!$entry) continue;
                        
                        // Filter by level if specified
                        if ($level && strtolower($entry['level']) !== strtolower($level)) {
                            continue;
                        }
                        
                        // Search in message and context
                        $searchText = strtolower($entry['message'] . ' ' . json_encode($entry['context']));
                        if (strpos($searchText, strtolower($query)) !== false) {
                            $results[] = [
                                'file' => basename($filename),
                                'line' => $lineNumber + 1,
                                'entry' => $entry
                            ];
                        }
                    }
                }
            }
        }
        
        return $results;
    }
}
?>