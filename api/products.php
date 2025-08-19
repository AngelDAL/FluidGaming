<?php
// api/products.php
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Stand.php';
require_once '../models/Claim.php';
require_once '../models/Event.php';
require_once '../models/User.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'claimable_count':
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'user_id requerido']);
            exit;
        }
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        $event = new Event($db);
        $claim = new Claim($db);
        $current_user = $user->getById($userId);
        if (!$current_user) {
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
            exit;
        }
        $activeEvents = $event->getActiveEvents();
        $selectedEventId = count($activeEvents) > 0 ? $activeEvents[0]['id'] : null;
        $count = 0;
        if ($selectedEventId) {
            // Obtener productos disponibles para reclamar
            $query = "SELECT p.*, s.id as stand_id, c.id as claim_id\n                    FROM products p\n                    LEFT JOIN stands s ON p.stand_id = s.id\n                    LEFT JOIN claims c ON p.id = c.product_id AND c.user_id = :user_id\n                    WHERE s.event_id = :event_id AND p.is_active = 1\n                    ORDER BY s.name, p.name";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':event_id', $selectedEventId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $allProducts = $stmt->fetchAll();
            foreach ($allProducts as $prod) {
                $isClaimed = !empty($prod['claim_id']);
                $canAfford = $current_user['total_points'] >= $prod['points_required'];
                if (!$isClaimed && $canAfford) {
                    $count++;
                }
            }
        }
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    // ...otros endpoints futuros...
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        exit;
}
