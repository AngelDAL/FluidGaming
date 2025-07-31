<?php
/**
 * Test script for Tournament API functionality
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/database.php';
require_once 'models/Tournament.php';
require_once 'models/Event.php';

echo "<h1>Testing Tournament Model and API</h1>";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    echo "<h2>✓ Database connection successful</h2>";
    
    // Initialize models
    $tournament = new Tournament($db);
    $event = new Event($db);
    
    echo "<h2>✓ Tournament and Event models initialized</h2>";
    
    // Test 1: Get active events for tournament creation
    echo "<h3>Test 1: Getting active events</h3>";
    $activeEvents = $event->getActiveEvents();
    echo "<p>Active events found: " . count($activeEvents) . "</p>";
    
    if (count($activeEvents) > 0) {
        $testEventId = $activeEvents[0]['id'];
        echo "<p>Using event ID: $testEventId for testing</p>";
        
        // Test 2: Validate tournament input
        echo "<h3>Test 2: Testing tournament validation</h3>";
        
        // Test with invalid data
        $errors = $tournament->validateTournament('', $testEventId, '', '');
        echo "<p>Validation errors for empty data: " . count($errors) . " errors</p>";
        if (!empty($errors)) {
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }
        
        // Test with valid data
        $futureDate = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $errors = $tournament->validateTournament('Test Tournament', $testEventId, $futureDate, 100);
        echo "<p>Validation errors for valid data: " . count($errors) . " errors</p>";
        
        // Test 3: Create a test tournament
        echo "<h3>Test 3: Creating test tournament</h3>";
        $testSpecs = [
            'gameMode' => '1vs1',
            'rules' => ['No cheating', 'Fair play'],
            'maxParticipants' => 16,
            'duration' => '30 minutes'
        ];
        
        $result = $tournament->create(
            'Test Tournament API',
            $testEventId,
            $futureDate,
            150,
            $testSpecs
        );
        
        if ($result['success']) {
            echo "<p>✓ Tournament created successfully with ID: " . $result['tournament_id'] . "</p>";
            $testTournamentId = $result['tournament_id'];
            
            // Test 4: Get tournament by ID
            echo "<h3>Test 4: Getting tournament by ID</h3>";
            $tournamentData = $tournament->getById($testTournamentId);
            if ($tournamentData) {
                echo "<p>✓ Tournament retrieved successfully</p>";
                echo "<p>Name: " . $tournamentData['name'] . "</p>";
                echo "<p>Points reward: " . $tournamentData['points_reward'] . "</p>";
                echo "<p>Specifications: " . json_encode($tournamentData['specifications']) . "</p>";
            } else {
                echo "<p>✗ Failed to retrieve tournament</p>";
            }
            
            // Test 5: Get tournaments by event
            echo "<h3>Test 5: Getting tournaments by event</h3>";
            $eventTournaments = $tournament->getByEventId($testEventId);
            echo "<p>Tournaments found for event: " . count($eventTournaments) . "</p>";
            
            // Test 6: Update tournament status
            echo "<h3>Test 6: Updating tournament status</h3>";
            $statusResult = $tournament->updateStatus($testTournamentId, 'active');
            if ($statusResult['success']) {
                echo "<p>✓ Tournament status updated to active</p>";
            } else {
                echo "<p>✗ Failed to update tournament status</p>";
            }
            
            // Test 7: Get upcoming tournaments
            echo "<h3>Test 7: Getting upcoming tournaments</h3>";
            $upcomingTournaments = $tournament->getUpcoming(5);
            echo "<p>Upcoming tournaments found: " . count($upcomingTournaments) . "</p>";
            
            // Test 8: Get active tournaments
            echo "<h3>Test 8: Getting active tournaments</h3>";
            $activeTournaments = $tournament->getActive();
            echo "<p>Active tournaments found: " . count($activeTournaments) . "</p>";
            
            // Test 9: Clean up - delete test tournament
            echo "<h3>Test 9: Cleaning up test tournament</h3>";
            $deleteResult = $tournament->delete($testTournamentId);
            if ($deleteResult['success']) {
                echo "<p>✓ Test tournament deleted successfully</p>";
            } else {
                echo "<p>✗ Failed to delete test tournament: " . implode(', ', $deleteResult['errors']) . "</p>";
            }
            
        } else {
            echo "<p>✗ Failed to create tournament: " . implode(', ', $result['errors']) . "</p>";
        }
        
    } else {
        echo "<p>⚠ No active events found. Please create an active event first to test tournaments.</p>";
    }
    
    echo "<h2>✓ All Tournament API tests completed</h2>";
    
} catch (Exception $e) {
    echo "<h2>✗ Error during testing: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>