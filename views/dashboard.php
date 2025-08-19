<?php
// session_start();
require_once 'includes/auth.php';
require_once 'models/User.php';
require_once 'config/database.php';

// Check if user is logged in
requireLogin();

// Get current user data
$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$user = $userModel->getById($_SESSION['user_id']);
if (!$user) {
    header('Location: ../index.php?page=login');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gamersland Arena</title>
    <link rel="stylesheet" href="views/styles.css">
    <!-- <link rel="stylesheet" href="views/responsive.css"> -->
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <h1><i class="fa-solid fa-gamepad"></i> Gamersland Arena</h1>
            <div class="navbar-controls">
                <div class="user-profile-mini">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars(substr($user['profile_image'], 3)); ?>" alt="Avatar" class="user-avatar-mini" />
                    <?php else: ?>
                        <div class="user-avatar-mini">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <span class="user-greeting-mini">
                        ¡Hola, <?php echo htmlspecialchars($user['nickname']); ?>!
                    </span>
                </div>
                <button class="hamburger-menu" onclick="toggleMenu()" id="hamburgerBtn">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </div>

        <!-- Menú desplegable -->
        <div class="dropdown-menu" id="dropdownMenu">
            <div class="dropdown-content">
                <div class="user-profile-section">
                    <div class="user-avatar"><i class="fa-solid fa-gamepad"></i></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($user['nickname']); ?></div>
                    </div>
                </div>

                <div class="menu-divider"></div>

                <div class="menu-items">
                    <a href="views/notifications.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon"><i class="fa-solid fa-bell"></i></div>
                        <div class="menu-text">
                            <span>Notificaciones</span>
                            <span class="notification-count" id="menuNotificationCount" style="display: none;">0</span>
                        </div>
                    </a>

                    <a href="views/leaderboard.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon"><i class="fa-solid fa-trophy"></i></div>
                        <div class="menu-text">Ranking Global</div>
                    </a>

                    <a href="views/products_catalog.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon"><i class="fa-solid fa-gift"></i></div>
                        <div class="menu-text">Tienda de Premios</div>
                    </a>

                    <?php if (canAssignPoints()): ?>
                        <a href="views/assign_points.php" class="menu-item" onclick="closeMenu()">
                            <div class="menu-icon"><i class="fa-solid fa-star"></i></div>
                            <div class="menu-text">Otorgar Puntos</div>
                        </a>
                    <?php endif; ?>

                    <?php if (canManageStands()): ?>
                        <a href="views/stand_claims.php" class="menu-item" onclick="closeMenu()">
                            <div class="menu-icon"><i class="fa-solid fa-bullseye"></i></div>
                            <div class="menu-text">Centro de Control</div>
                        </a>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                        <a href="views/admin_dashboard.php" class="menu-item" onclick="closeMenu()">
                            <div class="menu-icon"><i class="fa-solid fa-crown"></i></div>
                            <div class="menu-text">Panel Admin</div>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="menu-divider"></div>

                <button class="menu-item logout-item" onclick="logout()">
                    <div class="menu-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
                    <div class="menu-text">Cerrar Sesión</div>
                </button>
            </div>
        </div>

        <!-- Overlay para cerrar el menú -->
        <div class="menu-overlay" id="menuOverlay" onclick="closeMenu()"></div>
    </nav>

    <div class="container">
        <!-- SECCIÓN DEL PODIO Y RANKING -->
        <div class="podium-section">
            <div class="podium-card">
                <div class="section-header">
                    <h2><i class="fa-solid fa-trophy"></i> Batalla por el Trono</h2>
                    <p class="section-description">Los retadores que luchan por ser el campeón de Gamersland Arena</p>
                </div>

                <!-- Podio del Top 3 Compacto -->
                <div class="podium-container-compact">
                    <div class="podium-loading" id="podiumLoading">
                        <div class="loading-spinner"></div>
                        <p>Cargando campeones...</p>
                    </div>

                    <div class="podium-compact" id="podiumContainer" style="display: none;">
                        <!-- Primer lugar - Centro -->
                        <div class="champion-card first" id="firstPlace">
                            <div class="champion-crown">
                                <i class="fa-solid fa-crown"></i>
                            </div>
                            <div class="champion-avatar">
                                <img src="" alt="" id="firstAvatar" style="display: none;">
                                <div class="avatar-placeholder" id="firstAvatarPlaceholder"></div>
                                <div class="position-badge gold">
                                    <i class="fa-solid fa-medal"></i>
                                </div>
                            </div>
                            <div class="champion-info">
                                <div class="champion-rank">#1</div>
                                <div class="champion-name" id="firstName">-</div>
                                <div class="champion-points" id="firstPoints">
                                    <i class="fa-solid fa-bolt"></i> 0 pts
                                </div>
                            </div>
                        </div>

                        <!-- Segundo y Tercer lugar - Lado a lado -->
                        <div class="runners-up">
                            <div class="champion-card second" id="secondPlace">
                                <div class="champion-avatar">
                                    <img src="" alt="" id="secondAvatar" style="display: none;">
                                    <div class="avatar-placeholder" id="secondAvatarPlaceholder"></div>
                                    <div class="position-badge silver">
                                        <i class="fa-solid fa-award"></i>
                                    </div>
                                </div>
                                <div class="champion-info">
                                    <div class="champion-rank">#2</div>
                                    <div class="champion-name" id="secondName">-</div>
                                    <div class="champion-points" id="secondPoints">
                                        <i class="fa-solid fa-bolt"></i> 0 pts
                                    </div>
                                </div>
                            </div>

                            <div class="champion-card third" id="thirdPlace">
                                <div class="champion-avatar">
                                    <img src="" alt="" id="thirdAvatar" style="display: none;">
                                    <div class="avatar-placeholder" id="thirdAvatarPlaceholder"></div>
                                    <div class="position-badge bronze">
                                        <i class="fa-solid fa-certificate"></i>
                                    </div>
                                </div>
                                <div class="champion-info">
                                    <div class="champion-rank">#3</div>
                                    <div class="champion-name" id="thirdName">-</div>
                                    <div class="champion-points" id="thirdPoints">
                                        <i class="fa-solid fa-bolt"></i> 0 pts
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Posición del usuario actual -->
                <div class="user-position" id="userPosition">
                    <div class="user-position-card">
                        <div class="user-position-info">
                            <div class="user-position-avatar">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars(substr($user['profile_image'], 3)); ?>" alt="Tu avatar">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?php echo strtoupper(substr($user['nickname'], 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="user-position-details">
                                <div class="user-position-name">Tu posición actual</div>
                                <div class="user-position-rank">
                                    <span class="rank-number" id="userRankNumber">#-</span>
                                    <span class="rank-name"><?php echo htmlspecialchars($user['nickname']); ?></span>
                                </div>
                                <div class="user-position-points" id="userTotalPoints">0 puntos</div>
                            </div>
                        </div>
                        <div class="user-position-action">
                            <a href="views/leaderboard.php" class="btn btn-ranking">
                                <i class="fa-solid fa-list"></i>
                                Ver Ranking Completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DEL CARRUSEL EXPLICATIVO -->
        <div class="carousel-section">
            <div class="carousel-card">
                <div class="section-header">
                    <h2><i class="fa-solid fa-gamepad"></i> ¿Cómo Funciona Gamersland Arena?</h2>
                    <p class="section-description">Descubre todas las formas de ganar puntos y convertirte en el próximo campeón</p>
                </div>

                <div class="carousel-container">
                    <div class="carousel-wrapper" id="carouselWrapper">
                        <!-- Slide 1: Desafíos -->
                        <div class="carousel-slide active" data-slide="0">
                            <div class="slide-image">
                                <div class="slide-icon challenges">
                                    <i class="fa-solid fa-swords"></i>
                                </div>
                            </div>
                            <div class="slide-content">
                                <h3>Participa en Desafíos</h3>
                                <p>Completa actividades temáticas y desafíos especiales durante los eventos. Cada desafío completado te otorga puntos automáticamente.</p>
                                <div class="slide-points">
                                    <i class="fa-solid fa-plus"></i> 10-100 puntos por desafío
                                </div>
                                <button class="btn btn-slide" onclick="goToSection('challenges')">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Explorar Desafíos
                                </button>
                            </div>
                        </div>

                        <!-- Slide 2: Puestos -->
                        <div class="carousel-slide" data-slide="1">
                            <div class="slide-image">
                                <div class="slide-icon stands">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                            </div>
                            <div class="slide-content">
                                <h3>Compra en Puestos</h3>
                                <p>Visita los puestos participantes del evento y realiza compras. Presenta tu código QR para recibir puntos por cada compra.</p>
                                <div class="slide-points">
                                    <i class="fa-solid fa-plus"></i> 5-50 puntos por compra
                                </div>
                                <button class="btn btn-slide" onclick="goToSection('stands')">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Ver Puestos
                                </button>
                            </div>
                        </div>

                        <!-- Slide 3: Torneos -->
                        <div class="carousel-slide" data-slide="2">
                            <div class="slide-image">
                                <div class="slide-icon tournaments">
                                    <i class="fa-solid fa-trophy"></i>
                                </div>
                            </div>
                            <div class="slide-content">
                                <h3>Compite en Torneos</h3>
                                <p>Únete a torneos gaming y compite contra otros jugadores. Gana puntos por participar y bonificaciones por ganar.</p>
                                <div class="slide-points">
                                    <i class="fa-solid fa-plus"></i> 50-500 puntos por torneo
                                </div>
                                <button class="btn btn-slide" onclick="goToSection('tournaments')">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Ver Torneos
                                </button>
                            </div>
                        </div>

                        <!-- Slide 4: Premios -->
                        <div class="carousel-slide" data-slide="3">
                            <div class="slide-image">
                                <div class="slide-icon rewards">
                                    <i class="fa-solid fa-gift"></i>
                                </div>
                            </div>
                            <div class="slide-content">
                                <h3>Reclama Premios</h3>
                                <p>Usa tus puntos acumulados para reclamar increíbles premios y recompensas. ¡Conviértete en el RETADOR GAMERLAND!</p>
                                <div class="slide-points">
                                    <i class="fa-solid fa-coins"></i> Canjea tus puntos
                                </div>
                                <button class="btn btn-slide" onclick="goToSection('rewards')">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Ver Premios
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Controles del carrusel -->
                    <div class="carousel-controls">
                        <button class="carousel-btn prev" onclick="previousSlide()">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <div class="carousel-indicators">
                            <span class="indicator active" data-slide="0" onclick="goToSlide(0)"></span>
                            <span class="indicator" data-slide="1" onclick="goToSlide(1)"></span>
                            <span class="indicator" data-slide="2" onclick="goToSlide(2)"></span>
                            <span class="indicator" data-slide="3" onclick="goToSlide(3)"></span>
                        </div>
                        <button class="carousel-btn next" onclick="nextSlide()">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCIONES RÁPIDAS -->
        <div class="quick-actions-section">
            <div class="quick-actions-card">
                <div class="section-header">
                    <h2><i class="fa-solid fa-bolt"></i> Acciones Rápidas</h2>
                    <p class="section-description">Acceso directo a las funciones principales</p>
                </div>

                <div class="quick-actions-grid">
                    <a href="views/notifications.php" class="quick-action-item">
                        <div class="quick-action-icon"><i class="fa-solid fa-bell"></i></div>
                        <div class="quick-action-text">Notificaciones</div>
                    </a>

                    <?php if (canAssignPoints()): ?>
                        <a href="views/assign_points.php" class="quick-action-item">
                            <div class="quick-action-icon"><i class="fa-solid fa-star"></i></div>
                            <div class="quick-action-text">Otorgar Puntos</div>
                        </a>
                    <?php endif; ?>

                    <?php if (canManageStands()): ?>
                        <a href="views/stand_claims.php" class="quick-action-item">
                            <div class="quick-action-icon"><i class="fa-solid fa-bullseye"></i></div>
                            <div class="quick-action-text">Centro de Control</div>
                        </a>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                        <a href="views/admin_dashboard.php" class="quick-action-item">
                            <div class="quick-action-icon"><i class="fa-solid fa-crown"></i></div>
                            <div class="quick-action-text">Panel Admin</div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>


    </div>

    <script>
        // Funciones del menú hamburguesa
        function toggleMenu() {
            const menu = document.getElementById('dropdownMenu');
            const overlay = document.getElementById('menuOverlay');
            const hamburger = document.getElementById('hamburgerBtn');

            menu.classList.toggle('active');
            overlay.classList.toggle('active');
            hamburger.classList.toggle('active');

            // Prevenir scroll del body cuando el menú esté abierto
            if (menu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }

        function closeMenu() {
            const menu = document.getElementById('dropdownMenu');
            const overlay = document.getElementById('menuOverlay');
            const hamburger = document.getElementById('hamburgerBtn');

            menu.classList.remove('active');
            overlay.classList.remove('active');
            hamburger.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Cerrar menú con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });

        // Variables del carrusel
        let currentSlide = 0;
        const totalSlides = 4;
        let carouselInterval;

        // Funciones del carrusel
        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateCarousel();
        }

        function previousSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateCarousel();
        }

        function goToSlide(slideIndex) {
            currentSlide = slideIndex;
            updateCarousel();
        }

        function updateCarousel() {
            const slides = document.querySelectorAll('.carousel-slide');
            const indicators = document.querySelectorAll('.indicator');

            slides.forEach((slide, index) => {
                slide.classList.toggle('active', index === currentSlide);
            });

            indicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index === currentSlide);
            });
        }

        function startCarouselAutoplay() {
            carouselInterval = setInterval(nextSlide, 5000); // Cambiar cada 5 segundos
        }

        function stopCarouselAutoplay() {
            if (carouselInterval) {
                clearInterval(carouselInterval);
            }
        }

        // Funciones de navegación
        function goToSection(section) {
            const routes = {
                'challenges': 'views/notifications.php', // O la página de desafíos
                'stands': 'views/stand_claims.php',
                'tournaments': 'views/leaderboard.php', // O página de torneos
                'rewards': 'views/products_catalog.php'
            };

            if (routes[section]) {
                window.location.href = routes[section];
            }
        }

        // Load podium data
        async function loadPodiumData() {
            try {
                const response = await fetch('../api/leaderboard.php?action=top&limit=3');
                const result = await response.json();

                const podiumLoading = document.getElementById('podiumLoading');
                const podiumContainer = document.getElementById('podiumContainer');

                if (result.success && result.data && result.data.length > 0) {
                    const topUsers = result.data;
                    
                    // Animar salida si ya hay contenido
                    if (podiumContainer.style.display !== 'none') {
                        podiumContainer.style.opacity = '0';
                        podiumContainer.style.transform = 'translateY(20px)';
                        await new Promise(resolve => setTimeout(resolve, 300));
                    }
                    
                    // Llenar el podio con nueva estructura
                    if (topUsers[0]) {
                        updateChampionData('first', topUsers[0], 1);
                    }
                    
                    if (topUsers[1]) {
                        updateChampionData('second', topUsers[1], 2);
                    }
                    
                    if (topUsers[2]) {
                        updateChampionData('third', topUsers[2], 3);
                    }
                    
                    // Mostrar con animación
                    podiumLoading.style.display = 'none';
                    podiumContainer.style.display = 'block';
                    
                    // Animar entrada
                    setTimeout(() => {
                        podiumContainer.style.opacity = '1';
                        podiumContainer.style.transform = 'translateY(0)';
                        
                        // Animar cada campeón individualmente
                        const champions = podiumContainer.querySelectorAll('.champion-card');
                        champions.forEach((champion, index) => {
                            setTimeout(() => {
                                champion.classList.add('animate-in');
                            }, index * 150);
                        });
                    }, 100);
                    
                } else {
                    podiumLoading.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-trophy"></i></div>
                            <div class="empty-title">Podio en construcción</div>
                            <div class="empty-desc">¡Sé el primero en aparecer aquí!</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading podium data:', error);
                document.getElementById('podiumLoading').innerHTML = `
                    <div class="error-message">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        Error al cargar el podio
                    </div>
                `;
            }
        }

        // Update champion data helper function
        function updateChampionData(position, userData, rank) {
            const nameElement = document.getElementById(`${position}Name`);
            const pointsElement = document.getElementById(`${position}Points`);
            const avatarElement = document.getElementById(`${position}Avatar`);
            const placeholderElement = document.getElementById(`${position}AvatarPlaceholder`);
            
            if (nameElement) nameElement.textContent = userData.nickname;
            if (pointsElement) pointsElement.innerHTML = `<i class="fa-solid fa-bolt"></i> ${userData.total_points} pts`;
            
            if (userData.profile_image && avatarElement) {
                avatarElement.src = userData.profile_image;
                avatarElement.style.display = 'block';
                if (placeholderElement) placeholderElement.style.display = 'none';
            } else if (placeholderElement) {
                placeholderElement.textContent = userData.nickname.charAt(0).toUpperCase();
                placeholderElement.style.display = 'flex';
                if (avatarElement) avatarElement.style.display = 'none';
            }
        }

        // Load user data and stats
        async function loadDashboardData() {
            try {
                const response = await fetch('../api/users.php?action=profile');
                const result = await response.json();

                if (result.success) {
                    const points = result.user.total_points || 0;
                    document.getElementById('userTotalPoints').textContent = `${points} puntos`;

                    // Load user rank
                    await loadUserRank(result.user.id);
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Load user's rank in leaderboard
        async function loadUserRank(userId) {
            try {
                const response = await fetch(`../api/leaderboard.php?action=user_rank&user_id=${userId}`);
                const result = await response.json();

                if (result.success && result.data) {
                    document.getElementById('userRankNumber').textContent = `#${result.data.rank}`;
                } else {
                    document.getElementById('userRankNumber').textContent = '#-';
                }
            } catch (error) {
                console.error('Error loading user rank:', error);
                document.getElementById('userRankNumber').textContent = '#-';
            }
        }

        // Load active events
        async function loadActiveEvents() {
            try {
                const response = await fetch('../api/events.php?action=active');
                const result = await response.json();

                const list = document.getElementById('activeEventsList');

                if (result.success && result.events && result.events.length > 0) {
                    list.innerHTML = result.events.map(event => `
                        <div class="event-card">
                            <div class="event-header">
                                <div class="event-name">${escapeHtml(event.name)}</div>
                                <div class="event-status active">Activo</div>
                            </div>
                            <div class="event-description">${escapeHtml(event.description || 'Participa en este emocionante desafío')}</div>
                            <div class="event-dates">
                                📅 ${formatDate(event.start_date)} - ${formatDate(event.end_date)}
                            </div>
                            <div class="event-reward">🎯 Recompensa por completar</div>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-swords"></i></div>
                            <div class="empty-title">No hay desafíos activos</div>
                            <div class="empty-desc">¡Pronto habrá nuevos desafíos disponibles!</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading active events:', error);
                document.getElementById('activeEventsList').innerHTML = `
                    <div class="error-message">Error al cargar desafíos</div>
                `;
            }
        }

        // Load upcoming tournaments
        async function loadUpcomingTournaments() {
            try {
                const response = await fetch('../api/tournaments.php?action=upcoming&limit=5');
                const result = await response.json();

                const list = document.getElementById('upcomingTournamentsList');
                const activeTournamentsElement = document.getElementById('activeTournaments');

                if (result.success && result.tournaments && result.tournaments.length > 0) {
                    list.innerHTML = result.tournaments.map(tournament => `
                        <div class="tournament-card">
                            <div class="tournament-header">
                                <div class="tournament-name">${escapeHtml(tournament.name)}</div>
                                <div class="tournament-points">${tournament.points_reward} pts</div>
                            </div>
                            <div class="tournament-time">
                                📅 ${formatDateTime(tournament.scheduled_time)}
                            </div>
                            <div class="tournament-participants">
                                👥 Participantes: ${tournament.participants_count || 0}
                            </div>
                        </div>
                    `).join('');
                    activeTournamentsElement.textContent = result.tournaments.length;
                } else {
                    list.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-trophy"></i></div>
                            <div class="empty-title">No hay torneos programados</div>
                            <div class="empty-desc">¡Estate atento a futuros torneos épicos!</div>
                        </div>
                    `;
                    activeTournamentsElement.textContent = '0';
                }
            } catch (error) {
                console.error('Error loading upcoming tournaments:', error);
                document.getElementById('activeTournaments').textContent = '0';
                document.getElementById('upcomingTournamentsList').innerHTML = `
                    <div class="error-message">Error al cargar torneos</div>
                `;
            }
        }

        // Load available products count
        async function loadAvailableProducts() {
            try {
                // Get current user ID first
                const userResponse = await fetch('../api/users.php?action=profile');
                const userResult = await userResponse.json();

                if (!userResult.success) return;

                // This would need a products API endpoint, for now we'll simulate
                // In a real implementation, you'd call something like:
                // const response = await fetch(`../api/products.php?action=available&user_id=${userResult.user.id}`);

                // For now, set a placeholder
                document.getElementById('availableProducts').textContent = '0';
            } catch (error) {
                console.error('Error loading available products:', error);
                document.getElementById('availableProducts').textContent = '0';
            }
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Calculate user level based on points
        function calculateUserLevel(points) {
            if (points < 100) return 1;
            if (points < 500) return 2;
            if (points < 1000) return 3;
            if (points < 2500) return 4;
            if (points < 5000) return 5;
            return Math.floor(points / 1000) + 1;
        }

        // Load available products count
        async function loadAvailableProducts() {
            try {
                // Obtener el usuario actual
                const userResponse = await fetch('../api/users.php?action=profile');
                const userResult = await userResponse.json();
                if (!userResult.success) return;

                // Consultar productos disponibles para reclamar
                const response = await fetch(`../api/products.php?action=claimable_count&user_id=${userResult.user.id}`);
                const result = await response.json();

                if (result.success && typeof result.count !== 'undefined') {
                    document.getElementById('availableProducts').textContent = result.count;
                } else {
                    document.getElementById('availableProducts').textContent = '0';
                }
            } catch (error) {
                console.error('Error loading available products:', error);
                document.getElementById('availableProducts').textContent = '0';
            }
        }

        // Load notification count
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?action=unread-count');
                const result = await response.json();

                if (result.success) {
                    const menuCount = document.getElementById('menuNotificationCount');
                    const count = result.unread_count;

                    if (count > 0) {
                        const displayCount = count > 99 ? '99+' : count;
                        menuCount.textContent = displayCount;
                        menuCount.style.display = 'inline-block';
                    } else {
                        menuCount.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading notification count:', error);
            }
        }

        // Navigate to notifications
        function goToNotifications() {
            window.location.href = 'views/notifications.php';
        }

        // Logout function
        async function logout() {
            try {
                const response = await fetch('../api/users.php?action=logout', {
                    method: 'POST'
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = '../index.php?page=login';
                } else {
                    alert('Error al cerrar sesión');
                }
            } catch (error) {
                console.error('Error during logout:', error);
                // Force redirect even if there's an error
                window.location.href = '../index.php?page=login';
            }
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboardData();
            loadNotificationCount();
            loadPodiumData();
            startCarouselAutoplay();

            // Pausar autoplay cuando el usuario interactúa con el carrusel
            const carouselContainer = document.querySelector('.carousel-container');
            if (carouselContainer) {
                carouselContainer.addEventListener('mouseenter', stopCarouselAutoplay);
                carouselContainer.addEventListener('mouseleave', startCarouselAutoplay);
            }

            // Refresh data periodically
            setInterval(() => {
                loadNotificationCount();
                loadPodiumData();
            }, 60000); // Every minute
        });
    </script>
</body>

</html>