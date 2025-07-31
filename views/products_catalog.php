<?php

/**
 * Products Catalog View - Interface for users to view available products
 */

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Stand.php';
require_once '../models/Claim.php';
require_once '../models/Event.php';
require_once '../models/User.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
$stand = new Stand($db);
$claim = new Claim($db);
$event = new Event($db);
$user = new User($db);

$current_user_session = getCurrentUser();
$current_user = $user->getById($current_user_session['id']);

// Safety check - if user data couldn't be fetched, redirect to login
if (!$current_user) {
    header('Location: login.php');
    exit();
}

// Get active events
$activeEvents = $event->getActiveEvents();

// Get selected event
$selectedEventId = isset($_GET['event_id']) ? $_GET['event_id'] : (count($activeEvents) > 0 ? $activeEvents[0]['id'] : null);

// Get stands for selected event
$stands = [];
$standProducts = [];
$userClaims = [];

if ($selectedEventId) {
    // Get stands for the event
    $query = "SELECT s.*, u.nickname as manager_name FROM stands s 
              LEFT JOIN users u ON s.manager_id = u.id 
              WHERE s.event_id = :event_id 
              ORDER BY s.name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':event_id', $selectedEventId);
    $stmt->execute();
    $stands = $stmt->fetchAll();

    // Get all products for stands in this event with claim status for current user
    $query = "SELECT p.*, s.name as stand_name, s.id as stand_id,
                     c.id as claim_id, c.status as claim_status
              FROM products p 
              LEFT JOIN stands s ON p.stand_id = s.id 
              LEFT JOIN claims c ON p.id = c.product_id AND c.user_id = :user_id
              WHERE s.event_id = :event_id AND p.is_active = 1
              ORDER BY s.name, p.name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':event_id', $selectedEventId);
    $stmt->bindParam(':user_id', $current_user['id']);
    $stmt->execute();
    $allProducts = $stmt->fetchAll();

    // Group products by stand
    foreach ($allProducts as $prod) {
        $standProducts[$prod['stand_id']][] = $prod;
    }

    // Get user's claims for this event
    $userClaims = $claim->getByUserId($current_user['id']);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - Sistema de Puntos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .user-points {
            font-size: 1.2em;
            font-weight: bold;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .content {
            padding: 30px;
        }

        .event-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .event-selector h3 {
            color: #495057;
            margin-bottom: 15px;
        }

        .event-selector select {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
        }

        .stands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .stand-card {
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stand-card:hover {
            transform: translateY(-5px);
        }

        .stand-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .stand-header h3 {
            font-size: 1.3em;
            margin-bottom: 5px;
        }

        .stand-header p {
            opacity: 0.9;
            font-size: 0.9em;
        }

        .products-list {
            padding: 20px;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .product-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
        }

        .product-item.claimed {
            background: #f8f9fa;
            border-color: #28a745;
            opacity: 0.8;
        }

        .product-item.insufficient-points {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .product-item.can-claim {
            border-color: #28a745;
            background: #f8fff9;
        }

        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            color: #666;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .product-description {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .product-points {
            font-size: 1.1em;
            font-weight: bold;
            color: #667eea;
        }

        .product-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-claimed {
            background: #fff3cd;
            color: #856404;
        }

        .status-insufficient {
            background: #f8d7da;
            color: #721c24;
        }

        .status-can-claim {
            background: #d1ecf1;
            color: #0c5460;
        }

        .no-products {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-products h4 {
            margin-bottom: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 15px;
            color: #495057;
        }

        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-bar button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .filter-bar button:hover {
            background: #5a6fd8;
        }

        .stats-summary {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.9em;
            color: #666;
        }

        @media (max-width: 768px) {
            .stands-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-summary {
                flex-direction: column;
                gap: 10px;
            }

            .user-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ Catálogo de Productos</h1>
            <p>Descubre qué puedes canjear con tus puntos</p>

            <div class="user-info">
                <div class="user-points">
                    💰 Tus puntos: <?php echo number_format($current_user['total_points']); ?>
                </div>
                <a href="../" class="back-btn">← Volver al Dashboard</a>
            </div>
        </div>

        <div class="content">
            <?php if (empty($activeEvents)): ?>
                <div class="empty-state">
                    <h3>No hay eventos activos</h3>
                    <p>Actualmente no hay eventos en curso. Los productos estarán disponibles cuando haya eventos activos.</p>
                </div>
            <?php else: ?>
                <div class="event-selector">
                    <h3>Seleccionar Evento</h3>
                    <select onchange="changeEvent(this.value)">
                        <?php foreach ($activeEvents as $eventOption): ?>
                            <option value="<?php echo $eventOption['id']; ?>"
                                <?php echo $eventOption['id'] == $selectedEventId ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($eventOption['name']); ?>
                                (<?php echo date('d/m/Y', strtotime($eventOption['start_date'])); ?> -
                                <?php echo date('d/m/Y', strtotime($eventOption['end_date'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 30px; border-left: 4px solid #2196f3;">
                    <h4 style="color: #1976d2; margin-bottom: 10px;">ℹ️ Cómo funciona el canje de productos</h4>
                    <ul style="color: #424242; margin-left: 20px;">
                        <li><strong>Productos disponibles:</strong> Puedes ver todos los productos organizados por stand</li>
                        <li><strong>Puntos requeridos:</strong> Cada producto muestra cuántos puntos necesitas para canjearlo</li>
                        <li><strong>Estado del producto:</strong>
                            <span style="background: #d1ecf1; color: #0c5460; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">Puedes Reclamar</span> - Tienes suficientes puntos |
                            <span style="background: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">Puntos Insuficientes</span> - Necesitas más puntos |
                            <span style="background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">Ya Reclamado</span> - Solo puedes reclamar cada producto una vez
                        </li>
                        <li><strong>Para reclamar:</strong> Dirígete al stand correspondiente y muestra tu perfil al encargado</li>
                    </ul>
                </div>

                <?php if (empty($stands)): ?>
                    <div class="empty-state">
                        <h3>No hay stands en este evento</h3>
                        <p>Este evento aún no tiene stands configurados con productos.</p>
                    </div>
                <?php else: ?>
                    <?php
                    // Calculate statistics
                    $totalProducts = 0;
                    $availableProducts = 0;
                    $claimedProducts = 0;
                    $affordableProducts = 0;

                    foreach ($standProducts as $products) {
                        foreach ($products as $prod) {
                            $totalProducts++;
                            if ($prod['claim_id']) {
                                $claimedProducts++;
                            } else {
                                $availableProducts++;
                                if ($current_user['total_points'] >= $prod['points_required']) {
                                    $affordableProducts++;
                                }
                            }
                        }
                    }
                    ?>

                    <div class="stats-summary">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $totalProducts; ?></div>
                            <div class="stat-label">Total Productos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $availableProducts; ?></div>
                            <div class="stat-label">Disponibles</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $affordableProducts; ?></div>
                            <div class="stat-label">Puedes Canjear</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $claimedProducts; ?></div>
                            <div class="stat-label">Ya Reclamados</div>
                        </div>
                    </div>

                    <div class="filter-bar">
                        <input type="text" id="search_products" placeholder="Buscar productos..." oninput="filterProducts()">
                        <select id="filter_status" onchange="filterProducts()">
                            <option value="">Todos los estados</option>
                            <option value="available">Disponibles (todos)</option>
                            <option value="affordable">Puedo canjear ahora</option>
                            <option value="insufficient">Puntos insuficientes</option>
                            <option value="claimed">Ya reclamados por mí</option>
                        </select>
                        <button onclick="clearFilters()">Limpiar Filtros</button>
                    </div>

                    <div class="stands-grid">
                        <?php foreach ($stands as $standInfo): ?>
                            <div class="stand-card" data-stand-id="<?php echo $standInfo['id']; ?>">
                                <div class="stand-header">
                                    <h3><?php echo htmlspecialchars($standInfo['name']); ?></h3>
                                    <p>Encargado: <?php echo htmlspecialchars($standInfo['manager_name'] ?? 'Sin asignar'); ?></p>
                                </div>

                                <div class="products-list">
                                    <?php if (empty($standProducts[$standInfo['id']])): ?>
                                        <div class="no-products">
                                            <h4>Sin productos</h4>
                                            <p>Este stand aún no tiene productos disponibles.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($standProducts[$standInfo['id']] as $prod): ?>
                                            <?php
                                            $isClaimed = !empty($prod['claim_id']);
                                            $canAfford = $current_user['total_points'] >= $prod['points_required'];
                                            $statusClass = $isClaimed ? 'claimed' : ($canAfford ? 'can-claim' : 'insufficient-points');
                                            $statusText = $isClaimed ? 'Ya Reclamado' : ($canAfford ? 'Puedes Reclamar' : 'Puntos Insuficientes');
                                            $statusBadgeClass = $isClaimed ? 'status-claimed' : ($canAfford ? 'status-can-claim' : 'status-insufficient');
                                            ?>
                                            <div class="product-item <?php echo $statusClass; ?>"
                                                data-name="<?php echo strtolower($prod['name']); ?>"
                                                data-status="<?php echo $isClaimed ? 'claimed' : ($canAfford ? 'affordable' : 'insufficient'); ?>"
                                                data-points="<?php echo $prod['points_required']; ?>">

                                                <div class="product-image">
                                                    <?php if ($prod['image_url']): ?>
                                                        <img src="../<?php echo htmlspecialchars($prod['image_url']); ?>"
                                                            alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                                                    <?php else: ?>
                                                        📦
                                                    <?php endif; ?>
                                                </div>

                                                <div class="product-info">
                                                    <div class="product-name">
                                                        <?php echo htmlspecialchars($prod['name']); ?>
                                                    </div>
                                                    <?php if ($prod['description']): ?>
                                                        <div class="product-description">
                                                            <?php echo htmlspecialchars($prod['description']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="product-points">
                                                        💎 <?php echo number_format($prod['points_required']); ?> puntos
                                                    </div>
                                                </div>

                                                <div class="product-status <?php echo $statusBadgeClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </div>

                                                <?php if (!$isClaimed && $canAfford): ?>
                                                    <div style="position: absolute; bottom: 10px; right: 10px;">
                                                        <small style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.7em;">
                                                            ✓ Listo para canjear
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function changeEvent(eventId) {
            window.location.href = '?event_id=' + eventId;
        }

        function filterProducts() {
            const searchTerm = document.getElementById('search_products').value.toLowerCase();
            const statusFilter = document.getElementById('filter_status').value;

            const productItems = document.querySelectorAll('.product-item');
            const standCards = document.querySelectorAll('.stand-card');

            productItems.forEach(item => {
                const name = item.dataset.name;
                const status = item.dataset.status;

                let showItem = true;

                // Apply search filter
                if (searchTerm && !name.includes(searchTerm)) {
                    showItem = false;
                }

                // Apply status filter
                if (statusFilter) {
                    if (statusFilter === 'available' && status === 'claimed') {
                        showItem = false;
                    } else if (statusFilter === 'affordable' && status !== 'affordable') {
                        showItem = false;
                    } else if (statusFilter === 'insufficient' && status !== 'insufficient') {
                        showItem = false;
                    } else if (statusFilter === 'claimed' && status !== 'claimed') {
                        showItem = false;
                    }
                }

                item.style.display = showItem ? 'flex' : 'none';
            });

            // Hide stands that have no visible products
            standCards.forEach(card => {
                const visibleProducts = card.querySelectorAll('.product-item[style="display: flex"], .product-item:not([style*="display: none"])');
                const hasNoProducts = card.querySelector('.no-products');

                if (visibleProducts.length === 0 && !hasNoProducts) {
                    card.style.display = 'none';
                } else {
                    card.style.display = 'block';
                }
            });
        }

        function clearFilters() {
            document.getElementById('search_products').value = '';
            document.getElementById('filter_status').value = '';
            filterProducts();
        }

        // Initialize filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            // You could add any initialization logic here
        });
    </script>
</body>

</html>