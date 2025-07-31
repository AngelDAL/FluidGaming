<?php

require_once __DIR__ . '/../models/Notification.php';

class NotificationService {
    private $notification;
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->notification = new Notification($database);
    }
    
    /**
     * Send notification for new tournament
     * Requirement 8.1: CUANDO se programa un nuevo torneo ENTONCES el sistema DEBERÁ notificar a todos los usuarios registrados
     */
    public function notifyNewTournament($tournamentId, $tournamentName, $scheduledTime) {
        $title = "Nuevo Torneo Programado";
        $message = "Se ha programado un nuevo torneo: {$tournamentName}. Fecha: " . date('d/m/Y H:i', strtotime($scheduledTime));
        
        try {
            // Notify all regular users
            $result = $this->notification->createForRole('user', $title, $message, 'tournament');
            
            if ($result) {
                error_log("Notification sent for new tournament: {$tournamentName}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending tournament notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification for points assignment
     * Requirement 8.2: CUANDO un usuario gana puntos ENTONCES el sistema DEBERÁ enviar una notificación de confirmación
     */
    public function notifyPointsAssigned($userId, $points, $source, $assignedBy = null) {
        $title = "Puntos Asignados";
        
        $sourceText = $this->getSourceText($source);
        $message = "Has ganado {$points} puntos por {$sourceText}.";
        
        if ($assignedBy) {
            $stmt = $this->db->prepare("SELECT nickname FROM users WHERE id = ?");
            $stmt->execute([$assignedBy]);
            $assigner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assigner) {
                $message .= " Asignado por: {$assigner['nickname']}.";
            }
        }
        
        try {
            $result = $this->notification->create($userId, $title, $message, 'points');
            
            if ($result) {
                error_log("Points notification sent to user {$userId}: {$points} points");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending points notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification for event ending soon
     * Requirement 8.3: CUANDO un evento está por finalizar ENTONCES el sistema DEBERÁ enviar recordatorios a los usuarios
     */
    public function notifyEventEndingSoon($eventId, $eventName, $endDate) {
        $title = "Evento Terminando Pronto";
        $message = "El evento '{$eventName}' terminará el " . date('d/m/Y H:i', strtotime($endDate)) . ". ¡No pierdas la oportunidad de ganar más puntos!";
        
        try {
            $result = $this->notification->createForAllUsers($title, $message, 'event');
            
            if ($result) {
                error_log("Event ending notification sent for: {$eventName}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending event ending notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification for leaderboard changes
     * Requirement 8.4: CUANDO hay cambios en el leaderboard ENTONCES el sistema DEBERÁ notificar a los usuarios afectados
     */
    public function notifyLeaderboardChange($userId, $newRank, $previousRank = null) {
        $title = "Cambio en el Leaderboard";
        
        if ($previousRank === null) {
            $message = "¡Felicidades! Ahora estás en la posición #{$newRank} del leaderboard.";
        } else if ($newRank < $previousRank) {
            $message = "¡Excelente! Has subido del puesto #{$previousRank} al #{$newRank} en el leaderboard.";
        } else if ($newRank > $previousRank) {
            $message = "Has bajado del puesto #{$previousRank} al #{$newRank} en el leaderboard. ¡Sigue participando para subir!";
        } else {
            return true; // No change, no notification needed
        }
        
        try {
            $result = $this->notification->create($userId, $title, $message, 'points');
            
            if ($result) {
                error_log("Leaderboard change notification sent to user {$userId}: rank {$newRank}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending leaderboard notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send system notification
     */
    public function notifySystem($userId, $title, $message) {
        try {
            $result = $this->notification->create($userId, $title, $message, 'system');
            
            if ($result) {
                error_log("System notification sent to user {$userId}: {$title}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending system notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $limit = 50, $offset = 0) {
        return $this->notification->getUserNotifications($userId, $limit, $offset);
    }
    
    /**
     * Get unread notifications count
     */
    public function getUnreadCount($userId) {
        return $this->notification->getUnreadCount($userId);
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        return $this->notification->markAsRead($notificationId, $userId);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        return $this->notification->markAllAsRead($userId);
    }
    
    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications($days = 30) {
        return $this->notification->deleteOldNotifications($days);
    }
    
    /**
     * Get source text for points notification
     */
    private function getSourceText($source) {
        switch ($source) {
            case 'tournament':
                return 'participar en un torneo';
            case 'challenge':
                return 'completar un desafío';
            case 'bonus':
                return 'bonificación especial';
            default:
                return 'actividad';
        }
    }
    
    /**
     * Send notification when tournament starts
     */
    public function notifyTournamentStarting($tournamentId, $tournamentName) {
        $title = "Torneo Iniciando";
        $message = "El torneo '{$tournamentName}' está comenzando. ¡Participa ahora!";
        
        try {
            $result = $this->notification->createForAllUsers($title, $message, 'tournament');
            
            if ($result) {
                error_log("Tournament starting notification sent for: {$tournamentName}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending tournament starting notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification when tournament ends
     */
    public function notifyTournamentEnded($tournamentId, $tournamentName, $winners = []) {
        $title = "Torneo Finalizado";
        $message = "El torneo '{$tournamentName}' ha finalizado.";
        
        if (!empty($winners)) {
            $message .= " ¡Felicidades a los ganadores!";
        }
        
        try {
            $result = $this->notification->createForAllUsers($title, $message, 'tournament');
            
            if ($result) {
                error_log("Tournament ended notification sent for: {$tournamentName}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error sending tournament ended notification: " . $e->getMessage());
            return false;
        }
    }
}