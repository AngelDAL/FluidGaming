<?php
/**
 * Integration tests for tournament and points assignment integration
 */

require_once __DIR__ . '/../bootstrap.php';

class TournamentPointsIntegrationTest extends BaseTestCase {
    private $pointTransaction;
    private $user;
    
    public function setUp() {
        parent::setUp();
        $this->pointTransaction = new PointTransaction($this->db);
        $this->user = new User($this->db);
    }
    
    /**
     * Test complete tournament participation and points assignment flow
     * Requirements: 3.1, 3.2, 3.4, 4.1, 4.2, 4.4
     */
    public function testTournamentParticipationAndPointsFlow() {
        // Create additional test users
        $user1Id = $this->createTestUser('player1', 'player1@example.com');
        $user2Id = $this->createTestUser('player2', 'player2@example.com');
        $user3Id = $this->createTestUser('player3', 'player3@example.com');
        
        // Create additional tournament
        $stmt = $this->db->prepare("
            INSERT INTO tournaments (event_id, name, scheduled_time, points_reward, status) 
            VALUES (1, 'Championship Tournament', '2025-06-15 14:00:00', 100, 'active')
        ");
        $stmt->execute();
        $tournamentId = $this->db->lastInsertId();
        
        // Simulate tournament completion - assign points to winners
        $firstPlaceResult = $this->pointTransaction->create(
            $user1Id,
            100, // first place gets full points
            'earned',
            'tournament',
            3, // assigned by assistant
            $tournamentId,
            ['position' => 1, 'tournament_name' => 'Championship Tournament']
        );
        $this->assertTrue($firstPlaceResult['success'], 'First place points assignment should succeed');
        
        $secondPlaceResult = $this->pointTransaction->create(
            $user2Id,
            75, // second place gets 75% of points
            'earned',
            'tournament',
            3,
            $tournamentId,
            ['position' => 2, 'tournament_name' => 'Championship Tournament']
        );
        $this->assertTrue($secondPlaceResult['success'], 'Second place points assignment should succeed');
        
        $thirdPlaceResult = $this->pointTransaction->create(
            $user3Id,
            50, // third place gets 50% of points
            'earned',
            'tournament',
            3,
            $tournamentId,
            ['position' => 3, 'tournament_name' => 'Championship Tournament']
        );
        $this->assertTrue($thirdPlaceResult['success'], 'Third place points assignment should succeed');
        
        // Verify points were assigned correctly
        $user1Data = $this->user->getById($user1Id);
        $user2Data = $this->user->getById($user2Id);
        $user3Data = $this->user->getById($user3Id);
        
        $this->assertEquals(100, $user1Data['total_points']);
        $this->assertEquals(75, $user2Data['total_points']);
        $this->assertEquals(50, $user3Data['total_points']);
        
        // Verify transaction details
        $user1Transaction = $this->pointTransaction->getById($firstPlaceResult['transaction_id']);
        $this->assertEquals($tournamentId, $user1Transaction['tournament_id']);
        $this->assertEquals('Championship Tournament', $user1Transaction['tournament_name']);
        $this->assertEquals(['position' => 1, 'tournament_name' => 'Championship Tournament'], $user1Transaction['metadata']);
        
        // Test points statistics for tournament source
        $user1Stats = $this->pointTransaction->getUserPointsStats($user1Id);
        $this->assertEquals(100, $user1Stats['total_earned']);
        $this->assertEquals(100, $user1Stats['tournament_points']);
        $this->assertEquals(0, $user1Stats['challenge_points']);
        $this->assertEquals(0, $user1Stats['bonus_points']);
        $this->assertEquals(1, $user1Stats['total_transactions']);
    }
    
    /**
     * Test tournament points assignment with inactive event
     * Requirements: 2.3, 4.3
     */
    public function testTournamentPointsWithInactiveEvent() {
        $userId = $this->createTestUser('tournamentuser', 'tournament@example.com');
        
        // Deactivate the event
        $stmt = $this->db->prepare("UPDATE events SET is_active = 0 WHERE id = 1");
        $stmt->execute();
        
        // Try to assign tournament points
        $result = $this->pointTransaction->create(
            $userId,
            50,
            'earned',
            'tournament',
            3,
            1 // tournament from inactive event
        );
        
        $this->assertFalse($result['success'], 'Points assignment should fail for tournament from inactive event');
        $this->assertContains('El evento del torneo no está activo', $result['errors']);
        
        // Verify user points remain unchanged
        $userData = $this->user->getById($userId);
        $this->assertEquals(0, $userData['total_points']);
    }
    
    /**
     * Test tournament points assignment with date validation
     * Requirements: 2.1, 2.2, 2.3
     */
    public function testTournamentPointsWithDateValidation() {
        $userId = $this->createTestUser('dateuser', 'date@example.com');
        
        // Create an event with past dates
        $stmt = $this->db->prepare("
            INSERT INTO events (name, description, start_date, end_date, is_active, created_by) 
            VALUES ('Past Event', 'Event in the past', '2024-01-01 00:00:00', '2024-01-31 23:59:59', 1, 4)
        ");
        $stmt->execute();
        $pastEventId = $this->db->lastInsertId();
        
        // Create tournament in past event
        $stmt = $this->db->prepare("
            INSERT INTO tournaments (event_id, name, scheduled_time, points_reward, status) 
            VALUES (?, 'Past Tournament', '2024-01-15 10:00:00', 50, 'completed')
        ");
        $stmt->execute([$pastEventId]);
        $pastTournamentId = $this->db->lastInsertId();
        
        // Try to assign points for past tournament
        $result = $this->pointTransaction->create(
            $userId,
            50,
            'earned',
            'tournament',
            3,
            $pastTournamentId
        );
        
        $this->assertFalse($result['success'], 'Points assignment should fail for tournament from past event');
        $this->assertContains('El evento del torneo no está activo', $result['errors']);
    }
    
    /**
     * Test multiple tournaments in same event
     * Requirements: 3.1, 3.2, 4.1, 4.2
     */
    public function testMultipleTournamentsInSameEvent() {
        $userId = $this->createTestUser('multitournament', 'multi@example.com');
        
        // Create multiple tournaments in the same event
        $tournaments = [];
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $this->db->prepare("
                INSERT INTO tournaments (event_id, name, scheduled_time, points_reward, status) 
                VALUES (1, 'Tournament $i', '2025-06-0$i 10:00:00', ?, 'completed')
            ");
            $points = $i * 25; // 25, 50, 75 points
            $stmt->execute([$points]);
            $tournaments[$i] = ['id' => $this->db->lastInsertId(), 'points' => $points];
        }
        
        // User participates in all tournaments
        foreach ($tournaments as $i => $tournament) {
            $result = $this->pointTransaction->create(
                $userId,
                $tournament['points'],
                'earned',
                'tournament',
                3,
                $tournament['id'],
                ['tournament_number' => $i]
            );
            $this->assertTrue($result['success'], "Tournament $i points assignment should succeed");
        }
        
        // Verify total points
        $userData = $this->user->getById($userId);
        $this->assertEquals(150, $userData['total_points']); // 25 + 50 + 75
        
        // Verify transaction history
        $transactions = $this->pointTransaction->getByUserId($userId);
        $this->assertEquals(3, count($transactions['transactions']));
        $this->assertEquals(3, $transactions['total']);
        
        // Verify all transactions are tournament type
        foreach ($transactions['transactions'] as $transaction) {
            $this->assertEquals('tournament', $transaction['source']);
            $this->assertEquals('earned', $transaction['type']);
        }
    }
    
    /**
     * Test tournament points with different sources mixed
     * Requirements: 4.1, 4.2, 4.4
     */
    public function testMixedPointsSources() {
        $userId = $this->createTestUser('mixeduser', 'mixed@example.com');
        
        // Tournament points
        $tournamentResult = $this->pointTransaction->create(
            $userId,
            50,
            'earned',
            'tournament',
            3,
            1,
            ['source_detail' => 'Tournament victory']
        );
        $this->assertTrue($tournamentResult['success']);
        
        // Challenge points
        $challengeResult = $this->pointTransaction->create(
            $userId,
            30,
            'earned',
            'challenge',
            3,
            null,
            ['source_detail' => 'Side challenge completion']
        );
        $this->assertTrue($challengeResult['success']);
        
        // Bonus points
        $bonusResult = $this->pointTransaction->create(
            $userId,
            20,
            'earned',
            'bonus',
            4, // admin gives bonus
            null,
            ['source_detail' => 'Special achievement bonus']
        );
        $this->assertTrue($bonusResult['success']);
        
        // Verify total points
        $userData = $this->user->getById($userId);
        $this->assertEquals(100, $userData['total_points']);
        
        // Verify detailed statistics
        $stats = $this->pointTransaction->getUserPointsStats($userId);
        $this->assertEquals(100, $stats['total_earned']);
        $this->assertEquals(50, $stats['tournament_points']);
        $this->assertEquals(30, $stats['challenge_points']);
        $this->assertEquals(20, $stats['bonus_points']);
        $this->assertEquals(3, $stats['total_transactions']);
        
        // Test filtering by source
        $tournamentTransactions = $this->pointTransaction->getByUserId($userId, 1, 10, 'earned');
        $this->assertEquals(3, count($tournamentTransactions['transactions']));
        
        // Test recent transactions with source filter
        $recentTournament = $this->pointTransaction->getRecent(1, 10, 'earned', 'tournament');
        $tournamentCount = 0;
        foreach ($recentTournament['transactions'] as $transaction) {
            if ($transaction['user_id'] == $userId) {
                $tournamentCount++;
            }
        }
        $this->assertEquals(1, $tournamentCount);
    }
    
    /**
     * Test tournament points assignment validation edge cases
     * Requirements: 4.3, 4.4
     */
    public function testTournamentPointsValidationEdgeCases() {
        $userId = $this->createTestUser('edgeuser', 'edge@example.com');
        
        // Test maximum points limit
        $maxPointsResult = $this->pointTransaction->create(
            $userId,
            10000, // exactly at limit
            'earned',
            'tournament',
            3,
            1
        );
        $this->assertTrue($maxPointsResult['success'], 'Maximum allowed points should succeed');
        
        // Test over maximum points limit
        $overMaxResult = $this->pointTransaction->create(
            $userId,
            10001, // over limit
            'earned',
            'tournament',
            3,
            1
        );
        $this->assertFalse($overMaxResult['success'], 'Over maximum points should fail');
        $this->assertContains('Los puntos no pueden ser mayor a 10,000', $overMaxResult['errors']);
        
        // Test zero points
        $zeroPointsResult = $this->pointTransaction->create(
            $userId,
            0,
            'earned',
            'tournament',
            3,
            1
        );
        $this->assertFalse($zeroPointsResult['success'], 'Zero points should fail');
        $this->assertContains('Los puntos deben ser mayor a 0', $zeroPointsResult['errors']);
        
        // Test negative points
        $negativePointsResult = $this->pointTransaction->create(
            $userId,
            -50,
            'earned',
            'tournament',
            3,
            1
        );
        $this->assertFalse($negativePointsResult['success'], 'Negative points should fail');
        $this->assertContains('Los puntos deben ser mayor a 0', $negativePointsResult['errors']);
        
        // Test non-existing tournament
        $invalidTournamentResult = $this->pointTransaction->create(
            $userId,
            50,
            'earned',
            'tournament',
            3,
            999 // non-existing tournament
        );
        $this->assertFalse($invalidTournamentResult['success'], 'Non-existing tournament should fail');
        $this->assertContains('El torneo no existe', $invalidTournamentResult['errors']);
    }
    
    /**
     * Test concurrent tournament points assignment
     * Requirements: 4.1, 4.2, 4.4
     */
    public function testConcurrentTournamentPointsAssignment() {
        $user1Id = $this->createTestUser('concurrent1', 'concurrent1@example.com');
        $user2Id = $this->createTestUser('concurrent2', 'concurrent2@example.com');
        
        // Simulate concurrent points assignment by multiple assistants
        $assistant1 = 3; // assistant1
        $assistant2 = 4; // admin acting as assistant
        
        // Both assistants assign points to different users simultaneously
        $result1 = $this->pointTransaction->create(
            $user1Id,
            75,
            'earned',
            'tournament',
            $assistant1,
            1,
            ['assigned_by_name' => 'assistant1']
        );
        
        $result2 = $this->pointTransaction->create(
            $user2Id,
            60,
            'earned',
            'tournament',
            $assistant2,
            1,
            ['assigned_by_name' => 'admin1']
        );
        
        $this->assertTrue($result1['success'], 'Concurrent assignment 1 should succeed');
        $this->assertTrue($result2['success'], 'Concurrent assignment 2 should succeed');
        
        // Verify both users received their points
        $user1Data = $this->user->getById($user1Id);
        $user2Data = $this->user->getById($user2Id);
        
        $this->assertEquals(75, $user1Data['total_points']);
        $this->assertEquals(60, $user2Data['total_points']);
        
        // Verify transaction details
        $transaction1 = $this->pointTransaction->getById($result1['transaction_id']);
        $transaction2 = $this->pointTransaction->getById($result2['transaction_id']);
        
        $this->assertEquals($assistant1, $transaction1['assigned_by']);
        $this->assertEquals($assistant2, $transaction2['assigned_by']);
        $this->assertEquals('assistant1', $transaction1['assigned_by_nickname']);
        $this->assertEquals('admin1', $transaction2['assigned_by_nickname']);
    }
}