<?php
require_once 'config/database.php';
require_once 'models/Event.php';

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);

// Get all events
$allEvents = $event->getAll(1, 10);
echo 'Total events: ' . $allEvents['total'] . PHP_EOL;

if ($allEvents['total'] > 0) {
    foreach ($allEvents['events'] as $evt) {
        echo 'Event: ' . $evt['name'] . ' - Active: ' . ($evt['is_active'] ? 'Yes' : 'No') . ' - Dates: ' . $evt['start_date'] . ' to ' . $evt['end_date'] . PHP_EOL;
    }
} else {
    echo 'No events found. Creating a test event...' . PHP_EOL;
    
    // Create a test event
    $startDate = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $endDate = date('Y-m-d H:i:s', strtotime('+1 week'));
    
    $result = $event->create('Test Event for Tournaments', 'Event for testing tournament functionality', $startDate, $endDate, 1);
    
    if ($result['success']) {
        echo 'Test event created successfully with ID: ' . $result['event_id'] . PHP_EOL;
    } else {
        echo 'Failed to create test event: ' . implode(', ', $result['errors']) . PHP_EOL;
    }
}
?>