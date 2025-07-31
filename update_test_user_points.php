<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Give user ID 2 some points for testing
$updateQuery = 'UPDATE users SET total_points = 150 WHERE id = 2';
$stmt = $db->prepare($updateQuery);
$stmt->execute();

echo "Updated user points to 150 for testing\n";
?>