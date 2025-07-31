<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Test the exact query from getActiveEvents
$query = "SELECT e.*, u.nickname as created_by_name 
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id 
          WHERE e.is_active = 1 
          AND NOW() BETWEEN e.start_date AND e.end_date 
          ORDER BY e.start_date ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll();

echo "Active events query results: " . count($results) . PHP_EOL;

// Also test the individual conditions
$query2 = "SELECT e.*, u.nickname as created_by_name, 
           NOW() as now_time
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id";

$stmt2 = $db->prepare($query2);
$stmt2->execute();
$results2 = $stmt2->fetchAll();

foreach ($results2 as $event) {
    echo "Event: " . $event['name'] . PHP_EOL;
    echo "  Current time: " . $event['now_time'] . PHP_EOL;
    echo "  Start date: " . $event['start_date'] . PHP_EOL;
    echo "  End date: " . $event['end_date'] . PHP_EOL;
    echo "  Is active: " . $event['is_active'] . PHP_EOL;
    
    // Check if current time is between start and end dates
    $current = new DateTime($event['now_time']);
    $start = new DateTime($event['start_date']);
    $end = new DateTime($event['end_date']);
    $inRange = ($current >= $start && $current <= $end);
    echo "  Is in range: " . ($inRange ? 'Yes' : 'No') . PHP_EOL;
    echo "---" . PHP_EOL;
}
?>