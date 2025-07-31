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
    <title>Dashboard - Sistema de Puntos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #2c3e50;
            transition: all 0.3s ease;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: #2c3e50;
            padding: 1rem 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 3px solid #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .notification-bell {
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .notification-bell:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, #ff4757, #ff3838);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff4757, #ff3838);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.4);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem 2rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .welcome-card:hover::before {
            left: 100%;
        }

        .welcome-card h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .welcome-card p {
            font-size: 1.1rem;
            color: #7f8c8d;
            max-width: 600px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .action-card:hover::before {
            transform: scaleX(1);
        }

        .action-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .action-card p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .btn:hover::before {
            left: 100%;
        }

        .role-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .role-badge.admin {
            background: linear-gradient(135deg, #ff4757, #ff3838);
            box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
        }

        .role-badge.assistant {
            background: linear-gradient(135deg, #2ed573, #20bf6b);
            box-shadow: 0 4px 12px rgba(46, 213, 115, 0.3);
        }

        .role-badge.stand_manager {
            background: linear-gradient(135deg, #ffa502, #ff6348);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 165, 2, 0.3);
        }

        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .section-card h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .events-list, .tournaments-list {
            display: grid;
            gap: 1rem;
        }

        .event-item, .tournament-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 16px;
            border-left: 4px solid transparent;
            border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .event-item::before, .tournament-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            transition: left 0.5s ease;
        }

        .event-item:hover, .tournament-item:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .event-item:hover::before, .tournament-item:hover::before {
            left: 100%;
        }

        .event-info, .tournament-info {
            flex: 1;
        }

        .event-name, .tournament-name {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }

        .event-dates, .tournament-time {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .tournament-points {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .event-status {
            background: linear-gradient(135deg, #2ed573, #20bf6b);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(46, 213, 115, 0.3);
        }

        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 3rem;
            font-size: 1.1rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .quick-action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .quick-action-btn:hover::before {
            left: 100%;
        }

        .loading {
            text-align: center;
            color: #7f8c8d;
            padding: 2rem;
            font-size: 1.1rem;
        }

        .error {
            text-align: center;
            color: #ff4757;
            padding: 1.5rem;
            background: linear-gradient(135deg, #ffecec, #ffe6e6);
            border-radius: 12px;
            margin: 1rem 0;
            border: 1px solid rgba(255, 71, 87, 0.2);
            font-weight: 600;
        }

        /* Smooth page load animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-card, .stat-card, .action-card, .section-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .navbar .container {
                padding: 0 1rem;
            }
            
            .user-info {
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .welcome-card {
                padding: 2rem 1rem;
            }
            
            .welcome-card h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <h1>Sistema de gestion</h1>
            <div class="user-info">
                <span class="role-badge <?php echo $user['role']; ?>">
                    <?php echo ucfirst($user['role']); ?>
                </span>
                <button class="notification-bell" onclick="goToNotifications()" title="Notificaciones">
                    🔔
                    <span id="notification-badge" class="notification-badge" style="display: none;">0</span>
                </button>
                <span>¡Hola, <?php echo htmlspecialchars($user['nickname']); ?>!</span>
                <button class="logout-btn" onclick="logout()">Cerrar Sesión</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h2>¡Bienvenido al Sistema de Puntos y Torneos!</h2>
            <p>Participa en torneos, gana puntos y canjea increíbles premios.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="userPoints">0</div>
                <div class="stat-label">Puntos Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="userRank">-</div>
                <div class="stat-label">Posición en Ranking</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="activeTournaments">0</div>
                <div class="stat-label">Torneos Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="availableProducts">0</div>
                <div class="stat-label">Productos Disponibles</div>
            </div>
        </div>

        <!-- Active Events Section -->
        <div class="section-card" id="activeEventsSection" style="display: none;">
            <h3>🎯 Eventos Activos</h3>
            <div id="activeEventsList" class="events-list">
                <!-- Events will be loaded here -->
            </div>
        </div>

        <!-- Upcoming Tournaments Section -->
        <div class="section-card" id="upcomingTournamentsSection" style="display: none;">
            <h3>🏆 Próximos Torneos</h3>
            <div id="upcomingTournamentsList" class="tournaments-list">
                <!-- Tournaments will be loaded here -->
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="section-card">
            <h3>🚀 Acciones Rápidas</h3>
            <div class="quick-actions">
                <a href="views/leaderboard.php" class="quick-action-btn">
                    🏆 Ver Ranking
                </a>
                <a href="views/products_catalog.php" class="quick-action-btn">
                    🎁 Ver Productos
                </a>
                <a href="views/notifications.php" class="quick-action-btn">
                    🔔 Notificaciones
                </a>
                <?php if (canAssignPoints()): ?>
                <a href="views/assign_points.php" class="quick-action-btn">
                    ⭐ Asignar Puntos
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="actions-grid">
            <div class="action-card">
                <h3>Ver Leaderboard</h3>
                <p>Consulta tu posición y compite con otros usuarios.</p>
                <a href="views/leaderboard.php" class="btn">Ver Ranking</a>
            </div>

            <div class="action-card">
                <h3>Torneos Disponibles</h3>
                <p>Únete a torneos activos y gana puntos.</p>
                <a href="#" class="btn" onclick="showTournaments()">Ver Torneos</a>
            </div>

            <div class="action-card">
                <h3>Catálogo de Productos</h3>
                <p>Descubre qué puedes canjear con tus puntos.</p>
                <a href="views/products_catalog.php" class="btn">Ver Productos</a>
            </div>

            <?php if (canAssignPoints()): ?>
                <div class="action-card">
                    <h3>Asignar Puntos</h3>
                    <p>Asigna puntos a usuarios por completar challenges.</p>
                    <a href="#" class="btn">Asignar Puntos</a>
                </div>
            <?php endif; ?>

            <?php if (canManageStands()): ?>
                <div class="action-card">
                    <h3>Gestionar Reclamos</h3>
                    <p>Procesa reclamos de productos y verifica puntos de usuarios.</p>
                    <a href="views/stand_claims.php" class="btn">Gestionar Reclamos</a>
                </div>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
                <div class="action-card">
                    <h3>Panel Administrativo</h3>
                    <p>Accede al panel de control completo del sistema.</p>
                    <a href="views/admin_dashboard.php" class="btn">Panel de Control</a>
                </div>

                <div class="action-card">
                    <h3>Gestión de Eventos</h3>
                    <p>Crea y administra eventos del sistema.</p>
                    <a href="index.php?page=admin_events" class="btn">Gestionar Eventos</a>
                </div>

                <div class="action-card">
                    <h3>Reportes y Estadísticas</h3>
                    <p>Analiza el rendimiento del sistema.</p>
                    <a href="views/admin_reports.php" class="btn">Ver Reportes</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

                const section = document.getElementById('activeEventsSection');
                const list = document.getElementById('activeEventsList');

                if (result.success && result.events && result.events.length > 0) {
                    list.innerHTML = result.events.map(event => `
                        <div class="event-item">
                            <div class="event-info">
                                <div class="event-name">${escapeHtml(event.name)}</div>
                                <div class="event-dates">
                                    ${formatDate(event.start_date)} - ${formatDate(event.end_date)}
                                </div>
                            </div>
                            <div class="event-status">Activo</div>
                        </div>
                    `).join('');
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading active events:', error);
            }
        }

        // Load upcoming tournaments
        async function loadUpcomingTournaments() {
            try {
                const response = await fetch('../api/tournaments.php?action=upcoming&limit=5');
                const result = await response.json();

                const section = document.getElementById('upcomingTournamentsSection');
                const list = document.getElementById('upcomingTournamentsList');
                const activeTournamentsElement = document.getElementById('activeTournaments');

                if (result.success && result.tournaments && result.tournaments.length > 0) {
                    list.innerHTML = result.tournaments.map(tournament => `
                        <div class="tournament-item">
                            <div class="tournament-info">
                                <div class="tournament-name">${escapeHtml(tournament.name)}</div>
                                <div class="tournament-time">
                                    📅 ${formatDateTime(tournament.scheduled_time)}
                                </div>
                            </div>
                            <div class="tournament-points">${tournament.points_reward} pts</div>
                        </div>
                    `).join('');
                    section.style.display = 'block';
                    activeTournamentsElement.textContent = result.tournaments.length;
                } else {
                    section.style.display = 'none';
                    activeTournamentsElement.textContent = '0';
                }
            } catch (error) {
                console.error('Error loading upcoming tournaments:', error);
                document.getElementById('activeTournaments').textContent = '0';
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
            const section = document.getElementById('upcomingTournamentsSection');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Load notification count
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?action=unread-count');
                const result = await response.json();

                if (result.success) {
                    const badge = document.getElementById('notification-badge');
                    const count = result.unread_count;
                    
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
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
            loadActiveEvents();
            loadUpcomingTournaments();
            loadAvailableProducts();
            
            // Refresh data periodically
            setInterval(() => {
                loadNotificationCount();
                loadActiveEvents();
                loadUpcomingTournaments();
            }, 30000); // Every 30 seconds
        });
    </script>
</body>

</html>