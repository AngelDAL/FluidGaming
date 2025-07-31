<?php
/**
 * Tournament model
 */

class Tournament {
    private $conn;
    private $table_name = "tournaments";

    public $id;
    public $event_id;
    public $name;
    public $game_image;
    public $scheduled_time;
    public $points_reward;
    public $specifications;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Validate tournament input
     */
    public function validateTournament($name, $event_id, $scheduled_time, $points_reward, $specifications = null) {
        $errors = [];

        // Validate name
        if (empty($name)) {
            $errors[] = "El nombre del torneo es requerido";
        } elseif (strlen($name) < 3 || strlen($name) > 191) {
            $errors[] = "El nombre debe tener entre 3 y 191 caracteres";
        }

        // Validate event_id
        if (empty($event_id) || !is_numeric($event_id)) {
            $errors[] = "ID del evento es requerido y debe ser numérico";
        } else {
            // Check if event exists and is active
            $eventQuery = "SELECT id, start_date, end_date FROM events WHERE id = :event_id AND is_active = 1";
            $eventStmt = $this->conn->prepare($eventQuery);
            $eventStmt->bindParam(':event_id', $event_id);
            $eventStmt->execute();
            
            if ($eventStmt->rowCount() === 0) {
                $errors[] = "El evento especificado no existe o no está activo";
            } else {
                $event = $eventStmt->fetch();
                // Validate scheduled_time is within event dates
                if (!empty($scheduled_time)) {
                    $scheduledDateTime = new DateTime($scheduled_time);
                    $eventStart = new DateTime($event['start_date']);
                    $eventEnd = new DateTime($event['end_date']);
                    
                    if ($scheduledDateTime < $eventStart || $scheduledDateTime > $eventEnd) {
                        $errors[] = "La fecha del torneo debe estar dentro del rango del evento";
                    }
                }
            }
        }

        // Validate scheduled_time
        if (empty($scheduled_time)) {
            $errors[] = "La fecha y hora del torneo es requerida";
        } else {
            $scheduledDateTime = new DateTime($scheduled_time);
            $now = new DateTime();
            
            if ($scheduledDateTime <= $now) {
                $errors[] = "La fecha del torneo debe ser posterior a la fecha actual";
            }
        }

        // Validate points_reward
        if (empty($points_reward) || !is_numeric($points_reward)) {
            $errors[] = "Los puntos de recompensa son requeridos y deben ser numéricos";
        } elseif ($points_reward < 1 || $points_reward > 10000) {
            $errors[] = "Los puntos de recompensa deben estar entre 1 y 10000";
        }

        // Validate specifications JSON if provided
        if (!empty($specifications)) {
            if (is_string($specifications)) {
                $decodedSpecs = json_decode($specifications, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "Las especificaciones deben ser un JSON válido";
                }
            } elseif (is_array($specifications)) {
                // Convert array to JSON string for validation
                $specifications = json_encode($specifications);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "Error al procesar las especificaciones";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate and process game image upload
     */
    public function validateGameImage($imageFile) {
        if (!isset($imageFile) || $imageFile['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'image_path' => null];
        }

        if ($imageFile['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir la imagen'];
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($imageFile['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Tipo de archivo no permitido. Solo se permiten imágenes JPEG, PNG, GIF y WebP'];
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($imageFile['size'] > $maxSize) {
            return ['success' => false, 'error' => 'La imagen es demasiado grande. Tamaño máximo: 5MB'];
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/tournaments/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'error' => 'Error al crear directorio de subida'];
            }
        }

        // Generate unique filename
        $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
        $filename = uniqid('tournament_') . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
            // Return relative path for database storage
            return ['success' => true, 'image_path' => 'uploads/tournaments/' . $filename];
        } else {
            return ['success' => false, 'error' => 'Error al guardar la imagen'];
        }
    }

    /**
     * Check if tournament name already exists for the same event
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
     * Create new tournament
     */
    public function create($name, $event_id, $scheduled_time, $points_reward, $specifications = null, $game_image = null) {
        // Validate input
        $errors = $this->validateTournament($name, $event_id, $scheduled_time, $points_reward, $specifications);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists in this event
        if ($this->nameExistsInEvent($name, $event_id)) {
            return ['success' => false, 'errors' => ['Ya existe un torneo con este nombre en el evento']];
        }

        // Process specifications
        $specsJson = null;
        if (!empty($specifications)) {
            if (is_array($specifications)) {
                $specsJson = json_encode($specifications);
            } else {
                $specsJson = $specifications;
            }
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (name, event_id, scheduled_time, points_reward, specifications, game_image) 
                  VALUES (:name, :event_id, :scheduled_time, :points_reward, :specifications, :game_image)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':scheduled_time', $scheduled_time);
        $stmt->bindParam(':points_reward', $points_reward);
        $stmt->bindParam(':specifications', $specsJson);
        $stmt->bindParam(':game_image', $game_image);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return ['success' => true, 'tournament_id' => $this->id];
        }

        return ['success' => false, 'errors' => ['Error al crear el torneo']];
    }

    /**
     * Update existing tournament
     */
    public function update($id, $name, $event_id, $scheduled_time, $points_reward, $specifications = null, $game_image = null) {
        // Validate input
        $errors = $this->validateTournament($name, $event_id, $scheduled_time, $points_reward, $specifications);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists in this event (excluding current tournament)
        if ($this->nameExistsInEvent($name, $event_id, $id)) {
            return ['success' => false, 'errors' => ['Ya existe un torneo con este nombre en el evento']];
        }

        // Process specifications
        $specsJson = null;
        if (!empty($specifications)) {
            if (is_array($specifications)) {
                $specsJson = json_encode($specifications);
            } else {
                $specsJson = $specifications;
            }
        }

        // Build query based on whether image is being updated
        if ($game_image !== null) {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, event_id = :event_id, scheduled_time = :scheduled_time, 
                          points_reward = :points_reward, specifications = :specifications, game_image = :game_image 
                      WHERE id = :id";
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, event_id = :event_id, scheduled_time = :scheduled_time, 
                          points_reward = :points_reward, specifications = :specifications 
                      WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':scheduled_time', $scheduled_time);
        $stmt->bindParam(':points_reward', $points_reward);
        $stmt->bindParam(':specifications', $specsJson);
        
        if ($game_image !== null) {
            $stmt->bindParam(':game_image', $game_image);
        }

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al actualizar el torneo']];
    }

    /**
     * Delete tournament
     */
    public function delete($id) {
        // Check if tournament has associated point transactions
        $checkQuery = "SELECT COUNT(*) as count FROM point_transactions WHERE tournament_id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        $result = $checkStmt->fetch();

        if ($result['count'] > 0) {
            return ['success' => false, 'errors' => ['No se puede eliminar un torneo que tiene transacciones de puntos asociadas']];
        }

        // Get tournament data to delete image file
        $tournament = $this->getById($id);
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            // Delete image file if exists
            if ($tournament && !empty($tournament['game_image'])) {
                $imagePath = '../' . $tournament['game_image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al eliminar el torneo']];
    }

    /**
     * Get tournament by ID
     */
    public function getById($id) {
        $query = "SELECT t.*, e.name as event_name, e.start_date as event_start, e.end_date as event_end 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN events e ON t.event_id = e.id 
                  WHERE t.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $tournament = $stmt->fetch();
            // Decode specifications JSON
            if (!empty($tournament['specifications'])) {
                $tournament['specifications'] = json_decode($tournament['specifications'], true);
            }
            return $tournament;
        }

        return false;
    }

    /**
     * Get tournaments by event ID
     */
    public function getByEventId($event_id, $status = null) {
        $whereClause = "WHERE t.event_id = :event_id";
        $params = [':event_id' => $event_id];
        
        if ($status) {
            $whereClause .= " AND t.status = :status";
            $params[':status'] = $status;
        }

        $query = "SELECT t.*, e.name as event_name 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN events e ON t.event_id = e.id 
                  $whereClause
                  ORDER BY t.scheduled_time ASC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $tournaments = $stmt->fetchAll();

        // Decode specifications JSON for each tournament
        foreach ($tournaments as &$tournament) {
            if (!empty($tournament['specifications'])) {
                $tournament['specifications'] = json_decode($tournament['specifications'], true);
            }
        }

        return $tournaments;
    }

    /**
     * Get all tournaments with pagination and filters
     */
    public function getAll($page = 1, $limit = 10, $event_id = null, $status = null, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(t.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($event_id) {
            $conditions[] = "t.event_id = :event_id";
            $params[':event_id'] = $event_id;
        }
        
        if ($status) {
            $conditions[] = "t.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT t.*, e.name as event_name 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN events e ON t.event_id = e.id 
                  $whereClause
                  ORDER BY t.scheduled_time DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $tournaments = $stmt->fetchAll();

        // Decode specifications JSON for each tournament
        foreach ($tournaments as &$tournament) {
            if (!empty($tournament['specifications'])) {
                $tournament['specifications'] = json_decode($tournament['specifications'], true);
            }
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " t $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        return [
            'tournaments' => $tournaments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Update tournament status
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['scheduled', 'active', 'completed'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'errors' => ['Estado de torneo inválido']];
        }

        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al actualizar el estado del torneo']];
    }

    /**
     * Get upcoming tournaments
     */
    public function getUpcoming($limit = 10) {
        $query = "SELECT t.*, e.name as event_name 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN events e ON t.event_id = e.id 
                  WHERE t.status = 'scheduled' 
                  AND t.scheduled_time > NOW() 
                  AND e.is_active = 1 
                  ORDER BY t.scheduled_time ASC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $tournaments = $stmt->fetchAll();

        // Decode specifications JSON for each tournament
        foreach ($tournaments as &$tournament) {
            if (!empty($tournament['specifications'])) {
                $tournament['specifications'] = json_decode($tournament['specifications'], true);
            }
        }

        return $tournaments;
    }

    /**
     * Get active tournaments
     */
    public function getActive() {
        $query = "SELECT t.*, e.name as event_name 
                  FROM " . $this->table_name . " t 
                  LEFT JOIN events e ON t.event_id = e.id 
                  WHERE t.status = 'active' 
                  AND e.is_active = 1 
                  ORDER BY t.scheduled_time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $tournaments = $stmt->fetchAll();

        // Decode specifications JSON for each tournament
        foreach ($tournaments as &$tournament) {
            if (!empty($tournament['specifications'])) {
                $tournament['specifications'] = json_decode($tournament['specifications'], true);
            }
        }

        return $tournaments;
    }
}
?>