<?php
require_once 'config/database.php';
require_once 'models/Event.php';

$database = new Database();
$db = $database->getConnection();
$event = new Event($db);

$allEvents = $event->getAll(1, 10);
foreach ($allEvents['events'] as $evt) {
    echo 'Event: ' . $evt['name'] . ' - Active: ' . ($evt['is_active'] ? 'Yes' : 'No') . ' - Start: ' . $evt['start_date'] . ' - End: ' . $evt['end_date'] . ' - Now: ' . date('Y-m-d H:i:s') . PHP_EOL;
}

$activeEvents = $event->getActiveEvents();
echo 'Active events count: ' . count($activeEvents) . PHP_EOL;
?>