<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$startDate = date('Y-m-d H:i:s', strtotime('-6 hours'));
$endDate = date('Y-m-d H:i:s', strtotime('+1 week'));

$query = 'UPDATE events SET start_date = :start_date, end_date = :end_date WHERE id = 1';
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);

if ($stmt->execute()) {
    echo 'Event updated successfully' . PHP_EOL;
    echo 'New start date: ' . $startDate . PHP_EOL;
    echo 'New end date: ' . $endDate . PHP_EOL;
} else {
    echo 'Failed to update event' . PHP_EOL;
}
?>