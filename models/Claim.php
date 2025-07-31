<?php
/**
 * Claim model
 */

class Claim {
    private $conn;
    private $table_name = "claims";

    public $id;
    public $user_id;
    public $product_id;
    public $stand_id;
    public $processed_by;
    public $timestamp;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Validate claim input
     */
    public function validateClaim($user_id, $product_id, $stand_id) {
        $errors = [];

        // Validate user_id
        if (empty($user_id) || !is_numeric($user_id)) {
            $errors[] = "ID del usuario es requerido y debe ser numérico";
        } else {
            // Check if user exists
            $userQuery = "SELECT id, total_points FROM users WHERE id = :user_id";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->bindParam(':user_id', $user_id);
            $userStmt->execute();
            
            if ($userStmt->rowCount() === 0) {
                $errors[] = "El usuario especificado no existe";
            }
        }

        // Validate product_id
        if (empty($product_id) || !is_numeric($product_id)) {
            $errors[] = "ID del producto es requerido y debe ser numérico";
        } else {
            // Check if product exists and is active
            $productQuery = "SELECT id, points_required, stand_id, is_active FROM products WHERE id = :product_id";
            $productStmt = $this->conn->prepare($productQuery);
            $productStmt->bindParam(':product_id', $product_id);
            $productStmt->execute();
            
            if ($productStmt->rowCount() === 0) {
                $errors[] = "El producto especificado no existe";
            } else {
                $product = $productStmt->fetch();
                if (!$product['is_active']) {
                    $errors[] = "El producto no está disponible";
                }
                // Validate that stand_id matches product's stand
                if ($product['stand_id'] != $stand_id) {
                    $errors[] = "El producto no pertenece al stand especificado";
                }
            }
        }

        // Validate stand_id
        if (empty($stand_id) || !is_numeric($stand_id)) {
            $errors[] = "ID del stand es requerido y debe ser numérico";
        } else {
            // Check if stand exists
            $standQuery = "SELECT id FROM stands WHERE id = :stand_id";
            $standStmt = $this->conn->prepare($standQuery);
            $standStmt->bindParam(':stand_id', $stand_id);
            $standStmt->execute();
            
            if ($standStmt->rowCount() === 0) {
                $errors[] = "El stand especificado no existe";
            }
        }

        return $errors;
    }

    /**
     * Check if user has already claimed this product (uniqueness validation)
     */
    public function hasUserClaimedProduct($user_id, $product_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND product_id = :product_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Verify user has sufficient points without deducting them
     */
    public function verifyUserPoints($user_id, $product_id) {
        $query = "SELECT u.total_points, p.points_required, p.name as product_name
                  FROM users u, products p 
                  WHERE u.id = :user_id AND p.id = :product_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch();
            return [
                'has_sufficient_points' => $result['total_points'] >= $result['points_required'],
                'user_points' => $result['total_points'],
                'required_points' => $result['points_required'],
                'product_name' => $result['product_name']
            ];
        }
        
        return false;
    }

    /**
     * Create new claim (without deducting points)
     */
    public function create($user_id, $product_id, $stand_id, $processed_by = null) {
        // Validate input
        $errors = $this->validateClaim($user_id, $product_id, $stand_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check uniqueness - user can only claim each product once
        if ($this->hasUserClaimedProduct($user_id, $product_id)) {
            return ['success' => false, 'errors' => ['El usuario ya ha reclamado este producto']];
        }

        // Verify user has sufficient points
        $pointsCheck = $this->verifyUserPoints($user_id, $product_id);
        if (!$pointsCheck || !$pointsCheck['has_sufficient_points']) {
            return [
                'success' => false, 
                'errors' => ['Puntos insuficientes para reclamar este producto'],
                'points_info' => $pointsCheck
            ];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, product_id, stand_id, processed_by, status) 
                  VALUES (:user_id, :product_id, :stand_id, :processed_by, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $status = $processed_by ? 'completed' : 'pending';
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':stand_id', $stand_id);
        $stmt->bindParam(':processed_by', $processed_by);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return [
                'success' => true, 
                'claim_id' => $this->id,
                'points_info' => $pointsCheck
            ];
        }

        return ['success' => false, 'errors' => ['Error al crear el reclamo']];
    }

    /**
     * Process claim (mark as completed by stand manager)
     */
    public function processClaim($claim_id, $processed_by) {
        // Validate that the claim exists and is pending
        $claim = $this->getById($claim_id);
        if (!$claim) {
            return ['success' => false, 'errors' => ['El reclamo no existe']];
        }

        if ($claim['status'] === 'completed') {
            return ['success' => false, 'errors' => ['El reclamo ya ha sido procesado']];
        }

        // Verify user still has sufficient points
        $pointsCheck = $this->verifyUserPoints($claim['user_id'], $claim['product_id']);
        if (!$pointsCheck || !$pointsCheck['has_sufficient_points']) {
            return [
                'success' => false, 
                'errors' => ['El usuario ya no tiene puntos suficientes'],
                'points_info' => $pointsCheck
            ];
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET processed_by = :processed_by, status = 'completed', timestamp = CURRENT_TIMESTAMP
                  WHERE id = :claim_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':processed_by', $processed_by);
        $stmt->bindParam(':claim_id', $claim_id);

        if ($stmt->execute()) {
            return [
                'success' => true,
                'points_info' => $pointsCheck
            ];
        }

        return ['success' => false, 'errors' => ['Error al procesar el reclamo']];
    }

    /**
     * Get claim by ID
     */
    public function getById($id) {
        $query = "SELECT c.*, u.nickname as user_nickname, u.total_points as user_points,
                         p.name as product_name, p.points_required, p.image_url as product_image,
                         s.name as stand_name, 
                         pm.nickname as processed_by_name
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.user_id = u.id 
                  LEFT JOIN products p ON c.product_id = p.id 
                  LEFT JOIN stands s ON c.stand_id = s.id 
                  LEFT JOIN users pm ON c.processed_by = pm.id
                  WHERE c.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Get claims by user ID
     */
    public function getByUserId($user_id, $status = null) {
        $whereClause = "WHERE c.user_id = :user_id";
        $params = [':user_id' => $user_id];
        
        if ($status) {
            $whereClause .= " AND c.status = :status";
            $params[':status'] = $status;
        }

        $query = "SELECT c.*, u.nickname as user_nickname, u.total_points as user_points,
                         p.name as product_name, p.points_required, p.image_url as product_image,
                         s.name as stand_name, 
                         pm.nickname as processed_by_name
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.user_id = u.id 
                  LEFT JOIN products p ON c.product_id = p.id 
                  LEFT JOIN stands s ON c.stand_id = s.id 
                  LEFT JOIN users pm ON c.processed_by = pm.id
                  $whereClause
                  ORDER BY c.timestamp DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get claims by stand ID
     */
    public function getByStandId($stand_id, $status = null) {
        $whereClause = "WHERE c.stand_id = :stand_id";
        $params = [':stand_id' => $stand_id];
        
        if ($status) {
            $whereClause .= " AND c.status = :status";
            $params[':status'] = $status;
        }

        $query = "SELECT c.*, u.nickname as user_nickname, u.total_points as user_points,
                         p.name as product_name, p.points_required, p.image_url as product_image,
                         s.name as stand_name, 
                         pm.nickname as processed_by_name
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.user_id = u.id 
                  LEFT JOIN products p ON c.product_id = p.id 
                  LEFT JOIN stands s ON c.stand_id = s.id 
                  LEFT JOIN users pm ON c.processed_by = pm.id
                  $whereClause
                  ORDER BY c.timestamp DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get all claims with pagination and filters
     */
    public function getAll($page = 1, $limit = 10, $stand_id = null, $status = null, $user_id = null) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if ($stand_id) {
            $conditions[] = "c.stand_id = :stand_id";
            $params[':stand_id'] = $stand_id;
        }
        
        if ($status) {
            $conditions[] = "c.status = :status";
            $params[':status'] = $status;
        }
        
        if ($user_id) {
            $conditions[] = "c.user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT c.*, u.nickname as user_nickname, u.total_points as user_points,
                         p.name as product_name, p.points_required, p.image_url as product_image,
                         s.name as stand_name, 
                         pm.nickname as processed_by_name
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.user_id = u.id 
                  LEFT JOIN products p ON c.product_id = p.id 
                  LEFT JOIN stands s ON c.stand_id = s.id 
                  LEFT JOIN users pm ON c.processed_by = pm.id
                  $whereClause
                  ORDER BY c.timestamp DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $claims = $stmt->fetchAll();

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " c $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        return [
            'claims' => $claims,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get pending claims for a stand manager
     */
    public function getPendingClaimsForManager($manager_id) {
        $query = "SELECT c.*, u.nickname as user_nickname, u.total_points as user_points,
                         p.name as product_name, p.points_required, p.image_url as product_image,
                         s.name as stand_name
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.user_id = u.id 
                  LEFT JOIN products p ON c.product_id = p.id 
                  LEFT JOIN stands s ON c.stand_id = s.id 
                  WHERE s.manager_id = :manager_id AND c.status = 'pending'
                  ORDER BY c.timestamp ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':manager_id', $manager_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get claim statistics
     */
    public function getClaimStats($stand_id = null, $user_id = null) {
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if ($stand_id) {
            $conditions[] = "c.stand_id = :stand_id";
            $params[':stand_id'] = $stand_id;
        }
        
        if ($user_id) {
            $conditions[] = "c.user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT 
                    COUNT(*) as total_claims,
                    COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as completed_claims,
                    COUNT(CASE WHEN c.status = 'pending' THEN 1 END) as pending_claims,
                    SUM(CASE WHEN c.status = 'completed' THEN p.points_required ELSE 0 END) as total_points_claimed
                  FROM " . $this->table_name . " c
                  LEFT JOIN products p ON c.product_id = p.id
                  $whereClause";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return [
            'total_claims' => 0,
            'completed_claims' => 0,
            'pending_claims' => 0,
            'total_points_claimed' => 0
        ];
    }

    /**
     * Cancel pending claim
     */
    public function cancelClaim($claim_id, $user_id = null) {
        // Get claim details
        $claim = $this->getById($claim_id);
        if (!$claim) {
            return ['success' => false, 'errors' => ['El reclamo no existe']];
        }

        // Check if user is authorized to cancel (either the user who made the claim or admin)
        if ($user_id && $claim['user_id'] != $user_id) {
            // Check if user is admin
            $userQuery = "SELECT role FROM users WHERE id = :user_id";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->bindParam(':user_id', $user_id);
            $userStmt->execute();
            
            if ($userStmt->rowCount() === 0) {
                return ['success' => false, 'errors' => ['Usuario no autorizado']];
            }
            
            $user = $userStmt->fetch();
            if ($user['role'] !== 'admin') {
                return ['success' => false, 'errors' => ['No tienes permisos para cancelar este reclamo']];
            }
        }

        if ($claim['status'] === 'completed') {
            return ['success' => false, 'errors' => ['No se puede cancelar un reclamo ya procesado']];
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :claim_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':claim_id', $claim_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al cancelar el reclamo']];
    }
}
?>