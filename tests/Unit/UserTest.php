<?php
/**
 * Unit tests for User model
 */

require_once __DIR__ . '/../bootstrap.php';

class UserTest extends BaseTestCase {
    private $user;
    
    public function setUp() {
        parent::setUp();
        $this->user = new User($this->db);
    }
    
    /**
     * Test user registration validation - valid input
     */
    public function testValidateRegistrationWithValidInput() {
        $errors = $this->user->validateRegistration(
            'validuser',
            'valid@example.com',
            'password123'
        );
        
        $this->assertEmpty($errors, 'Valid input should not produce validation errors');
    }
    
    /**
     * Test user registration validation - empty nickname
     */
    public function testValidateRegistrationWithEmptyNickname() {
        $errors = $this->user->validateRegistration(
            '',
            'valid@example.com',
            'password123'
        );
        
        $this->assertContains('El nickname es requerido', $errors);
    }
    
    /**
     * Test user registration validation - short nickname
     */
    public function testValidateRegistrationWithShortNickname() {
        $errors = $this->user->validateRegistration(
            'ab',
            'valid@example.com',
            'password123'
        );
        
        $this->assertContains('El nickname debe tener entre 3 y 50 caracteres', $errors);
    }
    
    /**
     * Test user registration validation - long nickname
     */
    public function testValidateRegistrationWithLongNickname() {
        $longNickname = str_repeat('a', 51);
        $errors = $this->user->validateRegistration(
            $longNickname,
            'valid@example.com',
            'password123'
        );
        
        $this->assertContains('El nickname debe tener entre 3 y 50 caracteres', $errors);
    }
    
    /**
     * Test user registration validation - invalid nickname characters
     */
    public function testValidateRegistrationWithInvalidNicknameCharacters() {
        $errors = $this->user->validateRegistration(
            'invalid-user!',
            'valid@example.com',
            'password123'
        );
        
        $this->assertContains('El nickname solo puede contener letras, números y guiones bajos', $errors);
    }
    
    /**
     * Test user registration validation - empty email
     */
    public function testValidateRegistrationWithEmptyEmail() {
        $errors = $this->user->validateRegistration(
            'validuser',
            '',
            'password123'
        );
        
        $this->assertContains('El email es requerido', $errors);
    }
    
    /**
     * Test user registration validation - invalid email format
     */
    public function testValidateRegistrationWithInvalidEmail() {
        $errors = $this->user->validateRegistration(
            'validuser',
            'invalid-email',
            'password123'
        );
        
        $this->assertContains('El formato del email no es válido', $errors);
    }
    
    /**
     * Test user registration validation - empty password
     */
    public function testValidateRegistrationWithEmptyPassword() {
        $errors = $this->user->validateRegistration(
            'validuser',
            'valid@example.com',
            ''
        );
        
        $this->assertContains('La contraseña es requerida', $errors);
    }
    
    /**
     * Test user registration validation - short password
     */
    public function testValidateRegistrationWithShortPassword() {
        $errors = $this->user->validateRegistration(
            'validuser',
            'valid@example.com',
            '12345'
        );
        
        $this->assertContains('La contraseña debe tener al menos 6 caracteres', $errors);
    }
    
    /**
     * Test nickname existence check - existing nickname
     */
    public function testNicknameExistsWithExistingNickname() {
        $exists = $this->user->nicknameExists('testuser1');
        $this->assertTrue($exists, 'Should return true for existing nickname');
    }
    
    /**
     * Test nickname existence check - non-existing nickname
     */
    public function testNicknameExistsWithNonExistingNickname() {
        $exists = $this->user->nicknameExists('nonexistentuser');
        $this->assertFalse($exists, 'Should return false for non-existing nickname');
    }
    
    /**
     * Test email existence check - existing email
     */
    public function testEmailExistsWithExistingEmail() {
        $exists = $this->user->emailExists('test1@example.com');
        $this->assertTrue($exists, 'Should return true for existing email');
    }
    
    /**
     * Test email existence check - non-existing email
     */
    public function testEmailExistsWithNonExistingEmail() {
        $exists = $this->user->emailExists('nonexistent@example.com');
        $this->assertFalse($exists, 'Should return false for non-existing email');
    }
    
    /**
     * Test user creation - successful creation
     */
    public function testCreateUserSuccessfully() {
        $result = $this->user->create(
            'newuser',
            'newuser@example.com',
            'password123'
        );
        
        $this->assertTrue($result['success'], 'User creation should succeed');
        $this->assertArrayHasKey('user_id', $result);
        $this->assertIsInt($result['user_id']);
        
        // Verify user was actually created
        $userData = $this->user->getById($result['user_id']);
        $this->assertNotFalse($userData);
        $this->assertEquals('newuser', $userData['nickname']);
        $this->assertEquals('newuser@example.com', $userData['email']);
        $this->assertEquals('user', $userData['role']);
        $this->assertEquals(0, $userData['total_points']);
    }
    
    /**
     * Test user creation - duplicate nickname
     */
    public function testCreateUserWithDuplicateNickname() {
        $result = $this->user->create(
            'testuser1', // This nickname already exists in test data
            'newemail@example.com',
            'password123'
        );
        
        $this->assertFalse($result['success'], 'User creation should fail with duplicate nickname');
        $this->assertContains('El nickname ya está en uso', $result['errors']);
    }
    
    /**
     * Test user creation - duplicate email
     */
    public function testCreateUserWithDuplicateEmail() {
        $result = $this->user->create(
            'newnickname',
            'test1@example.com', // This email already exists in test data
            'password123'
        );
        
        $this->assertFalse($result['success'], 'User creation should fail with duplicate email');
        $this->assertContains('El email ya está registrado', $result['errors']);
    }
    
    /**
     * Test user creation - invalid input
     */
    public function testCreateUserWithInvalidInput() {
        $result = $this->user->create(
            'ab', // Too short
            'invalid-email',
            '123' // Too short
        );
        
        $this->assertFalse($result['success'], 'User creation should fail with invalid input');
        $this->assertNotEmpty($result['errors']);
        $this->assertGreaterThan(2, count($result['errors'])); // Should have multiple validation errors
    }
    
    /**
     * Test user authentication - successful login
     */
    public function testAuthenticateUserSuccessfully() {
        $result = $this->user->authenticate('test1@example.com', 'password123');
        
        $this->assertTrue($result['success'], 'Authentication should succeed with correct credentials');
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('testuser1', $result['user']['nickname']);
        $this->assertEquals('test1@example.com', $result['user']['email']);
    }
    
    /**
     * Test user authentication - wrong password
     */
    public function testAuthenticateUserWithWrongPassword() {
        $result = $this->user->authenticate('test1@example.com', 'wrongpassword');
        
        $this->assertFalse($result['success'], 'Authentication should fail with wrong password');
        $this->assertEquals('Credenciales inválidas', $result['error']);
    }
    
    /**
     * Test user authentication - non-existing email
     */
    public function testAuthenticateUserWithNonExistingEmail() {
        $result = $this->user->authenticate('nonexistent@example.com', 'password123');
        
        $this->assertFalse($result['success'], 'Authentication should fail with non-existing email');
        $this->assertEquals('Credenciales inválidas', $result['error']);
    }
    
    /**
     * Test get user by ID - existing user
     */
    public function testGetUserByIdWithExistingUser() {
        $userData = $this->user->getById(1);
        
        $this->assertNotFalse($userData, 'Should return user data for existing user');
        $this->assertEquals('testuser1', $userData['nickname']);
        $this->assertEquals('test1@example.com', $userData['email']);
        $this->assertEquals('user', $userData['role']);
        $this->assertEquals(100, $userData['total_points']);
    }
    
    /**
     * Test get user by ID - non-existing user
     */
    public function testGetUserByIdWithNonExistingUser() {
        $userData = $this->user->getById(999);
        
        $this->assertFalse($userData, 'Should return false for non-existing user');
    }
    
    /**
     * Test profile image validation - valid image
     */
    public function testValidateProfileImageWithValidImage() {
        // Mock a valid image file
        $mockImage = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/jpeg',
            'size' => 1024 * 1024, // 1MB
            'tmp_name' => __DIR__ . '/../fixtures/test_image.jpg'
        ];
        
        // Create a temporary test image file
        if (!file_exists(dirname($mockImage['tmp_name']))) {
            mkdir(dirname($mockImage['tmp_name']), 0777, true);
        }
        
        // Create a simple 1x1 pixel JPEG for testing
        $image = imagecreate(1, 1);
        imagejpeg($image, $mockImage['tmp_name']);
        imagedestroy($image);
        
        $errors = $this->user->validateProfileImage($mockImage);
        
        $this->assertEmpty($errors, 'Valid image should not produce validation errors');
        
        // Clean up
        unlink($mockImage['tmp_name']);
    }
    
    /**
     * Test profile image validation - invalid file type
     */
    public function testValidateProfileImageWithInvalidType() {
        $mockImage = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
            'size' => 1024,
            'tmp_name' => '/tmp/test.txt'
        ];
        
        $errors = $this->user->validateProfileImage($mockImage);
        
        $this->assertContains('Solo se permiten imágenes JPG, PNG o GIF', $errors);
    }
    
    /**
     * Test profile image validation - file too large
     */
    public function testValidateProfileImageWithLargeFile() {
        $mockImage = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/jpeg',
            'size' => 6 * 1024 * 1024, // 6MB (over 5MB limit)
            'tmp_name' => '/tmp/test.jpg'
        ];
        
        $errors = $this->user->validateProfileImage($mockImage);
        
        $this->assertContains('La imagen no puede ser mayor a 5MB', $errors);
    }
    
    /**
     * Test profile image validation - upload error
     */
    public function testValidateProfileImageWithUploadError() {
        $mockImage = [
            'error' => UPLOAD_ERR_PARTIAL,
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/test.jpg'
        ];
        
        $errors = $this->user->validateProfileImage($mockImage);
        
        $this->assertContains('Error al subir la imagen', $errors);
    }
}