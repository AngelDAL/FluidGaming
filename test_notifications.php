<?php
/**
 * Test script for notification system
 */

require_once 'config/database.php';
require_once 'services/NotificationService.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $notificationService = new NotificationService($db);
    
    echo "Testing Notification System...\n\n";
    
    // Get a test user
    $stmt = $db->prepare("SELECT id, nickname FROM users WHERE role = 'user' LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "No test user found. Please create a user first.\n";
        exit(1);
    }
    
    echo "Test user: {$testUser['nickname']} (ID: {$testUser['id']})\n\n";
    
    // Test 1: Tournament notification
    echo "1. Testing tournament notification...\n";
    $result1 = $notificationService->notifyNewTournament(1, "Test Tournament", "2024-01-15 18:00:00");
    echo $result1 ? "✓ Tournament notification sent\n" : "✗ Tournament notification failed\n";
    
    // Test 2: Points notification
    echo "2. Testing points notification...\n";
    $result2 = $notificationService->notifyPointsAssigned($testUser['id'], 100, 'tournament', 1);
    echo $result2 ? "✓ Points notification sent\n" : "✗ Points notification failed\n";
    
    // Test 3: System notification
    echo "3. Testing system notification...\n";
    $result3 = $notificationService->notifySystem($testUser['id'], "Test System", "This is a test system notification");
    echo $result3 ? "✓ System notification sent\n" : "✗ System notification failed\n";
    
    // Test 4: Get user notifications
    echo "4. Testing get user notifications...\n";
    $notifications = $notificationService->getUserNotifications($testUser['id'], 10);
    echo "✓ Retrieved " . count($notifications) . " notifications\n";
    
    // Test 5: Get unread count
    echo "5. Testing unread count...\n";
    $unreadCount = $notificationService->getUnreadCount($testUser['id']);
    echo "✓ Unread count: {$unreadCount}\n";
    
    echo "\nAll tests completed!\n";
    
} catch (Exception $e) {
    echo "Error during testing: " . $e->getMessage() . "\n";
    error_log("Error during notification testing: " . $e->getMessage());
}
?>