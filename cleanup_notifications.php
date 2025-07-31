<?php
/**
 * Cleanup script for old notifications
 * This script should be run periodically via cron job
 * Example: 0 2 * * * /usr/bin/php /path/to/cleanup_notifications.php
 */

require_once 'config/database.php';
require_once 'services/NotificationService.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $notificationService = new NotificationService($db);
    
    // Clean up notifications older than 30 days
    $result = $notificationService->cleanupOldNotifications(30);
    
    if ($result) {
        echo "Cleanup completed successfully\n";
        error_log("Notification cleanup completed successfully");
    } else {
        echo "Cleanup failed\n";
        error_log("Notification cleanup failed");
    }
    
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    error_log("Error during notification cleanup: " . $e->getMessage());
}
?>