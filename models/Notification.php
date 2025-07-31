<?php

class Notification {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create a new notification
     */
    public function create($userId, $title, $message, $type) {
        $validTypes = ['tournament', 'points', 'event', 'system'];
        
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Invalid notification type: $type");
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([$userId, $title, $message, $type]);
    }
    
    /**
     * Get notifications for a user
     */
    public function getUserNotifications($userId, $limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT id, title, message, type, is_read, created_at
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = false
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = true 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$notificationId, $userId]);
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = true 
            WHERE user_id = ? AND is_read = false
        ");
        
        return $stmt->execute([$userId]);
    }
    
    /**
     * Delete old notifications (older than specified days)
     */
    public function deleteOldNotifications($days = 30) {
        $stmt = $this->db->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        return $stmt->execute([$days]);
    }
    
    /**
     * Create notification for all users
     */
    public function createForAllUsers($title, $message, $type) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            SELECT id, ?, ?, ? FROM users WHERE role = 'user'
        ");
        
        return $stmt->execute([$title, $message, $type]);
    }
    
    /**
     * Create notification for users with specific role
     */
    public function createForRole($role, $title, $message, $type) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            SELECT id, ?, ?, ? FROM users WHERE role = ?
        ");
        
        return $stmt->execute([$title, $message, $type, $role]);
    }
}