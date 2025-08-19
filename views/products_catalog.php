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
    <title> Reclama Premios - Gamersland Arena</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="claimRewards.css">
</head>

<body>
    <div class="products-container" style="max-width: 1100px; margin: 0 auto;">
        <!-- Navbar superior -->
        <nav class="navbar" style="padding: 0.7rem 0; margin-bottom: 2rem;">
            <div class="container" style="display: flex; align-items: center; gap: 1.2rem;">
                <a href="../" class="btn dark" style="display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; min-width: 120px; justify-content: center;">
                    <i class="fa-solid fa-arrow-left"></i> Regresar
                </a>
            </div>
        </nav>

        <div class="products-header">
            <h1><i class="fa-solid fa-gift"></i> Tienda de Premios</h1>
            <p>Descubre qué puedes canjear con tus puntos de poder</p>
            <div class="user-stats">
                <div class="user-points-display">
                    <i class="fa-solid fa-bolt"></i> <?php echo number_format($current_user['total_points']); ?> Puntos
                </div>
            </div>
        </div>

        <?php
        // Mostrar premios que el usuario puede reclamar
        $claimableProducts = [];
        foreach ($standProducts as $products) {
            foreach ($products as $prod) {
                $isClaimed = !empty($prod['claim_id']);
                $canAfford = $current_user['total_points'] >= $prod['points_required'];
                if (!$isClaimed && $canAfford) {
                    $claimableProducts[] = $prod;
                }
            }
        }
        ?>
        <?php if (!empty($claimableProducts)): ?>
        <div class="section-card" style="margin-bottom:2.5rem;">
            <h3 style="margin-bottom:1rem;"><i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Premios que puedes reclamar ahora</h3>
            <div class="rewards-preview" style="flex-wrap:wrap; gap:1.5rem; justify-content:flex-start;">
                <?php foreach ($claimableProducts as $prod): ?>
                <div class="preview-card" style="min-width:260px;">
                    <div class="preview-icon"><i class="fa-solid fa-gift"></i></div>
                    <div>
                        <div class="preview-title"><?php echo htmlspecialchars($prod['name']); ?></div>
                        <div class="preview-desc" style="margin-bottom:0.5rem; color:#94a3b8;">
                            <?php echo htmlspecialchars($prod['description']); ?>
                        </div>
                        <div style="color:#22c55e; font-weight:600;"><i class="fa-solid fa-bolt"></i> <?php echo number_format($prod['points_required']); ?> pts</div>
                        <div style="font-size:0.95rem; color:#94a3b8;">Stand: <?php echo htmlspecialchars($prod['stand_name']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
            <?php if (empty($activeEvents)): ?>
                <div class="empty-state">
                    <h3><i class="fa-solid fa-swords"></i> No hay eventos activos</h3>
                    <p>Actualmente no hay batallas en curso. Los premios estarán disponibles cuando haya eventos activos.</p>
                </div>
            <?php else: ?>
                <div class="event-selector-card" style="display: none;">
                    <h3><i class="fa-solid fa-trophy"></i> Seleccionar Arena de Batalla</h3>
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

                                <div class="info-card" style="margin-bottom:2.5rem;">
                                        <h4><i class="fa-solid fa-circle-info"></i> ¿Cómo reclamar un premio?</h4>
                                        <div id="claim-steps-carousel" style="display: flex; align-items: center; justify-content: center; flex-wrap: wrap;">
                                            <div id="stepContent" style="flex:1; min-width:220px; max-width:350px; text-align:center; touch-action: pan-y;"></div>
                                        </div>
                                        <div style="text-align:center; margin-top:1rem;">
                                            <span id="stepIndicators"></span>
                                        </div>
                                </div>
                                <script>
                                                // Pasos del carrusel
                                                const claimSteps = [
                                                    {
                                                        icon: '<i class="fa-solid fa-gift fa-2x" style="color:#667eea;"></i>',
                                                        title: '1. Explora los premios',
                                                        desc: 'Revisa los stands y descubre los premios disponibles para ti.'
                                                    },
                                                    {
                                                        icon: '<i class="fa-solid fa-bolt fa-2x" style="color:#fbbf24;"></i>',
                                                        title: '2. Verifica tus puntos',
                                                        desc: 'Asegúrate de tener suficientes puntos para reclamar el premio que te interesa.'
                                                    },
                                                    {
                                                        icon: '<i class="fa-solid fa-check-circle fa-2x" style="color:#22c55e;"></i>',
                                                        title: '3. Elige tu premio',
                                                        desc: 'Selecciona el premio que puedes reclamar y acércate al stand correspondiente.'
                                                    },
                                                    {
                                                        icon: '<i class="fa-solid fa-id-card fa-2x" style="color:#667eea;"></i>',
                                                        title: '4. Muestra tu perfil',
                                                        desc: 'Presenta tu perfil de jugador al encargado del stand para validar tu identidad.'
                                                    },
                                                    {
                                                        icon: '<i class="fa-solid fa-hand-holding-heart fa-2x" style="color:#2ed573;"></i>',
                                                        title: '5. ¡Recibe tu premio!',
                                                        desc: 'El encargado validará tu canje y podrás disfrutar de tu recompensa.'
                                                    }
                                                ];
                                                let currentStep = 0;
                                                let autoScrollInterval = null;
                                                let isDragging = false;
                                                let dragStartX = 0;
                                                let dragDelta = 0;

                                                function renderStep() {
                                                    const step = claimSteps[currentStep];
                                                    document.getElementById('stepContent').innerHTML = `
                                                        <div style=\"margin-bottom:0.7rem;\">${step.icon}</div>
                                                        <div style=\"font-weight:700; font-size:1.1rem; margin-bottom:0.3rem;\">${step.title}</div>
                                                        <div style=\"color:#94a3b8; font-size:0.98rem;\">${step.desc}</div>
                                                    `;
                                                    // Indicadores
                                                    let indicators = '';
                                                    for(let i=0; i<claimSteps.length; i++) {
                                                        indicators += `<span class=\"carousel-indicator\" data-step=\"${i}\" style=\"display:inline-block;width:10px;height:10px;margin:0 3px;border-radius:50%;background:${i===currentStep?'#667eea':'#94a3b8'};cursor:pointer;transition:background 0.2s;\"></span>`;
                                                    }
                                                    document.getElementById('stepIndicators').innerHTML = indicators;
                                                    // Asignar click a los indicadores
                                                    document.querySelectorAll('.carousel-indicator').forEach(el => {
                                                        el.onclick = function() {
                                                            currentStep = parseInt(this.dataset.step);
                                                            renderStep();
                                                            restartAutoScroll();
                                                        };
                                                    });
                                                }

                                                function nextStep() {
                                                    currentStep = (currentStep + 1) % claimSteps.length;
                                                    renderStep();
                                                }

                                                function prevStep() {
                                                    currentStep = (currentStep - 1 + claimSteps.length) % claimSteps.length;
                                                    renderStep();
                                                }

                                                function startAutoScroll() {
                                                    if (autoScrollInterval) clearInterval(autoScrollInterval);
                                                    autoScrollInterval = setInterval(() => {
                                                        nextStep();
                                                    }, 3500);
                                                }

                                                function restartAutoScroll() {
                                                    startAutoScroll();
                                                }

                                                // Drag support
                                                const stepContent = document.getElementById('stepContent');
                                                stepContent.addEventListener('touchstart', e => {
                                                    isDragging = true;
                                                    dragStartX = e.touches[0].clientX;
                                                });
                                                stepContent.addEventListener('touchmove', e => {
                                                    if (!isDragging) return;
                                                    dragDelta = e.touches[0].clientX - dragStartX;
                                                });
                                                stepContent.addEventListener('touchend', e => {
                                                    if (!isDragging) return;
                                                    if (dragDelta > 40) {
                                                        prevStep();
                                                        restartAutoScroll();
                                                    } else if (dragDelta < -40) {
                                                        nextStep();
                                                        restartAutoScroll();
                                                    }
                                                    isDragging = false;
                                                    dragDelta = 0;
                                                });

                                                // Mouse drag (desktop)
                                                let mouseDown = false;
                                                let mouseStartX = 0;
                                                let mouseDelta = 0;
                                                stepContent.addEventListener('mousedown', e => {
                                                    mouseDown = true;
                                                    mouseStartX = e.clientX;
                                                });
                                                stepContent.addEventListener('mousemove', e => {
                                                    if (!mouseDown) return;
                                                    mouseDelta = e.clientX - mouseStartX;
                                                });
                                                stepContent.addEventListener('mouseup', e => {
                                                    if (!mouseDown) return;
                                                    if (mouseDelta > 40) {
                                                        prevStep();
                                                        restartAutoScroll();
                                                    } else if (mouseDelta < -40) {
                                                        nextStep();
                                                        restartAutoScroll();
                                                    }
                                                    mouseDown = false;
                                                    mouseDelta = 0;
                                                });
                                                stepContent.addEventListener('mouseleave', e => { mouseDown = false; mouseDelta = 0; });

                                                document.addEventListener('DOMContentLoaded', () => {
                                                    renderStep();
                                                    startAutoScroll();
                                                });
                                </script>

                <?php if (empty($stands)): ?>
                    <div class="empty-state">
                        <h3><i class="fa-solid fa-store"></i> No hay stands en esta batalla</h3>
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

                    <div class="filter-section" style="display: none;">
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
                                            <h4><i class="fa-solid fa-box"></i> Sin premios</h4>
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
                                                        <i class="fa-solid fa-gift"></i>
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
                                                        <i class="fa-solid fa-bolt"></i> <?php echo number_format($prod['points_required']); ?> puntos
                                                    </div>
                                                </div>

                                                <div class="product-status <?php echo $statusClass; ?>">
                                                    <?php if ($isClaimed): ?>
                                                        <i class="fa-solid fa-gift"></i> Ya Reclamado
                                                    <?php elseif ($canAfford): ?>
                                                        <i class="fa-solid fa-check"></i> Puedes Reclamar
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-xmark"></i> Puntos Insuficientes
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!$isClaimed && $canAfford): ?>
                                                    <div class="claim-ready-badge">
                                                        <i class="fa-solid fa-circle-check"></i> Listo para obtener
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