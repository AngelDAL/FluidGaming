<?php
/**
 * Product model
 */

class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $points_required;
    public $stand_id;
    public $image_url;
    public $is_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Validate product input
     */
    public function validateProduct($name, $description, $points_required, $stand_id) {
        $errors = [];

        // Validate name
        if (empty($name)) {
            $errors[] = "El nombre del producto es requerido";
        } elseif (strlen($name) < 2 || strlen($name) > 191) {
            $errors[] = "El nombre debe tener entre 2 y 191 caracteres";
        }

        // Validate points_required
        if (empty($points_required) || !is_numeric($points_required)) {
            $errors[] = "Los puntos requeridos son obligatorios y deben ser numéricos";
        } elseif ($points_required < 1 || $points_required > 100000) {
            $errors[] = "Los puntos requeridos deben estar entre 1 y 100000";
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

        // Validate description length if provided
        if (!empty($description) && strlen($description) > 1000) {
            $errors[] = "La descripción no puede exceder 1000 caracteres";
        }

        return $errors;
    }

    /**
     * Validate and process product image upload
     */
    public function validateProductImage($imageFile) {
        if (!isset($imageFile) || $imageFile['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'image_url' => null];
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

        // Validate file size (max 3MB)
        $maxSize = 3 * 1024 * 1024; // 3MB
        if ($imageFile['size'] > $maxSize) {
            return ['success' => false, 'error' => 'La imagen es demasiado grande. Tamaño máximo: 3MB'];
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'error' => 'Error al crear directorio de subida'];
            }
        }

        // Generate unique filename
        $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_') . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
            // Return relative path for database storage
            return ['success' => true, 'image_url' => 'uploads/products/' . $filename];
        } else {
            return ['success' => false, 'error' => 'Error al guardar la imagen'];
        }
    }

    /**
     * Check if product name already exists for the same stand
     */
    public function nameExistsInStand($name, $stand_id, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE name = :name AND stand_id = :stand_id";
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':stand_id', $stand_id);
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Create new product
     */
    public function create($name, $description, $points_required, $stand_id, $image_url = null) {
        // Validate input
        $errors = $this->validateProduct($name, $description, $points_required, $stand_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists in this stand
        if ($this->nameExistsInStand($name, $stand_id)) {
            return ['success' => false, 'errors' => ['Ya existe un producto con este nombre en el stand']];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, points_required, stand_id, image_url) 
                  VALUES (:name, :description, :points_required, :stand_id, :image_url)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':points_required', $points_required);
        $stmt->bindParam(':stand_id', $stand_id);
        $stmt->bindParam(':image_url', $image_url);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return ['success' => true, 'product_id' => $this->id];
        }

        return ['success' => false, 'errors' => ['Error al crear el producto']];
    }

    /**
     * Update existing product
     */
    public function update($id, $name, $description, $points_required, $stand_id, $image_url = null) {
        // Validate input
        $errors = $this->validateProduct($name, $description, $points_required, $stand_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if name already exists in this stand (excluding current product)
        if ($this->nameExistsInStand($name, $stand_id, $id)) {
            return ['success' => false, 'errors' => ['Ya existe un producto con este nombre en el stand']];
        }

        // Build query based on whether image is being updated
        if ($image_url !== null) {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, description = :description, points_required = :points_required, 
                          stand_id = :stand_id, image_url = :image_url 
                      WHERE id = :id";
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, description = :description, points_required = :points_required, 
                          stand_id = :stand_id 
                      WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':points_required', $points_required);
        $stmt->bindParam(':stand_id', $stand_id);
        
        if ($image_url !== null) {
            $stmt->bindParam(':image_url', $image_url);
        }

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al actualizar el producto']];
    }

    /**
     * Delete product
     */
    public function delete($id) {
        // Check if product has associated claims
        $checkQuery = "SELECT COUNT(*) as count FROM claims WHERE product_id = :id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        $result = $checkStmt->fetch();

        if ($result['count'] > 0) {
            return ['success' => false, 'errors' => ['No se puede eliminar un producto que tiene reclamos asociados']];
        }

        // Get product data to delete image file
        $product = $this->getById($id);
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            // Delete image file if exists
            if ($product && !empty($product['image_url'])) {
                $imagePath = '../' . $product['image_url'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            return ['success' => true];
        }

        return ['success' => false, 'errors' => ['Error al eliminar el producto']];
    }

    /**
     * Toggle product active status
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

        return ['success' => false, 'errors' => ['Error al cambiar el estado del producto']];
    }

    /**
     * Get product by ID
     */
    public function getById($id) {
        $query = "SELECT p.*, s.name as stand_name, s.manager_id, u.nickname as manager_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN stands s ON p.stand_id = s.id 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  WHERE p.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }

    /**
     * Get products by stand ID
     */
    public function getByStandId($stand_id, $active_only = false) {
        $whereClause = "WHERE p.stand_id = :stand_id";
        if ($active_only) {
            $whereClause .= " AND p.is_active = 1";
        }

        $query = "SELECT p.*, s.name as stand_name, s.manager_id, u.nickname as manager_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN stands s ON p.stand_id = s.id 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  $whereClause
                  ORDER BY p.points_required ASC, p.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':stand_id', $stand_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get all products with pagination and filters
     */
    public function getAll($page = 1, $limit = 10, $stand_id = null, $active_only = false, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($stand_id) {
            $conditions[] = "p.stand_id = :stand_id";
            $params[':stand_id'] = $stand_id;
        }
        
        if ($active_only) {
            $conditions[] = "p.is_active = 1";
        }
        
        if (!empty($conditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "SELECT p.*, s.name as stand_name, s.manager_id, u.nickname as manager_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN stands s ON p.stand_id = s.id 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  $whereClause
                  ORDER BY p.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $products = $stmt->fetchAll();

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->table_name . " p 
                       LEFT JOIN stands s ON p.stand_id = s.id 
                       $whereClause";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];

        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get products available for claiming by a user
     */
    public function getAvailableForUser($user_id, $event_id = null) {
        $whereClause = "WHERE p.is_active = 1 
                        AND c.id IS NULL"; // Product not yet claimed by user
        $params = [':user_id' => $user_id];
        
        if ($event_id) {
            $whereClause .= " AND s.event_id = :event_id";
            $params[':event_id'] = $event_id;
        }

        $query = "SELECT p.*, s.name as stand_name, s.manager_id, u.nickname as manager_name,
                         e.name as event_name
                  FROM " . $this->table_name . " p 
                  LEFT JOIN stands s ON p.stand_id = s.id 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  LEFT JOIN events e ON s.event_id = e.id
                  LEFT JOIN claims c ON p.id = c.product_id AND c.user_id = :user_id
                  $whereClause
                  ORDER BY p.points_required ASC, p.name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get products by points range
     */
    public function getByPointsRange($min_points, $max_points, $stand_id = null, $active_only = true) {
        $whereClause = "WHERE p.points_required BETWEEN :min_points AND :max_points";
        $params = [
            ':min_points' => $min_points,
            ':max_points' => $max_points
        ];
        
        if ($stand_id) {
            $whereClause .= " AND p.stand_id = :stand_id";
            $params[':stand_id'] = $stand_id;
        }
        
        if ($active_only) {
            $whereClause .= " AND p.is_active = 1";
        }

        $query = "SELECT p.*, s.name as stand_name, s.manager_id, u.nickname as manager_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN stands s ON p.stand_id = s.id 
                  LEFT JOIN users u ON s.manager_id = u.id 
                  $whereClause
                  ORDER BY p.points_required ASC, p.name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Check if user has already claimed a product
     */
    public function hasUserClaimed($user_id, $product_id) {
        $query = "SELECT COUNT(*) as count FROM claims 
                  WHERE user_id = :user_id AND product_id = :product_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Get product statistics
     */
    public function getProductStats($product_id) {
        $query = "SELECT 
                    COUNT(c.id) as total_claims,
                    COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as completed_claims,
                    COUNT(CASE WHEN c.status = 'pending' THEN 1 END) as pending_claims
                  FROM " . $this->table_name . " p
                  LEFT JOIN claims c ON p.id = c.product_id
                  WHERE p.id = :product_id
                  GROUP BY p.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return [
            'total_claims' => 0,
            'completed_claims' => 0,
            'pending_claims' => 0
        ];
    }
}
?>