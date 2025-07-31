<?php
/**
 * Unit tests for PointTransaction model
 */

require_once __DIR__ . '/../bootstrap.php';

class PointTransactionTest extends BaseTestCase {
    private $pointTransaction;
    
    public function setUp() {
        parent::setUp();
        $this->pointTransaction = new PointTransaction($this->db);
    }
    
    /**
     * Test transaction validation - valid input
     */
    public function testValidateTransactionWithValidInput() {
        $errors = $this->pointTransaction->validateTransaction(
            1, // user_id
            50, // points
            'earned', // type
            'tournament', // source
            3, // assigned_by (assistant)
            1 // tournament_id
        );
        
        $this->assertEmpty($errors, 'Valid transaction input should not produce validation errors');
    }
    
    /**
     * Test transaction validation - invalid user ID
     */
    public function testValidateTransactionWithInvalidUserId() {
        $errors = $this->pointTransaction->validateTransaction(
            'invalid', // non-numeric user_id
            50,
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertContains('ID de usuario inválido', $errors);
    }
    
    /**
     * Test transaction validation - non-existing user
     */
    public function testValidateTransactionWithNonExistingUser() {
        $errors = $this->pointTransaction->validateTransaction(
            999, // non-existing user_id
            50,
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertContains('El usuario no existe', $errors);
    }
    
    /**
     * Test transaction validation - invalid points (non-numeric)
     */
    public function testValidateTransactionWithNonNumericPoints() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            'invalid', // non-numeric points
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertContains('Los puntos deben ser un número', $errors);
    }
    
    /**
     * Test transaction validation - zero points
     */
    public function testValidateTransactionWithZeroPoints() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            0, // zero points
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertContains('Los puntos deben ser mayor a 0', $errors);
    }
    
    /**
     * Test transaction validation - negative points
     */
    public function testValidateTransactionWithNegativePoints() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            -10, // negative points
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertContains('Los puntos deben ser mayor a 0', $errors);
    }
    
    /**
     * Test transaction validation - excessive points
     */
    public function testValidateTransactionWithExcessivePoints() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            15000, // over 10,000 limit
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertContains('Los puntos no pueden ser mayor a 10,000', $errors);
    }
    
    /**
     * Test transaction validation - invalid type
     */
    public function testValidateTransactionWithInvalidType() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            50,
            'invalid_type', // invalid type
            'tournament',
            3,
            1
        );
        
        $this->assertContains('Tipo de transacción inválido', $errors);
    }
    
    /**
     * Test transaction validation - invalid source
     */
    public function testValidateTransactionWithInvalidSource() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            50,
            'earned',
            'invalid_source', // invalid source
            3,
            1
        );
        
        $this->assertContains('Fuente de puntos inválida', $errors);
    }
    
    /**
     * Test transaction validation - invalid assigned_by
     */
    public function testValidateTransactionWithInvalidAssignedBy() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            50,
            'earned',
            'tournament',
            'invalid', // non-numeric assigned_by
            1
        );
        
        $this->assertContains('ID del asignador inválido', $errors);
    }
    
    /**
     * Test transaction validation - user without permission to assign points
     */
    public function testValidateTransactionWithUnauthorizedAssigner() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            50,
            'earned',
            'tournament',
            1, // regular user (testuser1) cannot assign points
            1
        );
        
        $this->assertContains('El usuario no tiene permisos para asignar puntos', $errors);
    }
    
    /**
     * Test transaction validation - non-existing tournament
     */
    public function testValidateTransactionWithNonExistingTournament() {
        $errors = $this->pointTransaction->validateTransaction(
            1,
            50,
            'earned',
            'tournament',
            3,
            999 // non-existing tournament
        );
        
        $this->assertContains('El torneo no existe', $errors);
    }
    
    /**
     * Test active event validation - with active event
     */
    public function testValidateActiveEventWithActiveEvent() {
        $result = $this->pointTransaction->validateActiveEvent();
        
        $this->assertTrue($result['valid'], 'Should validate successfully when there is an active event');
    }
    
    /**
     * Test active event validation - with inactive event
     */
    public function testValidateActiveEventWithInactiveEvent() {
        // Deactivate the test event
        $stmt = $this->db->prepare("UPDATE events SET is_active = 0 WHERE id = 1");
        $stmt->execute();
        
        $result = $this->pointTransaction->validateActiveEvent();
        
        $this->assertFalse($result['valid'], 'Should fail validation when no active events exist');
        $this->assertEquals('No hay eventos activos para asignar puntos', $result['error']);
    }
    
    /**
     * Test active event validation - with tournament from active event
     */
    public function testValidateActiveEventWithTournamentFromActiveEvent() {
        $result = $this->pointTransaction->validateActiveEvent(1);
        
        $this->assertTrue($result['valid'], 'Should validate successfully for tournament from active event');
    }
    
    /**
     * Test active event validation - with tournament from inactive event
     */
    public function testValidateActiveEventWithTournamentFromInactiveEvent() {
        // Deactivate the test event
        $stmt = $this->db->prepare("UPDATE events SET is_active = 0 WHERE id = 1");
        $stmt->execute();
        
        $result = $this->pointTransaction->validateActiveEvent(1);
        
        $this->assertFalse($result['valid'], 'Should fail validation for tournament from inactive event');
        $this->assertEquals('El evento del torneo no está activo', $result['error']);
    }
    
    /**
     * Test successful transaction creation
     */
    public function testCreateTransactionSuccessfully() {
        $result = $this->pointTransaction->create(
            1, // user_id
            50, // points
            'earned', // type
            'tournament', // source
            3, // assigned_by (assistant)
            1, // tournament_id
            ['notes' => 'Test tournament completion']
        );
        
        $this->assertTrue($result['success'], 'Transaction creation should succeed');
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertIsInt($result['transaction_id']);
        
        // Verify transaction was created
        $transaction = $this->pointTransaction->getById($result['transaction_id']);
        $this->assertNotFalse($transaction);
        $this->assertEquals(1, $transaction['user_id']);
        $this->assertEquals(50, $transaction['points']);
        $this->assertEquals('earned', $transaction['type']);
        $this->assertEquals('tournament', $transaction['source']);
        $this->assertEquals(3, $transaction['assigned_by']);
        $this->assertEquals(1, $transaction['tournament_id']);
        $this->assertEquals(['notes' => 'Test tournament completion'], $transaction['metadata']);
        
        // Verify user's total points were updated
        $stmt = $this->db->prepare("SELECT total_points FROM users WHERE id = 1");
        $stmt->execute();
        $userPoints = $stmt->fetch()['total_points'];
        $this->assertEquals(150, $userPoints); // 100 initial + 50 earned
    }
    
    /**
     * Test transaction creation with validation errors
     */
    public function testCreateTransactionWithValidationErrors() {
        $result = $this->pointTransaction->create(
            999, // non-existing user
            -10, // negative points
            'invalid_type',
            'invalid_source',
            1 // user without permission
        );
        
        $this->assertFalse($result['success'], 'Transaction creation should fail with validation errors');
        $this->assertNotEmpty($result['errors']);
        $this->assertGreaterThan(3, count($result['errors'])); // Should have multiple validation errors
    }
    
    /**
     * Test transaction creation without active event
     */
    public function testCreateTransactionWithoutActiveEvent() {
        // Deactivate the test event
        $stmt = $this->db->prepare("UPDATE events SET is_active = 0 WHERE id = 1");
        $stmt->execute();
        
        $result = $this->pointTransaction->create(
            1,
            50,
            'earned',
            'tournament',
            3,
            1
        );
        
        $this->assertFalse($result['success'], 'Transaction creation should fail without active event');
        $this->assertContains('El evento del torneo no está activo', $result['errors']);
    }
    
    /**
     * Test claimed type transaction (should not update user points)
     */
    public function testCreateClaimedTransaction() {
        $initialPoints = 100; // testuser1's initial points
        
        $result = $this->pointTransaction->create(
            1,
            25,
            'claimed', // claimed type should not add to user's total
            'bonus',
            3
        );
        
        $this->assertTrue($result['success'], 'Claimed transaction creation should succeed');
        
        // Verify user's total points were NOT updated for claimed type
        $stmt = $this->db->prepare("SELECT total_points FROM users WHERE id = 1");
        $stmt->execute();
        $userPoints = $stmt->fetch()['total_points'];
        $this->assertEquals($initialPoints, $userPoints); // Should remain unchanged
    }
    
    /**
     * Test get transaction by ID
     */
    public function testGetTransactionById() {
        // Create a test transaction first
        $result = $this->pointTransaction->create(1, 30, 'earned', 'challenge', 3);
        $transactionId = $result['transaction_id'];
        
        $transaction = $this->pointTransaction->getById($transactionId);
        
        $this->assertNotFalse($transaction, 'Should return transaction data');
        $this->assertEquals(1, $transaction['user_id']);
        $this->assertEquals(30, $transaction['points']);
        $this->assertEquals('earned', $transaction['type']);
        $this->assertEquals('challenge', $transaction['source']);
        $this->assertEquals('testuser1', $transaction['user_nickname']);
        $this->assertEquals('assistant1', $transaction['assigned_by_nickname']);
    }
    
    /**
     * Test get transactions by user ID
     */
    public function testGetTransactionsByUserId() {
        // Create multiple test transactions
        $this->pointTransaction->create(1, 30, 'earned', 'challenge', 3);
        $this->pointTransaction->create(1, 20, 'earned', 'bonus', 4);
        $this->pointTransaction->create(2, 15, 'earned', 'tournament', 3, 1);
        
        $result = $this->pointTransaction->getByUserId(1, 1, 10);
        
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(2, count($result['transactions'])); // Should return 2 transactions for user 1
        $this->assertEquals(2, $result['total']);
        
        // Verify transactions are ordered by timestamp DESC
        $this->assertGreaterThanOrEqual(
            $result['transactions'][1]['timestamp'],
            $result['transactions'][0]['timestamp']
        );
    }
    
    /**
     * Test get user's total points from transactions
     */
    public function testGetUserTotalPoints() {
        // Create test transactions
        $this->pointTransaction->create(1, 30, 'earned', 'challenge', 3);
        $this->pointTransaction->create(1, 20, 'earned', 'bonus', 4);
        $this->pointTransaction->create(1, 10, 'claimed', 'bonus', 3); // Should not count
        
        $totalPoints = $this->pointTransaction->getUserTotalPoints(1);
        
        $this->assertEquals(50, $totalPoints); // 30 + 20, claimed transaction should not count
    }
    
    /**
     * Test get user's points statistics
     */
    public function testGetUserPointsStats() {
        // Create test transactions with different sources
        $this->pointTransaction->create(1, 30, 'earned', 'tournament', 3, 1);
        $this->pointTransaction->create(1, 20, 'earned', 'challenge', 3);
        $this->pointTransaction->create(1, 10, 'earned', 'bonus', 4);
        
        $stats = $this->pointTransaction->getUserPointsStats(1);
        
        $this->assertEquals(60, $stats['total_earned']);
        $this->assertEquals(30, $stats['tournament_points']);
        $this->assertEquals(20, $stats['challenge_points']);
        $this->assertEquals(10, $stats['bonus_points']);
        $this->assertEquals(3, $stats['total_transactions']);
    }
    
    /**
     * Test get recent transactions with filters
     */
    public function testGetRecentTransactionsWithFilters() {
        // Create test transactions
        $this->pointTransaction->create(1, 30, 'earned', 'tournament', 3, 1);
        $this->pointTransaction->create(2, 20, 'earned', 'challenge', 3);
        $this->pointTransaction->create(1, 10, 'claimed', 'bonus', 4);
        
        // Test filter by type
        $result = $this->pointTransaction->getRecent(1, 10, 'earned');
        $this->assertEquals(2, count($result['transactions']));
        
        // Test filter by source
        $result = $this->pointTransaction->getRecent(1, 10, null, 'tournament');
        $this->assertEquals(1, count($result['transactions']));
        $this->assertEquals('tournament', $result['transactions'][0]['source']);
    }
}