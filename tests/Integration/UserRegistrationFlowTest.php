<?php
/**
 * Integration tests for complete user registration and participation flow
 */

require_once __DIR__ . '/../bootstrap.php';

class UserRegistrationFlowTest extends BaseTestCase {
    private $user;
    private $pointTransaction;
    private $claim;
    
    public function setUp() {
        parent::setUp();
        $this->user = new User($this->db);
        $this->pointTransaction = new PointTransaction($this->db);
        $this->claim = new Claim($this->db);
    }
    
    /**
     * Test complete user registration and participation flow
     * Requirements: 1.1, 1.2, 1.3, 1.4, 4.1, 4.2, 4.4, 6.2, 6.3, 6.5, 6.7
     */
    public function testCompleteUserRegistrationAndParticipationFlow() {
        // Step 1: User Registration
        $registrationResult = $this->user->create(
            'newparticipant',
            'participant@example.com',
            'securepassword123'
        );
        
        $this->assertTrue($registrationResult['success'], 'User registration should succeed');
        $userId = $registrationResult['user_id'];
        
        // Verify user was created with correct initial state
        $userData = $this->user->getById($userId);
        $this->assertEquals('newparticipant', $userData['nickname']);
        $this->assertEquals('participant@example.com', $userData['email']);
        $this->assertEquals('user', $userData['role']);
        $this->assertEquals(0, $userData['total_points']);
        
        // Step 2: User Authentication
        $authResult = $this->user->authenticate('participant@example.com', 'securepassword123');
        $this->assertTrue($authResult['success'], 'User authentication should succeed');
        $this->assertEquals($userId, $authResult['user']['id']);
        
        // Step 3: Assistant assigns points for tournament participation
        $pointsResult = $this->pointTransaction->create(
            $userId,
            50, // points
            'earned',
            'tournament',
            3, // assigned by assistant
            1 // tournament_id
        );
        
        $this->assertTrue($pointsResult['success'], 'Points assignment should succeed');
        
        // Verify user's points were updated
        $updatedUserData = $this->user->getById($userId);
        $this->assertEquals(50, $updatedUserData['total_points']);
        
        // Step 4: User participates in another activity and gets more points
        $bonusPointsResult = $this->pointTransaction->create(
            $userId,
            25,
            'earned',
            'challenge',
            3 // assigned by assistant
        );
        
        $this->assertTrue($bonusPointsResult['success'], 'Bonus points assignment should succeed');
        
        // Verify total points
        $finalUserData = $this->user->getById($userId);
        $this->assertEquals(75, $finalUserData['total_points']);
        
        // Step 5: User attempts to claim a product
        $claimResult = $this->claim->create($userId, 1, 1); // product costs 75 points
        $this->assertTrue($claimResult['success'], 'Claim creation should succeed with sufficient points');
        
        // Step 6: Stand manager processes the claim
        $processResult = $this->claim->processClaim($claimResult['claim_id'], 5);
        $this->assertTrue($processResult['success'], 'Claim processing should succeed');
        
        // Verify claim was processed
        $claimData = $this->claim->getById($claimResult['claim_id']);
        $this->assertEquals('completed', $claimData['status']);
        $this->assertEquals(5, $claimData['processed_by']);
        
        // Step 7: Verify user cannot claim the same product again (uniqueness)
        $duplicateClaimResult = $this->claim->create($userId, 1, 1);
        $this->assertFalse($duplicateClaimResult['success'], 'Duplicate claim should fail');
        $this->assertContains('El usuario ya ha reclamado este producto', $duplicateClaimResult['errors']);
        
        // Step 8: Verify transaction history
        $transactionHistory = $this->pointTransaction->getByUserId($userId);
        $this->assertEquals(2, count($transactionHistory['transactions']));
        $this->assertEquals(2, $transactionHistory['total']);
        
        // Verify transaction details
        $transactions = $transactionHistory['transactions'];
        $this->assertEquals(25, $transactions[0]['points']); // Most recent first
        $this->assertEquals('challenge', $transactions[0]['source']);
        $this->assertEquals(50, $transactions[1]['points']);
        $this->assertEquals('tournament', $transactions[1]['source']);
    }
    
    /**
     * Test user registration with validation failures
     * Requirements: 1.1, 1.2, 1.3, 1.4
     */
    public function testUserRegistrationWithValidationFailures() {
        // Test registration with invalid data
        $invalidRegistrationResult = $this->user->create(
            'ab', // too short
            'invalid-email',
            '123' // too short
        );
        
        $this->assertFalse($invalidRegistrationResult['success'], 'Invalid registration should fail');
        $this->assertGreaterThan(2, count($invalidRegistrationResult['errors']));
        
        // Test registration with duplicate nickname
        $duplicateNicknameResult = $this->user->create(
            'testuser1', // already exists
            'newemail@example.com',
            'validpassword123'
        );
        
        $this->assertFalse($duplicateNicknameResult['success'], 'Duplicate nickname registration should fail');
        $this->assertContains('El nickname ya está en uso', $duplicateNicknameResult['errors']);
        
        // Test registration with duplicate email
        $duplicateEmailResult = $this->user->create(
            'newnickname',
            'test1@example.com', // already exists
            'validpassword123'
        );
        
        $this->assertFalse($duplicateEmailResult['success'], 'Duplicate email registration should fail');
        $this->assertContains('El email ya está registrado', $duplicateEmailResult['errors']);
    }
    
    /**
     * Test user participation without active event
     * Requirements: 2.3, 4.3
     */
    public function testUserParticipationWithoutActiveEvent() {
        // Create a new user
        $registrationResult = $this->user->create(
            'eventlessuser',
            'eventless@example.com',
            'password123'
        );
        $userId = $registrationResult['user_id'];
        
        // Deactivate all events
        $stmt = $this->db->prepare("UPDATE events SET is_active = 0");
        $stmt->execute();
        
        // Try to assign points without active event
        $pointsResult = $this->pointTransaction->create(
            $userId,
            50,
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertFalse($pointsResult['success'], 'Points assignment should fail without active event');
        $this->assertContains('El evento del torneo no está activo', $pointsResult['errors']);
        
        // Verify user's points remain unchanged
        $userData = $this->user->getById($userId);
        $this->assertEquals(0, $userData['total_points']);
    }
    
    /**
     * Test user authentication flow with various scenarios
     * Requirements: 1.1, 1.2, 1.3, 1.4
     */
    public function testUserAuthenticationFlow() {
        // Create a test user
        $registrationResult = $this->user->create(
            'authuser',
            'auth@example.com',
            'mypassword123'
        );
        $this->assertTrue($registrationResult['success']);
        
        // Test successful authentication
        $successAuth = $this->user->authenticate('auth@example.com', 'mypassword123');
        $this->assertTrue($successAuth['success'], 'Valid credentials should authenticate successfully');
        $this->assertEquals('authuser', $successAuth['user']['nickname']);
        
        // Test authentication with wrong password
        $wrongPasswordAuth = $this->user->authenticate('auth@example.com', 'wrongpassword');
        $this->assertFalse($wrongPasswordAuth['success'], 'Wrong password should fail authentication');
        $this->assertEquals('Credenciales inválidas', $wrongPasswordAuth['error']);
        
        // Test authentication with non-existing email
        $nonExistingAuth = $this->user->authenticate('nonexisting@example.com', 'mypassword123');
        $this->assertFalse($nonExistingAuth['success'], 'Non-existing email should fail authentication');
        $this->assertEquals('Credenciales inválidas', $nonExistingAuth['error']);
        
        // Test authentication with empty credentials
        $emptyAuth = $this->user->authenticate('', '');
        $this->assertFalse($emptyAuth['success'], 'Empty credentials should fail authentication');
    }
    
    /**
     * Test complete flow with insufficient points scenario
     * Requirements: 6.2, 6.6
     */
    public function testFlowWithInsufficientPoints() {
        // Create a new user
        $registrationResult = $this->user->create(
            'pooruser',
            'poor@example.com',
            'password123'
        );
        $userId = $registrationResult['user_id'];
        
        // Assign some points but not enough for expensive product
        $pointsResult = $this->pointTransaction->create(
            $userId,
            30, // only 30 points
            'earned',
            'challenge',
            3
        );
        $this->assertTrue($pointsResult['success']);
        
        // Try to claim expensive product (costs 75 points)
        $claimResult = $this->claim->create($userId, 1, 1);
        $this->assertFalse($claimResult['success'], 'Claim should fail with insufficient points');
        $this->assertContains('Puntos insuficientes para reclamar este producto', $claimResult['errors']);
        
        // Verify points info in response
        $this->assertArrayHasKey('points_info', $claimResult);
        $this->assertFalse($claimResult['points_info']['has_sufficient_points']);
        $this->assertEquals(30, $claimResult['points_info']['user_points']);
        $this->assertEquals(75, $claimResult['points_info']['required_points']);
        
        // User gets more points
        $morePointsResult = $this->pointTransaction->create(
            $userId,
            50, // now has 80 total
            'earned',
            'tournament',
            3,
            1
        );
        $this->assertTrue($morePointsResult['success']);
        
        // Now claim should succeed
        $successfulClaimResult = $this->claim->create($userId, 1, 1);
        $this->assertTrue($successfulClaimResult['success'], 'Claim should succeed with sufficient points');
    }
    
    /**
     * Test role-based permissions in the flow
     * Requirements: 4.1, 4.2, 6.2, 7.1
     */
    public function testRoleBasedPermissionsFlow() {
        // Create a regular user
        $userResult = $this->user->create('regularuser', 'regular@example.com', 'password123');
        $regularUserId = $userResult['user_id'];
        
        // Regular user tries to assign points (should fail)
        $invalidPointsResult = $this->pointTransaction->create(
            2, // target user
            50,
            'earned',
            'tournament',
            $regularUserId, // regular user trying to assign
            1
        );
        $this->assertFalse($invalidPointsResult['success'], 'Regular user should not be able to assign points');
        $this->assertContains('El usuario no tiene permisos para asignar puntos', $invalidPointsResult['errors']);
        
        // Assistant assigns points (should succeed)
        $validPointsResult = $this->pointTransaction->create(
            $regularUserId,
            50,
            'earned',
            'tournament',
            3, // assistant
            1
        );
        $this->assertTrue($validPointsResult['success'], 'Assistant should be able to assign points');
        
        // Admin assigns points (should succeed)
        $adminPointsResult = $this->pointTransaction->create(
            $regularUserId,
            25,
            'earned',
            'bonus',
            4 // admin
        );
        $this->assertTrue($adminPointsResult['success'], 'Admin should be able to assign points');
        
        // Stand manager assigns points (should succeed)
        $managerPointsResult = $this->pointTransaction->create(
            $regularUserId,
            15,
            'earned',
            'challenge',
            5 // stand manager
        );
        $this->assertTrue($managerPointsResult['success'], 'Stand manager should be able to assign points');
        
        // Verify total points
        $userData = $this->user->getById($regularUserId);
        $this->assertEquals(90, $userData['total_points']); // 50 + 25 + 15
    }
}