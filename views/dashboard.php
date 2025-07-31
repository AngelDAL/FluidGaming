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
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <h1>🎮 FluidGaming Arena</h1>
            <div class="navbar-controls">
                <div class="user-greeting-mini">
                    ¡Hola, <?php echo htmlspecialchars($user['nickname']); ?>!
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
                    <div class="user-avatar">🎮</div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($user['nickname']); ?></div>
                        <div class="user-level">Nivel <span id="userLevel">1</span></div>
                        <div class="user-xp">XP: <span id="currentXP">0</span> / <span id="nextLevelXP">100</span></div>
                    </div>
                </div>
                
                <div class="menu-divider"></div>
                
                <div class="menu-items">
                    <a href="views/notifications.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon">🔔</div>
                        <div class="menu-text">
                            <span>Notificaciones</span>
                            <span class="notification-count" id="menuNotificationCount" style="display: none;">0</span>
                        </div>
                    </a>
                    
                    <a href="views/leaderboard.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon">🏆</div>
                        <div class="menu-text">Ranking Global</div>
                    </a>
                    
                    <a href="views/products_catalog.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon">🎁</div>
                        <div class="menu-text">Tienda de Premios</div>
                    </a>
                    
                    <?php if (canAssignPoints()): ?>
                    <a href="views/assign_points.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon">⭐</div>
                        <div class="menu-text">Otorgar Puntos</div>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (canManageStands()): ?>
                    <a href="views/stand_claims.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon">🎯</div>
                        <div class="menu-text">Centro de Control</div>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                    <a href="views/admin_dashboard.php" class="menu-item" onclick="closeMenu()">
                        <div class="menu-icon">👑</div>
                        <div class="menu-text">Panel Admin</div>
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="menu-divider"></div>
                
                <button class="menu-item logout-item" onclick="logout()">
                    <div class="menu-icon">🚪</div>
                    <div class="menu-text">Cerrar Sesión</div>
                </button>
            </div>
        </div>
        
        <!-- Overlay para cerrar el menú -->
        <div class="menu-overlay" id="menuOverlay" onclick="closeMenu()"></div>
    </nav>

    <div class="container">
        <!-- PRIMER PLANO: Estadísticas principales del usuario -->
        <div class="main-stats-section">
            <div class="user-main-card">
                <div class="user-avatar-large">�</div>
                <div class="user-main-info">
                    <h2><?php echo htmlspecialchars($user['nickname']); ?></h2>
                    <div class="user-level-display">Nivel <span id="userLevel">1</span></div>
                    <div class="progress-section">
                        <div class="xp-bar">
                            <div class="xp-fill" id="xpBar" style="width: 0%"></div>
                        </div>
                        <div class="xp-text">XP: <span id="currentXP">0</span> / <span id="nextLevelXP">100</span></div>
                    </div>
                </div>
            </div>
            
            <div class="primary-stats">
                <div class="primary-stat-card points">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-content">
                        <div class="stat-number" id="userPoints">0</div>
                        <div class="stat-label">Puntos de Poder</div>
                    </div>
                </div>
                <div class="primary-stat-card ranking">
                    <div class="stat-icon">🥇</div>
                    <div class="stat-content">
                        <div class="stat-number" id="userRank">-</div>
                        <div class="stat-label">Posición Global</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEGUNDO PLANO: Navegación por pestañas -->
        <div class="dashboard-tabs">
            <div class="tab-nav">
                <button class="tab-btn active" onclick="showTab('challenges')">⚔️ Desafíos</button>
                <button class="tab-btn" onclick="showTab('tournaments')">🏟️ Torneos</button>
                <button class="tab-btn" onclick="showTab('rewards')">🎁 Recompensas</button>
                <button class="tab-btn" onclick="showTab('actions')">⚡ Acciones</button>
            </div>
            
            <!-- Tab: Desafíos Activos -->
            <div class="tab-content active" id="challenges-tab">
                <div class="tab-header">
                    <h3>⚔️ Desafíos Activos</h3>
                    <p>Completa estos desafíos para ganar puntos y experiencia</p>
                </div>
                <div id="activeEventsList" class="events-grid">
                    <div class="loading-message">Cargando desafíos...</div>
                </div>
            </div>
            
            <!-- Tab: Torneos -->
            <div class="tab-content" id="tournaments-tab">
                <div class="tab-header">
                    <h3>🏟️ Arena de Batalla</h3>
                    <p>Únete a torneos épicos y compite por grandes recompensas</p>
                </div>
                <div class="secondary-stats">
                    <div class="secondary-stat-card">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-number" id="activeTournaments">0</div>
                        <div class="stat-label">Torneos Activos</div>
                    </div>
                </div>
                <div id="upcomingTournamentsList" class="tournaments-grid">
                    <div class="loading-message">Cargando torneos...</div>
                </div>
            </div>
            
            <!-- Tab: Recompensas -->
            <div class="tab-content" id="rewards-tab">
                <div class="tab-header">
                    <h3>🎁 Tienda de Premios</h3>
                    <p>Canjea tus puntos por increíbles recompensas</p>
                </div>
                <div class="secondary-stats">
                    <div class="secondary-stat-card">
                        <div class="stat-icon">🎁</div>
                        <div class="stat-number" id="availableProducts">0</div>
                        <div class="stat-label">Recompensas Disponibles</div>
                    </div>
                </div>
                <div class="rewards-preview">
                    <a href="views/products_catalog.php" class="preview-card">
                        <div class="preview-icon">🛍️</div>
                        <div class="preview-text">
                            <div class="preview-title">Explorar Tienda</div>
                            <div class="preview-desc">Ver todos los premios disponibles</div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Tab: Acciones Rápidas -->
            <div class="tab-content" id="actions-tab">
                <div class="tab-header">
                    <h3>⚡ Centro de Comando</h3>
                    <p>Acceso rápido a todas las funciones principales</p>
                </div>
                <div class="actions-grid-compact">
                    <a href="views/leaderboard.php" class="action-card-compact primary">
                        <div class="action-icon">🏆</div>
                        <div class="action-text">
                            <div class="action-title">Ranking Global</div>
                            <div class="action-desc">Ver clasificación</div>
                        </div>
                    </a>
                    
                    <a href="views/notifications.php" class="action-card-compact info">
                        <div class="action-icon">🔔</div>
                        <div class="action-text">
                            <div class="action-title">Notificaciones</div>
                            <div class="action-desc">Ver misiones</div>
                        </div>
                    </a>
                    
                    <?php if (canAssignPoints()): ?>
                    <a href="views/assign_points.php" class="action-card-compact warning">
                        <div class="action-icon">⭐</div>
                        <div class="action-text">
                            <div class="action-title">Otorgar Puntos</div>
                            <div class="action-desc">Poder de moderador</div>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (canManageStands()): ?>
                    <a href="views/stand_claims.php" class="action-card-compact purple">
                        <div class="action-icon">🎯</div>
                        <div class="action-text">
                            <div class="action-title">Centro de Control</div>
                            <div class="action-desc">Gestionar reclamos</div>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                    <a href="views/admin_dashboard.php" class="action-card-compact danger">
                        <div class="action-icon">👑</div>
                        <div class="action-text">
                            <div class="action-title">Panel Admin</div>
                            <div class="action-desc">Control total</div>
                        </div>
                    </a>
                    
                    <a href="index.php?page=admin_events" class="action-card-compact dark">
                        <div class="action-icon">🎪</div>
                        <div class="action-text">
                            <div class="action-title">Gestión de Eventos</div>
                            <div class="action-desc">Crear desafíos</div>
                        </div>
                    </a>
                    
                    <a href="views/admin_reports.php" class="action-card-compact gradient">
                        <div class="action-icon">📊</div>
                        <div class="action-text">
                            <div class="action-title">Analytics</div>
                            <div class="action-desc">Ver estadísticas</div>
                        </div>
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

        // Sistema de pestañas
        function showTab(tabName) {
            // Ocultar todas las pestañas
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remover clase activa de todos los botones
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Mostrar la pestaña seleccionada
            const selectedTab = document.getElementById(tabName + '-tab');
            const selectedButton = event.target;
            
            if (selectedTab) {
                selectedTab.classList.add('active');
                selectedButton.classList.add('active');
            }
            
            // Cargar contenido específico de la pestaña
            switch(tabName) {
                case 'challenges':
                    loadActiveEvents();
                    break;
                case 'tournaments':
                    loadUpcomingTournaments();
                    break;
                case 'rewards':
                    loadAvailableProducts();
                    break;
            }
        }

        // Load user data and stats
        async function loadDashboardData() {
            try {
                const response = await fetch('../api/users.php?action=profile');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('userPoints').textContent = result.user.total_points || 0;
                    
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
                    document.getElementById('userRank').textContent = `#${result.data.rank}`;
                } else {
                    document.getElementById('userRank').textContent = 'N/A';
                }
            } catch (error) {
                console.error('Error loading user rank:', error);
                document.getElementById('userRank').textContent = 'N/A';
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
                            <div class="empty-icon">⚔️</div>
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
                            <div class="empty-icon">🏟️</div>
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

        // Show tournaments section
        function showTournaments() {
            showTab('tournaments');
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
                document.getElementById('availableProducts').textContent = '12';
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
            loadActiveEvents(); // Cargar desafíos por defecto (primera pestaña)
            
            // Refresh data periodically
            setInterval(() => {
                loadNotificationCount();
                // Solo refrescar la pestaña activa
                const activeTab = document.querySelector('.tab-content.active');
                if (activeTab) {
                    const tabId = activeTab.id;
                    if (tabId === 'challenges-tab') {
                        loadActiveEvents();
                    } else if (tabId === 'tournaments-tab') {
                        loadUpcomingTournaments();
                    }
                }
            }, 30000); // Every 30 seconds
        });
    </script>
</body>

</html>