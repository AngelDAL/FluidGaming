<?php
/**
 * Unit tests for Claim model
 */

require_once __DIR__ . '/../bootstrap.php';

class ClaimTest extends BaseTestCase {
    private $claim;
    
    public function setUp() {
        parent::setUp();
        $this->claim = new Claim($this->db);
    }
    
    /**
     * Test claim validation - valid input
     */
    public function testValidateClaimWithValidInput() {
        $errors = $this->claim->validateClaim(1, 1, 1); // user_id, product_id, stand_id
        
        $this->assertEmpty($errors, 'Valid claim input should not produce validation errors');
    }
    
    /**
     * Test claim validation - invalid user ID
     */
    public function testValidateClaimWithInvalidUserId() {
        $errors = $this->claim->validateClaim('invalid', 1, 1);
        
        $this->assertContains('ID del usuario es requerido y debe ser numérico', $errors);
    }
    
    /**
     * Test claim validation - non-existing user
     */
    public function testValidateClaimWithNonExistingUser() {
        $errors = $this->claim->validateClaim(999, 1, 1);
        
        $this->assertContains('El usuario especificado no existe', $errors);
    }
    
    /**
     * Test claim validation - invalid product ID
     */
    public function testValidateClaimWithInvalidProductId() {
        $errors = $this->claim->validateClaim(1, 'invalid', 1);
        
        $this->assertContains('ID del producto es requerido y debe ser numérico', $errors);
    }
    
    /**
     * Test claim validation - non-existing product
     */
    public function testValidateClaimWithNonExistingProduct() {
        $errors = $this->claim->validateClaim(1, 999, 1);
        
        $this->assertContains('El producto especificado no existe', $errors);
    }
    
    /**
     * Test claim validation - inactive product
     */
    public function testValidateClaimWithInactiveProduct() {
        // Deactivate the test product
        $stmt = $this->db->prepare("UPDATE products SET is_active = 0 WHERE id = 1");
        $stmt->execute();
        
        $errors = $this->claim->validateClaim(1, 1, 1);
        
        $this->assertContains('El producto no está disponible', $errors);
    }
    
    /**
     * Test claim validation - product doesn't belong to stand
     */
    public function testValidateClaimWithProductNotBelongingToStand() {
        // Create another stand
        $stmt = $this->db->prepare("INSERT INTO stands (name, manager_id, event_id) VALUES ('Other Stand', 5, 1)");
        $stmt->execute();
        $otherStandId = $this->db->lastInsertId();
        
        $errors = $this->claim->validateClaim(1, 1, $otherStandId);
        
        $this->assertContains('El producto no pertenece al stand especificado', $errors);
    }
    
    /**
     * Test claim validation - invalid stand ID
     */
    public function testValidateClaimWithInvalidStandId() {
        $errors = $this->claim->validateClaim(1, 1, 'invalid');
        
        $this->assertContains('ID del stand es requerido y debe ser numérico', $errors);
    }
    
    /**
     * Test claim validation - non-existing stand
     */
    public function testValidateClaimWithNonExistingStand() {
        $errors = $this->claim->validateClaim(1, 1, 999);
        
        $this->assertContains('El stand especificado no existe', $errors);
    }
    
    /**
     * Test user claimed product check - user has not claimed product
     */
    public function testHasUserClaimedProductWithNoClaim() {
        $hasClaimed = $this->claim->hasUserClaimedProduct(1, 1);
        
        $this->assertFalse($hasClaimed, 'Should return false when user has not claimed the product');
    }
    
    /**
     * Test user claimed product check - user has claimed product
     */
    public function testHasUserClaimedProductWithExistingClaim() {
        // Create a claim first
        $this->claim->create(1, 1, 1, 5);
        
        $hasClaimed = $this->claim->hasUserClaimedProduct(1, 1);
        
        $this->assertTrue($hasClaimed, 'Should return true when user has already claimed the product');
    }
    
    /**
     * Test verify user points - sufficient points
     */
    public function testVerifyUserPointsWithSufficientPoints() {
        $result = $this->claim->verifyUserPoints(1, 1); // testuser1 has 100 points, product costs 75
        
        $this->assertNotFalse($result, 'Should return verification data');
        $this->assertTrue($result['has_sufficient_points'], 'User should have sufficient points');
        $this->assertEquals(100, $result['user_points']);
        $this->assertEquals(75, $result['required_points']);
        $this->assertEquals('Test Product', $result['product_name']);
    }
    
    /**
     * Test verify user points - insufficient points
     */
    public function testVerifyUserPointsWithInsufficientPoints() {
        $result = $this->claim->verifyUserPoints(2, 1); // testuser2 has 50 points, product costs 75
        
        $this->assertNotFalse($result, 'Should return verification data');
        $this->assertFalse($result['has_sufficient_points'], 'User should not have sufficient points');
        $this->assertEquals(50, $result['user_points']);
        $this->assertEquals(75, $result['required_points']);
    }
    
    /**
     * Test verify user points - non-existing user or product
     */
    public function testVerifyUserPointsWithNonExistingData() {
        $result = $this->claim->verifyUserPoints(999, 1);
        
        $this->assertFalse($result, 'Should return false for non-existing user');
        
        $result = $this->claim->verifyUserPoints(1, 999);
        
        $this->assertFalse($result, 'Should return false for non-existing product');
    }
    
    /**
     * Test successful claim creation
     */
    public function testCreateClaimSuccessfully() {
        $result = $this->claim->create(1, 1, 1, 5); // user, product, stand, processed_by
        
        $this->assertTrue($result['success'], 'Claim creation should succeed');
        $this->assertArrayHasKey('claim_id', $result);
        $this->assertArrayHasKey('points_info', $result);
        $this->assertIsInt($result['claim_id']);
        
        // Verify claim was created
        $claimData = $this->claim->getById($result['claim_id']);
        $this->assertNotFalse($claimData);
        $this->assertEquals(1, $claimData['user_id']);
        $this->assertEquals(1, $claimData['product_id']);
        $this->assertEquals(1, $claimData['stand_id']);
        $this->assertEquals(5, $claimData['processed_by']);
        $this->assertEquals('completed', $claimData['status']);
    }
    
    /**
     * Test claim creation with validation errors
     */
    public function testCreateClaimWithValidationErrors() {
        $result = $this->claim->create(999, 999, 999); // non-existing IDs
        
        $this->assertFalse($result['success'], 'Claim creation should fail with validation errors');
        $this->assertNotEmpty($result['errors']);
        $this->assertGreaterThan(2, count($result['errors'])); // Should have multiple validation errors
    }
    
    /**
     * Test claim creation - duplicate claim (uniqueness validation)
     */
    public function testCreateClaimWithDuplicateClaim() {
        // Create first claim
        $result1 = $this->claim->create(1, 1, 1, 5);
        $this->assertTrue($result1['success'], 'First claim should succeed');
        
        // Try to create duplicate claim
        $result2 = $this->claim->create(1, 1, 1, 5);
        $this->assertFalse($result2['success'], 'Duplicate claim should fail');
        $this->assertContains('El usuario ya ha reclamado este producto', $result2['errors']);
    }
    
    /**
     * Test claim creation - insufficient points
     */
    public function testCreateClaimWithInsufficientPoints() {
        $result = $this->claim->create(2, 1, 1, 5); // testuser2 has 50 points, product costs 75
        
        $this->assertFalse($result['success'], 'Claim creation should fail with insufficient points');
        $this->assertContains('Puntos insuficientes para reclamar este producto', $result['errors']);
        $this->assertArrayHasKey('points_info', $result);
        $this->assertFalse($result['points_info']['has_sufficient_points']);
    }
    
    /**
     * Test claim creation without processed_by (pending status)
     */
    public function testCreateClaimWithoutProcessedBy() {
        $result = $this->claim->create(1, 1, 1); // no processed_by parameter
        
        $this->assertTrue($result['success'], 'Claim creation should succeed');
        
        $claimData = $this->claim->getById($result['claim_id']);
        $this->assertEquals('pending', $claimData['status']);
        $this->assertNull($claimData['processed_by']);
    }
    
    /**
     * Test process claim successfully
     */
    public function testProcessClaimSuccessfully() {
        // Create a pending claim first
        $createResult = $this->claim->create(1, 1, 1);
        $claimId = $createResult['claim_id'];
        
        $result = $this->claim->processClaim($claimId, 5);
        
        $this->assertTrue($result['success'], 'Claim processing should succeed');
        $this->assertArrayHasKey('points_info', $result);
        
        // Verify claim was processed
        $claimData = $this->claim->getById($claimId);
        $this->assertEquals('completed', $claimData['status']);
        $this->assertEquals(5, $claimData['processed_by']);
    }
    
    /**
     * Test process claim - non-existing claim
     */
    public function testProcessClaimWithNonExistingClaim() {
        $result = $this->claim->processClaim(999, 5);
        
        $this->assertFalse($result['success'], 'Processing non-existing claim should fail');
        $this->assertContains('El reclamo no existe', $result['errors']);
    }
    
    /**
     * Test process claim - already completed claim
     */
    public function testProcessClaimWithAlreadyCompletedClaim() {
        // Create a completed claim
        $createResult = $this->claim->create(1, 1, 1, 5);
        $claimId = $createResult['claim_id'];
        
        $result = $this->claim->processClaim($claimId, 5);
        
        $this->assertFalse($result['success'], 'Processing already completed claim should fail');
        $this->assertContains('El reclamo ya ha sido procesado', $result['errors']);
    }
    
    /**
     * Test process claim - user no longer has sufficient points
     */
    public function testProcessClaimWithInsufficientPointsAfterCreation() {
        // Create a pending claim
        $createResult = $this->claim->create(1, 1, 1);
        $claimId = $createResult['claim_id'];
        
        // Reduce user's points below required amount
        $stmt = $this->db->prepare("UPDATE users SET total_points = 50 WHERE id = 1");
        $stmt->execute();
        
        $result = $this->claim->processClaim($claimId, 5);
        
        $this->assertFalse($result['success'], 'Processing claim should fail when user no longer has sufficient points');
        $this->assertContains('El usuario ya no tiene puntos suficientes', $result['errors']);
    }
    
    /**
     * Test get claim by ID
     */
    public function testGetClaimById() {
        $createResult = $this->claim->create(1, 1, 1, 5);
        $claimId = $createResult['claim_id'];
        
        $claimData = $this->claim->getById($claimId);
        
        $this->assertNotFalse($claimData, 'Should return claim data');
        $this->assertEquals(1, $claimData['user_id']);
        $this->assertEquals(1, $claimData['product_id']);
        $this->assertEquals(1, $claimData['stand_id']);
        $this->assertEquals('testuser1', $claimData['user_nickname']);
        $this->assertEquals('Test Product', $claimData['product_name']);
        $this->assertEquals('Test Stand', $claimData['stand_name']);
        $this->assertEquals('manager1', $claimData['processed_by_name']);
    }
    
    /**
     * Test get claims by user ID
     */
    public function testGetClaimsByUserId() {
        // Create multiple claims for user
        $this->claim->create(1, 1, 1, 5);
        $this->claim->create(1, 2, 1); // pending claim
        $this->claim->create(2, 1, 1, 5); // different user
        
        $claims = $this->claim->getByUserId(1);
        
        $this->assertEquals(2, count($claims), 'Should return 2 claims for user 1');
        
        // Test with status filter
        $completedClaims = $this->claim->getByUserId(1, 'completed');
        $this->assertEquals(1, count($completedClaims), 'Should return 1 completed claim for user 1');
        
        $pendingClaims = $this->claim->getByUserId(1, 'pending');
        $this->assertEquals(1, count($pendingClaims), 'Should return 1 pending claim for user 1');
    }
    
    /**
     * Test get claims by stand ID
     */
    public function testGetClaimsByStandId() {
        // Create claims for different stands
        $this->claim->create(1, 1, 1, 5);
        $this->claim->create(2, 1, 1);
        
        // Create another stand and product
        $stmt = $this->db->prepare("INSERT INTO stands (name, manager_id, event_id) VALUES ('Other Stand', 5, 1)");
        $stmt->execute();
        $otherStandId = $this->db->lastInsertId();
        
        $stmt = $this->db->prepare("INSERT INTO products (name, points_required, stand_id, is_active) VALUES ('Other Product', 50, ?, 1)");
        $stmt->execute([$otherStandId]);
        $otherProductId = $this->db->lastInsertId();
        
        $this->claim->create(1, $otherProductId, $otherStandId, 5);
        
        $stand1Claims = $this->claim->getByStandId(1);
        $this->assertEquals(2, count($stand1Claims), 'Should return 2 claims for stand 1');
        
        $stand2Claims = $this->claim->getByStandId($otherStandId);
        $this->assertEquals(1, count($stand2Claims), 'Should return 1 claim for other stand');
    }
    
    /**
     * Test get pending claims for manager
     */
    public function testGetPendingClaimsForManager() {
        // Create claims with different statuses
        $this->claim->create(1, 1, 1, 5); // completed
        $this->claim->create(2, 1, 1); // pending
        $this->claim->create(1, 2, 1); // pending
        
        $pendingClaims = $this->claim->getPendingClaimsForManager(5); // manager1
        
        $this->assertEquals(2, count($pendingClaims), 'Should return 2 pending claims for manager');
        
        foreach ($pendingClaims as $claim) {
            $this->assertEquals('pending', $claim['status']);
        }
    }
    
    /**
     * Test get claim statistics
     */
    public function testGetClaimStats() {
        // Create claims with different statuses
        $this->claim->create(1, 1, 1, 5); // completed, 75 points
        $this->claim->create(2, 1, 1); // pending, 75 points
        $this->claim->create(1, 2, 1, 5); // completed, 200 points
        
        $stats = $this->claim->getClaimStats();
        
        $this->assertEquals(3, $stats['total_claims']);
        $this->assertEquals(2, $stats['completed_claims']);
        $this->assertEquals(1, $stats['pending_claims']);
        $this->assertEquals(275, $stats['total_points_claimed']); // 75 + 200 (only completed)
        
        // Test with stand filter
        $standStats = $this->claim->getClaimStats(1);
        $this->assertEquals(3, $standStats['total_claims']);
        
        // Test with user filter
        $userStats = $this->claim->getClaimStats(null, 1);
        $this->assertEquals(2, $userStats['total_claims']); // user 1 has 2 claims
    }
    
    /**
     * Test cancel claim successfully
     */
    public function testCancelClaimSuccessfully() {
        // Create a pending claim
        $createResult = $this->claim->create(1, 1, 1);
        $claimId = $createResult['claim_id'];
        
        $result = $this->claim->cancelClaim($claimId, 1); // user cancels their own claim
        
        $this->assertTrue($result['success'], 'Claim cancellation should succeed');
        
        // Verify claim was deleted
        $claimData = $this->claim->getById($claimId);
        $this->assertFalse($claimData, 'Claim should be deleted');
    }
    
    /**
     * Test cancel claim - non-existing claim
     */
    public function testCancelClaimWithNonExistingClaim() {
        $result = $this->claim->cancelClaim(999, 1);
        
        $this->assertFalse($result['success'], 'Cancelling non-existing claim should fail');
        $this->assertContains('El reclamo no existe', $result['errors']);
    }
    
    /**
     * Test cancel claim - unauthorized user
     */
    public function testCancelClaimWithUnauthorizedUser() {
        // Create a claim for user 1
        $createResult = $this->claim->create(1, 1, 1);
        $claimId = $createResult['claim_id'];
        
        // Try to cancel with user 2 (not admin)
        $result = $this->claim->cancelClaim($claimId, 2);
        
        $this->assertFalse($result['success'], 'Unauthorized user should not be able to cancel claim');
        $this->assertContains('No tienes permisos para cancelar este reclamo', $result['errors']);
    }
    
    /**
     * Test cancel claim - admin can cancel any claim
     */
    public function testCancelClaimAsAdmin() {
        // Create a claim for user 1
        $createResult = $this->claim->create(1, 1, 1);
        $claimId = $createResult['claim_id'];
        
        // Admin (user 4) cancels the claim
        $result = $this->claim->cancelClaim($claimId, 4);
        
        $this->assertTrue($result['success'], 'Admin should be able to cancel any claim');
    }
    
    /**
     * Test cancel claim - cannot cancel completed claim
     */
    public function testCancelCompletedClaim() {
        // Create a completed claim
        $createResult = $this->claim->create(1, 1, 1, 5);
        $claimId = $createResult['claim_id'];
        
        $result = $this->claim->cancelClaim($claimId, 1);
        
        $this->assertFalse($result['success'], 'Should not be able to cancel completed claim');
        $this->assertContains('No se puede cancelar un reclamo ya procesado', $result['errors']);
    }
}