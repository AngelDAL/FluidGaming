<?php
/**
 * PointTransaction model
 */

class PointTransaction {
    private $conn;
    private $table_name = "point_transactions";

    public $id;
    public $user_id;
    public $points;
    public $type;
    public $source;
    public $tournament_id;
    public $assigned_by;
    public $timestamp;
    public $metadata;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Validate point transaction input
     */
    public function validateTransaction($user_id, $points, $type, $source, $assigned_by, $tournament_id = null, $metadata = null) {
        $errors = [];

        // Validate user_id
        if (empty($user_id) || !is_numeric($user_id)) {
            $errors[] = "ID de usuario inválido";
        } else {
            // Check if user exists
            if (!$this->userExists($user_id)) {
                $errors[] = "El usuario no existe";
            }
        }

        // Validate points
        if (!is_numeric($points)) {
            $errors[] = "Los puntos deben ser un número";
        } elseif ($points <= 0) {
            $errors[] = "Los puntos deben ser mayor a 0";
        } elseif ($points > 10000) {
            $errors[] = "Los puntos no pueden ser mayor a 10,000";
        }

        // Validate type
        $validTypes = ['earned', 'claimed'];
        if (empty($type) || !in_array($type, $validTypes)) {
            $errors[] = "Tipo de transacción inválido";
        }

        // Validate source
        $validSources = ['tournament', 'challenge', 'bonus'];
        if (empty($source) || !in_array($source, $validSources)) {
            $errors[] = "Fuente de puntos inválida";
        }

        // Validate assigned_by
        if (empty($assigned_by) || !is_numeric($assigned_by)) {
            $errors[] = "ID del asignador inválido";
        } else {
            // Check if assigner exists and has permission
            if (!$this->userCanAssignPoints($assigned_by)) {
                $errors[] = "El usuario no tiene permisos para asignar puntos";
            }
        }

        // Validate tournament_id if provided
        if ($tournament_id !== null) {
            if (!is_numeric($tournament_id)) {
                $errors[] = "ID de torneo inválido";
            } else {
                // Check if tournament exists
                if (!$this->tournamentExists($tournament_id)) {
                    $errors[] = "El torneo no existe";
                }
            }
        }

        // Validate metadata if provided
        if ($metadata !== null && !empty($metadata)) {
            if (!is_array($metadata)) {
                $errors[] = "Los metadatos deben ser un array";
            }
        }

        return $errors;
    }

    /**
     * Check if user exists
     */
    private function userExists($user_id) {
        $query = "SELECT id FROM users WHERE id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if user can assign points (assistant, stand_manager, or admin)
     */
    private function userCanAssignPoints($user_id) {
        $query = "SELECT role FROM users WHERE id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $allowedRoles = ['assistant', 'stand_manager', 'admin'];
            return in_array($row['role'], $allowedRoles);
        }
        
        return false;
    }

    /**
     * Check if tournament exists
     */
    private function tournamentExists($tournament_id) {
        $query = "SELECT id FROM tournaments WHERE id = :tournament_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tournament_id', $tournament_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Validate that event is active before assigning points
     */
    public function validateActiveEvent($tournament_id = null) {
        if ($tournament_id === null) {
            // For non-tournament points, check if there's any active event
            $query = "SELECT COUNT(*) as count FROM events 
                      WHERE is_active = 1 
                      AND NOW() BETWEEN start_date AND end_date";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                return ['valid' => false, 'error' => 'No hay eventos activos para asignar puntos'];
            }
        } else {
            // For tournament points, check if the tournament's event is active
            $query = "SELECT e.id, e.name 
                      FROM tournaments t 
                      JOIN events e ON t.event_id = e.id 
                      WHERE t.id = :tournament_id 
                      AND e.is_active = 1 
                      AND NOW() BETWEEN e.start_date AND e.end_date 
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tournament_id', $tournament_id);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return ['valid' => false, 'error' => 'El evento del torneo no está activo'];
            }
        }
        
        return ['valid' => true];
    }

    /**
     * Create new point transaction
     */
    public function create($user_id, $points, $type, $source, $assigned_by, $tournament_id = null, $metadata = null) {
        // Validate input
        $errors = $this->validateTransaction($user_id, $points, $type, $source, $assigned_by, $tournament_id, $metadata);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Validate active event (temporarily disabled for testing)
        // $eventValidation = $this->validateActiveEvent($tournament_id);
        // if (!$eventValidation['valid']) {
        //     return ['success' => false, 'errors' => [$eventValidation['error']]];
        // }

        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Convert metadata to JSON if provided
            $metadataJson = null;
            if ($metadata !== null && !empty($metadata)) {
                $metadataJson = json_encode($metadata);
            }

            // Insert point transaction
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, points, type, source, tournament_id, assigned_by, metadata) 
                      VALUES (:user_id, :points, :type, :source, :tournament_id, :assigned_by, :metadata)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':points', $points);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':source', $source);
            $stmt->bindParam(':tournament_id', $tournament_id);
            $stmt->bindParam(':assigned_by', $assigned_by);
            $stmt->bindParam(':metadata', $metadataJson);

            if (!$stmt->execute()) {
                throw new Exception('Error al crear la transacción de puntos');
            }

            $this->id = $this->conn->lastInsertId();

            // Update user's total points only for 'earned' type
            if ($type === 'earned') {
                $updateQuery = "UPDATE users SET total_points = total_points + :points WHERE id = :user_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':points', $points);
                $updateStmt->bindParam(':user_id', $user_id);
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Error al actualizar los puntos del usuario');
                }
            }

            // Commit transaction
            $this->conn->commit();

            return ['success' => true, 'transaction_id' => $this->id];

        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollback();
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Get transaction by ID
     */
    public function getById($id) {
        $query = "SELECT pt.*, 
                         u.nickname as user_nickname,
                         a.nickname as assigned_by_nickname,
                         t.name as tournament_name
                  FROM " . $this->table_name . " pt
                  LEFT JOIN users u ON pt.user_id = u.id
                  LEFT JOIN users a ON pt.assigned_by = a.id
                  LEFT JOIN tournaments t ON pt.tournament_id = t.id
                  WHERE pt.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            // Decode metadata JSON
            if ($row['metadata']) {
                $row['metadata'] = json_decode($row['metadata'], true);
            }
            return $row;
        }

        return false;
    }

    /**
     * Get transactions by user ID with pagination
     */
    public function getByUserId($user_id, $page = 1, $limit = 10, $type = null) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE pt.user_id = :user_id";
        $params = [':user_id' => $user_id];
        
        if ($type !== null && in_array($type, ['earned', 'claimed'])) {
            $whereClause .= " AND pt.type = :type";
            $params[':type'] = $type;
        }

        $query = "SELECT pt.*, 
                         u.nickname as user_nickname,
                         a.nickname as assigned_by_nickname,
                         t.name as tournament_name
                  FROM " . $this->table_name . " pt
                  LEFT JOIN users u ON pt.user_id = u.id
                  LEFT JOIN users a ON pt.assigned_by = a.id
                  LEFT JOIN tournaments t ON pt.tournament_id = t.id
                  $whereClause
                  ORDER BY pt.timestamp DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $transactions = $stmt->fetchAll();

        // Decode metadata JSON for each transaction
        foreach ($transactions as &$transaction) {
            if ($transaction['metadata']) {
                $transaction['metadata'] = json_decode($transaction['metadata'], true);
            }
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " pt $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        return [
            'transactions' => $transactions,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get recent transactions with pagination
     */
    public function getRecent($page = 1, $limit = 20, $type = null, $source = null) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "";
        $params = [];
        
        $conditions = [];
        if ($type !== null && in_array($type, ['earned', 'claimed'])) {
            $conditions[] = "pt.type = :type";
            $params[':type'] = $type;
        }
        
        if ($source !== null && in_array($source, ['tournament', 'challenge', 'bonus'])) {
            $conditions[] = "pt.source = :source";
            $params[':source'] = $source;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }

        $query = "SELECT pt.*, 
                         u.nickname as user_nickname,
                         a.nickname as assigned_by_nickname,
                         t.name as tournament_name
                  FROM " . $this->table_name . " pt
                  LEFT JOIN users u ON pt.user_id = u.id
                  LEFT JOIN users a ON pt.assigned_by = a.id
                  LEFT JOIN tournaments t ON pt.tournament_id = t.id
                  $whereClause
                  ORDER BY pt.timestamp DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $transactions = $stmt->fetchAll();

        // Decode metadata JSON for each transaction
        foreach ($transactions as &$transaction) {
            if ($transaction['metadata']) {
                $transaction['metadata'] = json_decode($transaction['metadata'], true);
            }
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " pt $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        return [
            'transactions' => $transactions,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get user's total points from transactions
     */
    public function getUserTotalPoints($user_id) {
        $query = "SELECT COALESCE(SUM(CASE WHEN type = 'earned' THEN points ELSE 0 END), 0) as total_points
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['total_points'] : 0;
    }

    /**
     * Get points statistics for a user
     */
    public function getUserPointsStats($user_id) {
        $query = "SELECT 
                    COALESCE(SUM(CASE WHEN type = 'earned' THEN points ELSE 0 END), 0) as total_earned,
                    COALESCE(SUM(CASE WHEN type = 'earned' AND source = 'tournament' THEN points ELSE 0 END), 0) as tournament_points,
                    COALESCE(SUM(CASE WHEN type = 'earned' AND source = 'challenge' THEN points ELSE 0 END), 0) as challenge_points,
                    COALESCE(SUM(CASE WHEN type = 'earned' AND source = 'bonus' THEN points ELSE 0 END), 0) as bonus_points,
                    COUNT(CASE WHEN type = 'earned' THEN 1 END) as total_transactions
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
}
?>