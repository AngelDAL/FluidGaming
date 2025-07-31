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
    <title>🎁 Tienda de Premios - FluidGaming Arena</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Estilos específicos para el catálogo de productos */
        .products-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .products-header {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .products-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .products-header:hover::before {
            left: 100%;
        }

        .products-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .products-header p {
            color: #94a3b8;
            font-size: 1.2rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .user-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 2rem;
            position: relative;
            z-index: 1;
        }

        .user-points-display {
            background: rgba(102, 126, 234, 0.15);
            border: 1px solid rgba(102, 126, 234, 0.3);
            padding: 1rem 2rem;
            border-radius: 16px;
            color: #e2e8f0;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .event-selector-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .event-selector-card h3 {
            color: #e2e8f0;
            margin-bottom: 1rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-select {
            width: 100%;
            max-width: 500px;
            padding: 1rem;
            background: rgba(15, 15, 35, 0.8);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            color: #e2e8f0;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .event-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .info-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-left: 4px solid #22c55e;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .info-card h4 {
            color: #22c55e;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card ul {
            color: #94a3b8;
            margin-left: 1.5rem;
            line-height: 1.8;
        }

        .info-card li {
            margin-bottom: 0.5rem;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 0 0.25rem;
        }

        .status-can-claim {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-insufficient {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .status-claimed {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card-mini {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card-mini:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .filter-section {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
        }

        .search-input, .filter-select {
            padding: 1rem;
            background: rgba(15, 15, 35, 0.8);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            color: #e2e8f0;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus, .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .clear-filters-btn {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .clear-filters-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(100, 116, 139, 0.4);
        }

        .stands-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .stand-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .stand-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
        }

        .stand-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .stand-header h3 {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stand-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
        }

        .products-grid {
            padding: 2rem;
        }

        .product-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 16px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            background: rgba(15, 15, 35, 0.3);
        }

        .product-card:hover {
            border-color: #667eea;
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }

        .product-card.can-claim {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
        }

        .product-card.insufficient {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .product-card.claimed {
            border-color: #fbbf24;
            background: rgba(251, 191, 36, 0.05);
            opacity: 0.7;
        }

        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background: rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            flex-shrink: 0;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            color: #e2e8f0;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .product-points {
            color: #667eea;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .product-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .claim-ready-badge {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }

        .empty-state h3 {
            color: #e2e8f0;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .no-products {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        .no-products h4 {
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .products-container {
                padding: 1rem;
            }

            .user-stats {
                flex-direction: column;
                text-align: center;
            }

            .filter-controls {
                grid-template-columns: 1fr;
            }

            .stands-container {
                grid-template-columns: 1fr;
            }

            .product-card {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>
    <div class="products-container">
        <div class="products-header">
            <h1>🎁 Tienda de Premios</h1>
            <p>Descubre qué puedes canjear con tus puntos de poder</p>
            
            <div class="user-stats">
                <div class="user-points-display">
                    ⚡ <?php echo number_format($current_user['total_points']); ?> Puntos de Poder
                </div>
                <a href="../" class="back-button">
                    ← Volver al Arena
                </a>
            </div>
        </div>
            <?php if (empty($activeEvents)): ?>
                <div class="empty-state">
                    <h3>⚔️ No hay eventos activos</h3>
                    <p>Actualmente no hay batallas en curso. Los premios estarán disponibles cuando haya eventos activos.</p>
                </div>
            <?php else: ?>
                <div class="event-selector-card">
                    <h3>🏟️ Seleccionar Arena de Batalla</h3>
                    <select class="event-select" onchange="changeEvent(this.value)">
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

                <div class="info-card">
                    <h4>ℹ️ Cómo funciona el canje de premios</h4>
                    <ul>
                        <li><strong>Explora los premios:</strong> Cada stand tiene productos únicos esperándote</li>
                        <li><strong>Verifica tus puntos:</strong> 
                            <span class="status-badge status-can-claim">Puedes Reclamar</span> - Tienes suficientes puntos |
                            <span class="status-badge status-insufficient">Puntos Insuficientes</span> - Necesitas más batallas |
                            <span class="status-badge status-claimed">Ya Reclamado</span> - Solo uno por jugador
                        </li>
                        <li><strong>Para reclamar:</strong> Ve al stand correspondiente y muestra tu perfil gaming al encargado</li>
                    </ul>
                </div>

                <?php if (empty($stands)): ?>
                    <div class="empty-state">
                        <h3>🏪 No hay stands en esta batalla</h3>
                        <p>Esta arena aún no tiene stands configurados con premios.</p>
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

                    <div class="stats-grid">
                        <div class="stat-card-mini">
                            <div class="stat-number"><?php echo $totalProducts; ?></div>
                            <div class="stat-label">Total Premios</div>
                        </div>
                        <div class="stat-card-mini">
                            <div class="stat-number"><?php echo $availableProducts; ?></div>
                            <div class="stat-label">Disponibles</div>
                        </div>
                        <div class="stat-card-mini">
                            <div class="stat-number"><?php echo $affordableProducts; ?></div>
                            <div class="stat-label">Puedes Obtener</div>
                        </div>
                        <div class="stat-card-mini">
                            <div class="stat-number"><?php echo $claimedProducts; ?></div>
                            <div class="stat-label">Ya Reclamados</div>
                        </div>
                    </div>

                    <div class="filter-section">
                        <div class="filter-controls">
                            <input type="text" id="search_products" class="search-input" placeholder="🔍 Buscar premios..." oninput="filterProducts()">
                            <select id="filter_status" class="filter-select" onchange="filterProducts()">
                                <option value="">Todos los estados</option>
                                <option value="available">Disponibles (todos)</option>
                                <option value="affordable">Puedo obtener ahora</option>
                                <option value="insufficient">Puntos insuficientes</option>
                                <option value="claimed">Ya reclamados por mí</option>
                            </select>
                            <button class="clear-filters-btn" onclick="clearFilters()">Limpiar</button>
                        </div>
                    </div>

                    <div class="stands-container">
                        <?php foreach ($stands as $standInfo): ?>
                            <div class="stand-card" data-stand-id="<?php echo $standInfo['id']; ?>">
                                <div class="stand-header">
                                    <h3>🏪 <?php echo htmlspecialchars($standInfo['name']); ?></h3>
                                    <p>Encargado: <?php echo htmlspecialchars($standInfo['manager_name'] ?? 'Sin asignar'); ?></p>
                                </div>

                                <div class="products-grid">
                                    <?php if (empty($standProducts[$standInfo['id']])): ?>
                                        <div class="no-products">
                                            <h4>📦 Sin premios</h4>
                                            <p>Este stand aún no tiene premios disponibles.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($standProducts[$standInfo['id']] as $prod): ?>
                                            <?php
                                            $isClaimed = !empty($prod['claim_id']);
                                            $canAfford = $current_user['total_points'] >= $prod['points_required'];
                                            $cardClass = $isClaimed ? 'claimed' : ($canAfford ? 'can-claim' : 'insufficient');
                                            $statusText = $isClaimed ? 'Ya Reclamado' : ($canAfford ? 'Puedes Reclamar' : 'Puntos Insuficientes');
                                            $statusClass = $isClaimed ? 'status-claimed' : ($canAfford ? 'status-can-claim' : 'status-insufficient');
                                            ?>
                                            <div class="product-card <?php echo $cardClass; ?>"
                                                data-name="<?php echo strtolower($prod['name']); ?>"
                                                data-status="<?php echo $isClaimed ? 'claimed' : ($canAfford ? 'affordable' : 'insufficient'); ?>"
                                                data-points="<?php echo $prod['points_required']; ?>">

                                                <div class="product-image">
                                                    <?php if ($prod['image_url']): ?>
                                                        <img src="../<?php echo htmlspecialchars($prod['image_url']); ?>"
                                                            alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                                                    <?php else: ?>
                                                        🎁
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
                                                        ⚡ <?php echo number_format($prod['points_required']); ?> puntos
                                                    </div>
                                                </div>

                                                <div class="product-status <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </div>

                                                <?php if (!$isClaimed && $canAfford): ?>
                                                    <div class="claim-ready-badge">
                                                        ✓ Listo para obtener
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

    <script>
        function changeEvent(eventId) {
            window.location.href = '?event_id=' + eventId;
        }

        function filterProducts() {
            const searchTerm = document.getElementById('search_products').value.toLowerCase();
            const statusFilter = document.getElementById('filter_status').value;

            const productCards = document.querySelectorAll('.product-card');
            const standCards = document.querySelectorAll('.stand-card');

            productCards.forEach(card => {
                const name = card.dataset.name;
                const status = card.dataset.status;

                let showCard = true;

                // Apply search filter
                if (searchTerm && !name.includes(searchTerm)) {
                    showCard = false;
                }

                // Apply status filter
                if (statusFilter) {
                    if (statusFilter === 'available' && status === 'claimed') {
                        showCard = false;
                    } else if (statusFilter === 'affordable' && status !== 'affordable') {
                        showCard = false;
                    } else if (statusFilter === 'insufficient' && status !== 'insufficient') {
                        showCard = false;
                    } else if (statusFilter === 'claimed' && status !== 'claimed') {
                        showCard = false;
                    }
                }

                card.style.display = showCard ? 'flex' : 'none';
            });

            // Hide stands that have no visible products
            standCards.forEach(card => {
                const visibleProducts = card.querySelectorAll('.product-card[style="display: flex"], .product-card:not([style*="display: none"])');
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