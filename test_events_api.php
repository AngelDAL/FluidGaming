<?php
/**
 * Test script for Events API
 */

require_once 'config/database.php';
require_once 'models/Event.php';

echo "Testing Event Model and API...\n\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    echo "✓ Database connection successful\n";
    
    // Initialize Event model
    $event = new Event($db);
    echo "✓ Event model initialized\n";
    
    // Test validation
    echo "\n--- Testing Validation ---\n";
    
    // Test empty name
    $errors = $event->validateEvent('', 'Test description', '2025-08-01 10:00:00', '2025-08-01 12:00:00');
    if (!empty($errors)) {
        echo "✓ Empty name validation: " . implode(', ', $errors) . "\n";
    }
    
    // Test invalid dates
    $errors = $event->validateEvent('Test Event', 'Test description', '2025-08-01 12:00:00', '2025-08-01 10:00:00');
    if (!empty($errors)) {
        echo "✓ Invalid date order validation: " . implode(', ', $errors) . "\n";
    }
    
    // Test past date
    $errors = $event->validateEvent('Test Event', 'Test description', '2024-01-01 10:00:00', '2024-01-01 12:00:00');
    if (!empty($errors)) {
        echo "✓ Past date validation: " . implode(', ', $errors) . "\n";
    }
    
    // Test short duration
    $errors = $event->validateEvent('Test Event', 'Test description', '2025-08-01 10:00:00', '2025-08-01 10:30:00');
    if (!empty($errors)) {
        echo "✓ Short duration validation: " . implode(', ', $errors) . "\n";
    }
    
    // Test valid event
    $futureStart = date('Y-m-d H:i:s', strtotime('+1 day'));
    $futureEnd = date('Y-m-d H:i:s', strtotime('+1 day +2 hours'));
    $errors = $event->validateEvent('Test Event', 'Test description', $futureStart, $futureEnd);
    if (empty($errors)) {
        echo "✓ Valid event validation passed\n";
    } else {
        echo "✗ Valid event validation failed: " . implode(', ', $errors) . "\n";
    }
    
    // Test database operations
    echo "\n--- Testing Database Operations ---\n";
    
    // Test getting all events (should work even if empty)
    $result = $event->getAll();
    echo "✓ Get all events: Found " . $result['total'] . " events\n";
    
    // Test getting active events
    $activeEvents = $event->getActiveEvents();
    echo "✓ Get active events: Found " . count($activeEvents) . " active events\n";
    
    // Test getting upcoming events
    $upcomingEvents = $event->getUpcomingEvents();
    echo "✓ Get upcoming events: Found " . count($upcomingEvents) . " upcoming events\n";
    
    echo "\n--- Event Model Tests Completed Successfully ---\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>