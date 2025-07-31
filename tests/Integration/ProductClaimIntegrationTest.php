<?php
/**
 * Integration tests for complete product claim process
 */

require_once __DIR__ . '/../bootstrap.php';

class ProductClaimIntegrationTest extends BaseTestCase {
    private $claim;
    private $pointTransaction;
    private $user;
    
    public function setUp() {
        parent::setUp();
        $this->claim = new Claim($this->db);
        $this->pointTransaction = new PointTransaction($this->db);
        $this->user = new User($this->db);
    }
    
    /**
     * Test complete product claim process from points earning to claim completion
     * Requirements: 4.1, 4.2, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7
     */
    public function testCompleteProductClaimProcess() {
        // Step 1: Create a new user
        $userResult = $this->user->create('claimuser', 'claim@example.com', 'password123');
        $this->assertTrue($userResult['success']);
        $userId = $userResult['user_id'];
        
        // Step 2: User earns points through tournament participation
        $pointsResult = $this->pointTransaction->create(
            $userId,
            100, // enough for both products
            'earned',
            'tournament',
            3, // assistant
            1,
            ['tournament_name' => 'Main Tournament']
        );
        $this->assertTrue($pointsResult['success'], 'Points earning should succeed');
        
        // Verify user has points
        $userData = $this->user->getById($userId);
        $this->assertEquals(100, $userData['total_points']);
        
        // Step 3: User attempts to claim first product (costs 75 points)
        $claim1Result = $this->claim->create($userId, 1, 1);
        $this->assertTrue($claim1Result['success'], 'First claim should succeed');
        $claim1Id = $claim1Result['claim_id'];
        
        // Verify claim was created as pending
        $claim1Data = $this->claim->getById($claim1Id);
        $this->assertEquals('pending', $claim1Data['status']);
        $this->assertNull($claim1Data['processed_by']);
        
        // Step 4: Stand manager verifies user points
        $pointsVerification = $this->claim->verifyUserPoints($userId, 1);
        $this->assertTrue($pointsVerification['has_sufficient_points']);
        $this->assertEquals(100, $pointsVerification['user_points']);
        $this->assertEquals(75, $pointsVerification['required_points']);
        
        // Step 5: Stand manager processes the claim
        $processResult = $this->claim->processClaim($claim1Id, 5); // manager1
        $this->assertTrue($processResult['success'], 'Claim processing should succeed');
        
        // Verify claim was processed
        $processedClaim = $this->claim->getById($claim1Id);
        $this->assertEquals('completed', $processedClaim['status']);
        $this->assertEquals(5, $processedClaim['processed_by']);
        $this->assertEquals('manager1', $processedClaim['processed_by_name']);
        
        // Step 6: User attempts to claim the same product again (should fail - uniqueness)
        $duplicateClaimResult = $this->claim->create($userId, 1, 1);
        $this->assertFalse($duplicateClaimResult['success'], 'Duplicate claim should fail');
        $this->assertContains('El usuario ya ha reclamado este producto', $duplicateClaimResult['errors']);
        
        // Step 7: User claims a different product (costs 200 points - should fail due to insufficient points)
        $expensiveClaimResult = $this->claim->create($userId, 2, 1);
        $this->assertFalse($expensiveClaimResult['success'], 'Expensive product claim should fail');
        $this->assertContains('Puntos insuficientes para reclamar este producto', $expensiveClaimResult['errors']);
        
        // Step 8: User earns more points
        $morePointsResult = $this->pointTransaction->create(
            $userId,
            150, // now has 250 total
            'earned',
            'challenge',
            3,
            null,
            ['challenge_name' => 'Special Challenge']
        );
        $this->assertTrue($morePointsResult['success']);
        
        // Verify total points
        $updatedUserData = $this->user->getById($userId);
        $this->assertEquals(250, $updatedUserData['total_points']);
        
        // Step 9: User can now claim the expensive product
        $expensiveClaimResult2 = $this->claim->create($userId, 2, 1, 5); // directly processed
        $this->assertTrue($expensiveClaimResult2['success'], 'Expensive product claim should now succeed');
        
        // Verify claim was created as completed
        $expensiveClaim = $this->claim->getById($expensiveClaimResult2['claim_id']);
        $this->assertEquals('completed', $expensiveClaim['status']);
        $this->assertEquals(5, $expensiveClaim['processed_by']);
        
        // Step 10: Verify user's claim history
        $userClaims = $this->claim->getByUserId($userId);
        $this->assertEquals(2, count($userClaims), 'User should have 2 claims');
        
        // Verify claim details
        $completedClaims = $this->claim->getByUserId($userId, 'completed');
        $this->assertEquals(2, count($completedClaims), 'Both claims should be completed');
        
        // Step 11: Verify stand manager's claim history
        $standClaims = $this->claim->getByStandId(1);
        $this->assertEquals(2, count($standClaims), 'Stand should have 2 claims');
        
        // Step 12: Verify claim statistics
        $claimStats = $this->claim->getClaimStats(1); // for stand 1
        $this->assertEquals(2, $claimStats['total_claims']);
        $this->assertEquals(2, $claimStats['completed_claims']);
        $this->assertEquals(0, $claimStats['pending_claims']);
        $this->assertEquals(275, $claimStats['total_points_claimed']); // 75 + 200
    }
    
    /**
     * Test product claim with points verification edge cases
     * Requirements: 6.2, 6.3, 6.6
     */
    public function testProductClaimPointsVerificationEdgeCases() {
        $userId = $this->createTestUser('edgeclaimuser', 'edgeclaim@example.com');
        
        // Give user exactly the required points
        $this->pointTransaction->create($userId, 75, 'earned', 'tournament', 3, 1);
        
        // Claim should succeed with exact points
        $exactPointsResult = $this->claim->create($userId, 1, 1);
        $this->assertTrue($exactPointsResult['success'], 'Claim with exact points should succeed');
        
        // Create another user with one point less than required
        $userId2 = $this->createTestUser('shortuser', 'short@example.com');
        $this->pointTransaction->create($userId2, 74, 'earned', 'tournament', 3, 1); // 1 point short
        
        // Claim should fail
        $shortPointsResult = $this->claim->create($userId2, 1, 1);
        $this->assertFalse($shortPointsResult['success'], 'Claim with insufficient points should fail');
        $this->assertEquals(74, $shortPointsResult['points_info']['user_points']);
        $this->assertEquals(75, $shortPointsResult['points_info']['required_points']);
        $this->assertFalse($shortPointsResult['points_info']['has_sufficient_points']);
    }
    
    /**
     * Test claim process with points changes between creation and processing
     * Requirements: 6.2, 6.5, 6.7
     */
    public function testClaimProcessWithPointsChanges() {
        $userId = $this->createTestUser('changinguser', 'changing@example.com');
        
        // User earns points
        $this->pointTransaction->create($userId, 100, 'earned', 'tournament', 3, 1);
        
        // User creates a pending claim
        $claimResult = $this->claim->create($userId, 1, 1); // costs 75 points
        $this->assertTrue($claimResult['success']);
        $claimId = $claimResult['claim_id'];
        
        // Simulate user's points being reduced (maybe they claimed something else)
        $stmt = $this->db->prepare("UPDATE users SET total_points = 50 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Stand manager tries to process the claim
        $processResult = $this->claim->processClaim($claimId, 5);
        $this->assertFalse($processResult['success'], 'Processing should fail when user no longer has sufficient points');
        $this->assertContains('El usuario ya no tiene puntos suficientes', $processResult['errors']);
        
        // Verify claim remains pending
        $claimData = $this->claim->getById($claimId);
        $this->assertEquals('pending', $claimData['status']);
        
        // User earns points again
        $stmt = $this->db->prepare("UPDATE users SET total_points = 80 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Now processing should succeed
        $processResult2 = $this->claim->processClaim($claimId, 5);
        $this->assertTrue($processResult2['success'], 'Processing should succeed when user has sufficient points again');
        
        // Verify claim is completed
        $finalClaimData = $this->claim->getById($claimId);
        $this->assertEquals('completed', $finalClaimData['status']);
    }
    
    /**
     * Test multiple users claiming different products from same stand
     * Requirements: 6.1, 6.2, 6.4, 7.2
     */
    public function testMultipleUsersClaimingFromSameStand() {
        // Create multiple users
        $user1Id = $this->createTestUser('multiuser1', 'multi1@example.com');
        $user2Id = $this->createTestUser('multiuser2', 'multi2@example.com');
        $user3Id = $this->createTestUser('multiuser3', 'multi3@example.com');
        
        // Give all users sufficient points
        $this->pointTransaction->create($user1Id, 100, 'earned', 'tournament', 3, 1);
        $this->pointTransaction->create($user2Id, 250, 'earned', 'tournament', 3, 1);
        $this->pointTransaction->create($user3Id, 150, 'earned', 'challenge', 3);
        
        // User 1 claims product 1 (75 points)
        $claim1Result = $this->claim->create($user1Id, 1, 1, 5);
        $this->assertTrue($claim1Result['success']);
        
        // User 2 claims product 2 (200 points)
        $claim2Result = $this->claim->create($user2Id, 2, 1, 5);
        $this->assertTrue($claim2Result['success']);
        
        // User 3 claims product 1 (should succeed - different user)
        $claim3Result = $this->claim->create($user3Id, 1, 1, 5);
        $this->assertTrue($claim3Result['success'], 'Different user should be able to claim same product');
        
        // User 1 tries to claim product 1 again (should fail - same user, same product)
        $duplicateResult = $this->claim->create($user1Id, 1, 1, 5);
        $this->assertFalse($duplicateResult['success'], 'Same user cannot claim same product twice');
        
        // User 1 claims product 2 (should succeed - same user, different product)
        $differentProductResult = $this->claim->create($user1Id, 2, 1, 5);
        $this->assertFalse($differentProductResult['success'], 'Should fail due to insufficient points');
        
        // Verify stand statistics
        $standStats = $this->claim->getClaimStats(1);
        $this->assertEquals(3, $standStats['total_claims']);
        $this->assertEquals(3, $standStats['completed_claims']);
        $this->assertEquals(0, $standStats['pending_claims']);
        $this->assertEquals(350, $standStats['total_points_claimed']); // 75 + 200 + 75
        
        // Verify individual user statistics
        $user1Stats = $this->claim->getClaimStats(null, $user1Id);
        $this->assertEquals(1, $user1Stats['total_claims']);
        $this->assertEquals(75, $user1Stats['total_points_claimed']);
        
        $user2Stats = $this->claim->getClaimStats(null, $user2Id);
        $this->assertEquals(1, $user2Stats['total_claims']);
        $this->assertEquals(200, $user2Stats['total_points_claimed']);
    }
    
    /**
     * Test claim cancellation scenarios
     * Requirements: 6.5, 6.7
     */
    public function testClaimCancellationScenarios() {
        $userId = $this->createTestUser('canceluser', 'cancel@example.com');
        $otherUserId = $this->createTestUser('otheruser', 'other@example.com');
        
        // Give users points
        $this->pointTransaction->create($userId, 100, 'earned', 'tournament', 3, 1);
        $this->pointTransaction->create($otherUserId, 100, 'earned', 'tournament', 3, 1);
        
        // User creates a pending claim
        $claimResult = $this->claim->create($userId, 1, 1);
        $this->assertTrue($claimResult['success']);
        $claimId = $claimResult['claim_id'];
        
        // User cancels their own claim
        $cancelResult = $this->claim->cancelClaim($claimId, $userId);
        $this->assertTrue($cancelResult['success'], 'User should be able to cancel their own claim');
        
        // Verify claim was deleted
        $claimData = $this->claim->getById($claimId);
        $this->assertFalse($claimData, 'Cancelled claim should be deleted');
        
        // Create another claim
        $claim2Result = $this->claim->create($userId, 1, 1);
        $claim2Id = $claim2Result['claim_id'];
        
        // Other user tries to cancel (should fail)
        $unauthorizedCancelResult = $this->claim->cancelClaim($claim2Id, $otherUserId);
        $this->assertFalse($unauthorizedCancelResult['success'], 'Unauthorized user should not be able to cancel claim');
        $this->assertContains('No tienes permisos para cancelar este reclamo', $unauthorizedCancelResult['errors']);
        
        // Admin cancels the claim (should succeed)
        $adminCancelResult = $this->claim->cancelClaim($claim2Id, 4); // admin user
        $this->assertTrue($adminCancelResult['success'], 'Admin should be able to cancel any claim');
        
        // Create a completed claim
        $completedClaimResult = $this->claim->create($userId, 1, 1, 5);
        $completedClaimId = $completedClaimResult['claim_id'];
        
        // Try to cancel completed claim (should fail)
        $cancelCompletedResult = $this->claim->cancelClaim($completedClaimId, $userId);
        $this->assertFalse($cancelCompletedResult['success'], 'Should not be able to cancel completed claim');
        $this->assertContains('No se puede cancelar un reclamo ya procesado', $cancelCompletedResult['errors']);
    }
    
    /**
     * Test stand manager workflow for processing claims
     * Requirements: 6.2, 6.7, 7.2
     */
    public function testStandManagerClaimProcessingWorkflow() {
        // Create multiple users with claims
        $user1Id = $this->createTestUser('standuser1', 'stand1@example.com');
        $user2Id = $this->createTestUser('standuser2', 'stand2@example.com');
        $user3Id = $this->createTestUser('standuser3', 'stand3@example.com');
        
        // Give users points
        $this->pointTransaction->create($user1Id, 100, 'earned', 'tournament', 3, 1);
        $this->pointTransaction->create($user2Id, 80, 'earned', 'challenge', 3);
        $this->pointTransaction->create($user3Id, 250, 'earned', 'bonus', 4);
        
        // Users create pending claims
        $claim1Result = $this->claim->create($user1Id, 1, 1); // 75 points
        $claim2Result = $this->claim->create($user2Id, 1, 1); // 75 points
        $claim3Result = $this->claim->create($user3Id, 2, 1); // 200 points
        
        $this->assertTrue($claim1Result['success']);
        $this->assertTrue($claim2Result['success']);
        $this->assertTrue($claim3Result['success']);
        
        // Stand manager gets pending claims
        $pendingClaims = $this->claim->getPendingClaimsForManager(5); // manager1
        $this->assertEquals(3, count($pendingClaims), 'Should have 3 pending claims');
        
        // Verify all claims are pending
        foreach ($pendingClaims as $claim) {
            $this->assertEquals('pending', $claim['status']);
            $this->assertEquals(1, $claim['stand_id']);
        }
        
        // Stand manager processes claims one by one
        foreach ($pendingClaims as $claim) {
            // Verify points before processing
            $pointsVerification = $this->claim->verifyUserPoints($claim['user_id'], $claim['product_id']);
            $this->assertTrue($pointsVerification['has_sufficient_points'], 
                "User {$claim['user_nickname']} should have sufficient points");
            
            // Process the claim
            $processResult = $this->claim->processClaim($claim['id'], 5);
            $this->assertTrue($processResult['success'], 
                "Processing claim for {$claim['user_nickname']} should succeed");
        }
        
        // Verify no pending claims remain
        $remainingPendingClaims = $this->claim->getPendingClaimsForManager(5);
        $this->assertEquals(0, count($remainingPendingClaims), 'Should have no pending claims left');
        
        // Verify all claims are completed
        $standClaims = $this->claim->getByStandId(1, 'completed');
        $this->assertEquals(3, count($standClaims), 'All claims should be completed');
        
        // Verify final statistics
        $finalStats = $this->claim->getClaimStats(1);
        $this->assertEquals(3, $finalStats['total_claims']);
        $this->assertEquals(3, $finalStats['completed_claims']);
        $this->assertEquals(0, $finalStats['pending_claims']);
        $this->assertEquals(350, $finalStats['total_points_claimed']); // 75 + 75 + 200
    }
}