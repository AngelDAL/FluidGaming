<?php
/**
 * Stand model
 */

class Stand {
    private $conn;
    private $table_name = "stands";

    public $id;
    public $name;
    public $manager_id;
    public $event_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Validate stand input
     */
    public function validateStand($name, $manager_id, $event_id) {
        $errors = [];

        // Validate name
        if (empty($name)) {
            $errors[] = "El nombre del stand es requerido";
        } elseif (strlen($name) < 3 || strlen($name) > 191) {
            $errors[] = "El nombre debe tener entre 3 y 191 caracteres";
        }

        // Validate manager_id
        if (empty($manager_id) || !is_numeric($manager_id)) {
            $errors[] = "ID del encargado es requerido y debe ser numérico";
        } else {
            // Check if manager exists and has appropriate role
            $managerQuery = "SELECT id, role FROM users WHERE id = :manager_id";
            $managerStmt = $this->conn->prepare($managerQuery);
            $managerStmt->bindParam(':manager_id', $manager_id);
            $managerStmt->execute();
            
            if ($managerStmt->rowCount() === 0) {
                $errors[] = "El encargado especificado no existe";
            } else {
                $manager = $managerStmt->fetch();
                $allowedRoles = ['stand_manager', 'admin'];
                if (!in_array($manager['role'], $allowedRoles)) {
                    $errors[] = "El usuario debe tener rol de encargado de stand o administrador";
                }
            }
        }

        // Validate event_id
        if (empty($event_id) || !is_numeric($event_id)) {
            $errors[] = "ID del evento es requerido y debe ser numérico";
        } else {
            // Check if event exists and is active
            $eventQuery = "SELECT id FROM events WHERE id = :event_id AND is_active = 1";
            $eventStmt = $this->conn->prepare($eventQuery);
            $eventStmt->bindParam(':event_id', $event_id);
            $eventStmt->execute();
            
            if ($eventStmt->rowCount() === 0) {
                $errors[] = "El evento especificado no existe o no está activo";
            }
        }

        return $errors;
    }

    /**
     * Check if stand name already exists for the same event
     */
    public function nameExistsInEvent($name, $event_id, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE name = :name AND event_id = :event_id";
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':event_id', $event_id);
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Create new stand
     */
    public function create($name, $manager_id, $event_id) {
        // Validate input
        $errors = $this->validateStand($name, $manager_id, $event_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists in this event
        if ($this->nameExistsInEvent($name, $event_id)) {
            return ['success' => false, 'errors' => ['Ya existe un stand con este nombre en el evento']];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (name, manager_id, event_id) 
                  VALUES (:name, :manager_id, :event_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':manager_id', $manager_id);
        $stmt->bindParam(':event_id', $event_id);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return ['success' => true, 'stand_id' => $this->id];
        }

        return ['success' => false, 'errors' => ['Error al crear el stand']];
    }

    /**
     * Update existing stand
     */
    public function update($id, $name, $manager_id, $event_id) {
        // Validate input
        $errors = $this->validateStand($name, $manager_id, $event_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists in this event (excluding current stand)
        if ($this->nameExistsInEvent($name, $event_id, $id)) {
            return ['success' => false, 'errors' => ['Ya existe un stand con este nombre en el evento']];
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, manager_id = :manager_id, event_id = :event_id 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':manager_id', $manager_id);
        $stmt->bindParam(':event_id', $event_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al actualizar el stand']];
    }

    /**
     * Delete stand
     */
    public function delete($id) {
        // Check if stand has associated products
        $checkQuery = "SELECT COUNT(*) as count FROM products WHERE stand_id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        $result = $checkStmt->fetch();

        if ($result['count'] > 0) {
            return ['success' => false, 'errors' => ['No se puede eliminar un stand que tiene productos asociados']];
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al eliminar el stand']];
    }

    /**
     * Get stand by ID
     */
    public function getById($id) {
        $query = "SELECT s.*, u.nickname as manager_name, e.name as event_name 
                  FROM " . $this->table_name . " s 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  LEFT JOIN events e ON s.event_id = e.id 
                  WHERE s.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Get stands by event ID
     */
    public function getByEventId($event_id) {
        $query = "SELECT s.*, u.nickname as manager_name, e.name as event_name 
                  FROM " . $this->table_name . " s 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  LEFT JOIN events e ON s.event_id = e.id 
                  WHERE s.event_id = :event_id
                  ORDER BY s.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get stands by manager ID
     */
    public function getByManagerId($manager_id) {
        $query = "SELECT s.*, u.nickname as manager_name, e.name as event_name 
                  FROM " . $this->table_name . " s 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  LEFT JOIN events e ON s.event_id = e.id 
                  WHERE s.manager_id = :manager_id
                  ORDER BY s.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':manager_id', $manager_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get all stands with pagination and filters
     */
    public function getAll($page = 1, $limit = 10, $event_id = null, $manager_id = null, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(s.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($event_id) {
            $conditions[] = "s.event_id = :event_id";
            $params[':event_id'] = $event_id;
        }
        
        if ($manager_id) {
            $conditions[] = "s.manager_id = :manager_id";
            $params[':manager_id'] = $manager_id;
        }
        
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT s.*, u.nickname as manager_name, e.name as event_name 
                  FROM " . $this->table_name . " s 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  LEFT JOIN events e ON s.event_id = e.id 
                  $whereClause
                  ORDER BY s.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $stands = $stmt->fetchAll();

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " s $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        return [
            'stands' => $stands,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get stands with product count
     */
    public function getStandsWithProductCount($event_id = null) {
        $whereClause = '';
        $params = [];
        
        if ($event_id) {
            $whereClause = 'WHERE s.event_id = :event_id';
            $params[':event_id'] = $event_id;
        }

        $query = "SELECT s.*, u.nickname as manager_name, e.name as event_name,
                         COUNT(p.id) as product_count
                  FROM " . $this->table_name . " s 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  LEFT JOIN events e ON s.event_id = e.id 
                  LEFT JOIN products p ON s.id = p.stand_id AND p.is_active = 1
                  $whereClause
                  GROUP BY s.id
                  ORDER BY s.name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get available stand managers (users with stand_manager or admin role)
     */
    public function getAvailableManagers() {
        $query = "SELECT id, nickname, email, role 
                  FROM users 
                  WHERE role IN ('stand_manager', 'admin') 
                  ORDER BY nickname ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>