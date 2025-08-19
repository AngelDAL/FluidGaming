

<?php
/**
 * User model
 */

class User
{
    private $conn;
    private $table_name = "users";

    public $id;
    public $nickname;
    public $email;
    public $password_hash;
    public $profile_image;
    public $role;
    public $total_points;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtener todos los usuarios (id, nickname, total_points, profile_image)
     */
    public function getAll()
    {
        $query = "SELECT id, nickname, total_points, profile_image FROM " . $this->table_name . " ORDER BY nickname ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Validate user input for registration
     */
    public function validateRegistration($nickname, $email, $password, $profile_image = null)
    {
        $errors = [];

        // Validate nickname
        if (empty($nickname)) {
            $errors[] = "El nickname es requerido";
        } elseif (strlen($nickname) < 3 || strlen($nickname) > 50) {
            $errors[] = "El nickname debe tener entre 3 y 50 caracteres";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $nickname)) {
            $errors[] = "El nickname solo puede contener letras, números y guiones bajos";
        }

        // Validate email
        if (empty($email)) {
            $errors[] = "El email es requerido";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El formato del email no es válido";
        }

        // Validate password
        if (empty($password)) {
            $errors[] = "La contraseña es requerida";
        } elseif (strlen($password) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }

        // Validate profile image if provided
        if ($profile_image && !empty($profile_image['tmp_name'])) {
            $imageErrors = $this->validateProfileImage($profile_image);
            $errors = array_merge($errors, $imageErrors);
        }

        return $errors;
    }

    /**
     * Validate profile image
     */
    public function validateProfileImage($image)
    {
        $errors = [];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($image['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error al subir la imagen";
            return $errors;
        }

        if (!in_array($image['type'], $allowedTypes)) {
            $errors[] = "Solo se permiten imágenes JPG, PNG o GIF";
        }

        if ($image['size'] > $maxSize) {
            $errors[] = "La imagen no puede ser mayor a 5MB";
        }

        // Validate image dimensions
        $imageInfo = getimagesize($image['tmp_name']);
        if (!$imageInfo) {
            $errors[] = "El archivo no es una imagen válida";
        }

        return $errors;
    }

    /**
     * Check if nickname already exists
     */
    public function nicknameExists($nickname)
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE nickname = :nickname LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nickname', $nickname);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Check if email already exists
     */
    public function emailExists($email)
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Create new user
     */
    public function create($nickname, $email, $password, $profile_image = null)
    {
        // Validate input
        $errors = $this->validateRegistration($nickname, $email, $password, $profile_image);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if nickname or email already exists
        if ($this->nicknameExists($nickname)) {
            return ['success' => false, 'errors' => ['El nickname ya está en uso']];
        }

        if ($this->emailExists($email)) {
            return ['success' => false, 'errors' => ['El email ya está registrado']];
        }

        // Handle profile image upload or predefined selection
        $imagePath = null;
        if (isset($_POST['predefined_image']) && !empty($_POST['predefined_image'])) {
            // Use predefined image
            $predefinedId = $_POST['predefined_image'];
            $imagePath = $this->getPredefinedImagePath($predefinedId);
        } elseif ($profile_image && !empty($profile_image['tmp_name'])) {
            $imagePath = $this->uploadProfileImage($profile_image);
            if (!$imagePath) {
                return ['success' => false, 'errors' => ['Error al subir la imagen']];
            }
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $query = "INSERT INTO " . $this->table_name . " 
                  (nickname, email, password_hash, profile_image, role, total_points) 
                  VALUES (:nickname, :email, :password_hash, :profile_image, :role, :total_points)";

        $stmt = $this->conn->prepare($query);

        $role = 'user';
        $totalPoints = 0;

        $stmt->bindParam(':nickname', $nickname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->bindParam(':profile_image', $imagePath);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':total_points', $totalPoints);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return ['success' => true, 'user_id' => $this->id];
        }

        return ['success' => false, 'errors' => ['Error al crear el usuario']];
    }

    /**
     * Upload profile image
     */
    private function uploadProfileImage($image)
    {
        $uploadDir = '../uploads/profiles/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($image['tmp_name'], $filepath)) {
            return 'uploads/profiles/' . $filename;
        }

        return false;
    }

    /**
     * Get predefined image path
     */
    private function getPredefinedImagePath($imageId)
    {
        $predefinedImages = [
            '1' => 'https://via.placeholder.com/150/FF6B6B/FFFFFF?text=1',
            '2' => 'https://via.placeholder.com/150/4ECDC4/FFFFFF?text=2',
            '3' => 'https://via.placeholder.com/150/45B7D1/FFFFFF?text=3',
            '4' => 'https://via.placeholder.com/150/96CEB4/FFFFFF?text=4',
            '5' => 'https://via.placeholder.com/150/FFEAA7/333333?text=5',
            '6' => 'https://via.placeholder.com/150/DDA0DD/FFFFFF?text=6',
            '7' => 'https://via.placeholder.com/150/98D8C8/FFFFFF?text=7',
            '8' => 'https://via.placeholder.com/150/F7DC6F/333333?text=8'
        ];

        return $predefinedImages[$imageId] ?? null;
    }

    /**
     * Authenticate user
     */
    public function authenticate($email, $password)
    {
        $query = "SELECT id, nickname, email, password_hash, profile_image, role, total_points 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();

            if (password_verify($password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->nickname = $row['nickname'];
                $this->email = $row['email'];
                $this->profile_image = $row['profile_image'];
                $this->role = $row['role'];
                $this->total_points = $row['total_points'];

                return ['success' => true, 'user' => $row];
            }
        }

        return ['success' => false, 'error' => 'Credenciales inválidas'];
    }

    /**
     * Get user by ID
     */
    public function getById($id)
    {
        $query = "SELECT id, nickname, email, profile_image, role, total_points, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }

        return false;
    }
}
?>