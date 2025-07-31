<?php
/**
 * Event model
 */

class Event {
    private $conn;
    private $table_name = "events";

    public $id;
    public $name;
    public $description;
    public $start_date;
    public $end_date;
    public $is_active;
    public $created_by;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Validate event input
     */
    public function validateEvent($name, $description, $start_date, $end_date) {
        $errors = [];

        // Validate name
        if (empty($name)) {
            $errors[] = "El nombre del evento es requerido";
        } elseif (strlen($name) < 3 || strlen($name) > 191) {
            $errors[] = "El nombre debe tener entre 3 y 191 caracteres";
        }

        // Validate dates
        if (empty($start_date)) {
            $errors[] = "La fecha de inicio es requerida";
        }

        if (empty($end_date)) {
            $errors[] = "La fecha de fin es requerida";
        }

        if (!empty($start_date) && !empty($end_date)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $now = new DateTime();

            // Check if start date is in the future
            if ($start < $now) {
                $errors[] = "La fecha de inicio debe ser posterior a la fecha actual";
            }

            // Check if end date is after start date
            if ($end <= $start) {
                $errors[] = "La fecha de fin debe ser posterior a la fecha de inicio";
            }

            // Check minimum duration (at least 1 hour)
            $interval = $start->diff($end);
            $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            if ($totalMinutes < 60) {
                $errors[] = "El evento debe durar al menos 1 hora";
            }
        }

        return $errors;
    }

    /**
     * Check if event name already exists
     */
    public function nameExists($name, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE name = :name";
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Create new event
     */
    public function create($name, $description, $start_date, $end_date, $created_by) {
        // Validate input
        $errors = $this->validateEvent($name, $description, $start_date, $end_date);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists
        if ($this->nameExists($name)) {
            return ['success' => false, 'errors' => ['Ya existe un evento con este nombre']];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, start_date, end_date, created_by) 
                  VALUES (:name, :description, :start_date, :end_date, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':created_by', $created_by);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return ['success' => true, 'event_id' => $this->id];
        }

        return ['success' => false, 'errors' => ['Error al crear el evento']];
    }

    /**
     * Update existing event
     */
    public function update($id, $name, $description, $start_date, $end_date) {
        // Validate input
        $errors = $this->validateEvent($name, $description, $start_date, $end_date);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists (excluding current event)
        if ($this->nameExists($name, $id)) {
            return ['success' => false, 'errors' => ['Ya existe un evento con este nombre']];
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, description = :description, 
                      start_date = :start_date, end_date = :end_date 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al actualizar el evento']];
    }

    /**
     * Delete event
     */
    public function delete($id) {
        // Check if event has associated tournaments
        $checkQuery = "SELECT COUNT(*) as count FROM tournaments WHERE event_id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        $result = $checkStmt->fetch();

        if ($result['count'] > 0) {
            return ['success' => false, 'errors' => ['No se puede eliminar un evento que tiene torneos asociados']];
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al eliminar el evento']];
    }

    /**
     * Get event by ID
     */
    public function getById($id) {
        $query = "SELECT e.*, u.nickname as created_by_name 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.created_by = u.id 
                  WHERE e.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Get all events with pagination
     */
    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE e.name LIKE :search OR e.description LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $query = "SELECT e.*, u.nickname as created_by_name 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.created_by = u.id 
                  $whereClause
                  ORDER BY e.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $events = $stmt->fetchAll();

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " e $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        return [
            'events' => $events,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get active events (current date is between start_date and end_date)
     */
    public function getActiveEvents() {
        $query = "SELECT e.*, u.nickname as created_by_name 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.created_by = u.id 
                  WHERE e.is_active = 1 
                  AND NOW() BETWEEN e.start_date AND e.end_date 
                  ORDER BY e.start_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Check if an event is currently active
     */
    public function isEventActive($eventId) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE id = :id 
                  AND is_active = 1 
                  AND NOW() BETWEEN start_date AND end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $eventId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Toggle event active status
     */
    public function toggleActive($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = NOT is_active 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al cambiar el estado del evento']];
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents($limit = 5) {
        $query = "SELECT e.*, u.nickname as created_by_name 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.created_by = u.id 
                  WHERE e.is_active = 1 
                  AND e.start_date > NOW() 
                  ORDER BY e.start_date ASC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get events by date range
     */
    public function getEventsByDateRange($startDate, $endDate) {
        $query = "SELECT e.*, u.nickname as created_by_name 
                  FROM " . $this->table_name . " e 
                  LEFT JOIN users u ON e.created_by = u.id 
                  WHERE (e.start_date BETWEEN :start_date AND :end_date) 
                  OR (e.end_date BETWEEN :start_date AND :end_date) 
                  OR (e.start_date <= :start_date AND e.end_date >= :end_date) 
                  ORDER BY e.start_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>