<?php
session_start();
require_once '../includes/auth.php';
require_once '../models/User.php';
require_once '../config/database.php';

// Verify authentication
requireLogin();

// Get current user data
$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$currentUser = $userModel->getById($_SESSION['user_id']);
if (!$currentUser) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Sistema de Puntos</title>
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
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-link {
            color: #2c3e50 !important;
            font-weight: 600;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
            text-decoration: none;
        }

        .nav-link:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .gap-3 {
            gap: 1rem;
        }

        .me-2 {
            margin-right: 0.5rem;
        }

        .rounded-circle {
            border-radius: 50%;
        }

        .leaderboard-hero {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem 2rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .leaderboard-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .leaderboard-hero:hover::before {
            left: 100%;
        }

        .leaderboard-hero h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .leaderboard-hero p {
            font-size: 1.2rem;
            color: #7f8c8d;
            max-width: 600px;
            margin: 0 auto;
        }

        .trophy-animation {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
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

        .podium-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .text-center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .podium-container {
            display: flex;
            justify-content: center;
            align-items: end;
            gap: 1rem;
            margin: 2rem 0;
            min-height: 200px;
        }

        .podium-place {
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .podium-place:hover {
            transform: translateY(-5px);
        }

        .podium-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 4px solid;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .podium-place.first .podium-avatar {
            border-color: #ffd700;
            width: 100px;
            height: 100px;
        }

        .podium-place.second .podium-avatar {
            border-color: #c0c0c0;
        }

        .podium-place.third .podium-avatar {
            border-color: #cd7f32;
        }

        .podium-base {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem 1rem;
            border-radius: 12px;
            min-width: 120px;
            position: relative;
        }

        .podium-place.first .podium-base {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #333;
            height: 120px;
            padding-top: 2rem;
        }

        .podium-place.second .podium-base {
            background: linear-gradient(45deg, #c0c0c0, #e8e8e8);
            color: #333;
            height: 100px;
        }

        .podium-place.third .podium-base {
            background: linear-gradient(45deg, #cd7f32, #daa520);
            height: 80px;
        }

        .crown {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            color: #ffd700;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateX(-50%) translateY(0px); }
            50% { transform: translateX(-50%) translateY(-10px); }
        }

        .user-context-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-context-section h5 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .leaderboard-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .leaderboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .leaderboard-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .refresh-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .refresh-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .refresh-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .auto-refresh-indicator {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .leaderboard-entry {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .leaderboard-entry::before {
            content: '';
            position: absolute;
            left: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            transition: left 0.5s ease;
        }

        .leaderboard-entry:hover {
            background: rgba(102, 126, 234, 0.02);
            transform: translateX(5px);
        }

        .leaderboard-entry:hover::before {
            left: 100%;
        }

        .leaderboard-entry.current-user {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05));
            border-left: 4px solid #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .rank-badge {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            margin-right: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .rank-badge:hover {
            transform: scale(1.1);
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            border: 3px solid #fff;
        }

        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            color: #333;
            border: 3px solid #fff;
        }

        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #daa520);
            color: white;
            border: 3px solid #fff;
        }

        .rank-other {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .profile-section {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .profile-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-right: 1rem;
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.1);
        }

        .user-info h6 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .user-info .user-status {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .points-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .points-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .loading-spinner {
            text-align: center;
            padding: 4rem 2rem;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0,0,0,0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 2rem;
            text-align: center;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Animations */
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

        .stat-card, .leaderboard-entry, .podium-place {
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .leaderboard-hero {
                padding: 2rem 1rem;
            }
            
            .leaderboard-hero h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .podium-container {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .leaderboard-entry {
                padding: 1rem;
            }
            
            .leaderboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .refresh-controls {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            cursor: pointer;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 150px;
            z-index: 1000;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 0.5rem 1rem;
            color: #333;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .position-relative {
            position: relative;
        }

        .position-absolute {
            position: absolute;
        }

        .top-0 {
            top: 0;
        }

        .start-100 {
            left: 100%;
        }

        .translate-middle {
            transform: translate(-50%, -50%);
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .rounded-pill {
            border-radius: 50rem;
        }

        .bg-danger {
            background-color: #dc3545;
        }

        .mt-3 {
            margin-top: 1rem;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="../">
                <i class="fas fa-trophy me-2"></i>Sistema de Puntos
            </a>
            <div class="d-flex align-items-center gap-3">
                <a class="nav-link" href="../">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a class="nav-link active" href="leaderboard.php">
                    <i class="fas fa-medal me-1"></i>Leaderboard
                </a>
                <a class="nav-link position-relative" href="notifications.php" title="Notificaciones">
                    <i class="fas fa-bell"></i>
                    <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                        0
                    </span>
                </a>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" onclick="toggleDropdown()">
                        <img src="<?php echo htmlspecialchars($currentUser['profile_image'] ?? 'https://picsum.photos/30'); ?>"
                            alt="Profile" class="rounded-circle me-2" width="30" height="30">
                        <?php echo htmlspecialchars($currentUser['nickname']); ?>
                    </a>
                    <ul class="dropdown-menu" id="dropdownMenu">
                        <li><a class="dropdown-item" href="../includes/logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Hero Section -->
        <div class="leaderboard-hero">
            <div class="trophy-animation">🏆</div>
            <h1>Leaderboard</h1>
            <p>Compite con otros usuarios, escala posiciones y demuestra tu habilidad en los torneos</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-number" id="totalUsers">-</div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="highestPoints">-</div>
                <div class="stat-label">Puntos Máximos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="averagePoints">-</div>
                <div class="stat-label">Promedio de Puntos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="userRank">-</div>
                <div class="stat-label">Tu Posición</div>
            </div>
        </div>

        <!-- Podium Section -->
        <div class="podium-section" id="podiumSection" style="display: none;">
            <h3 class="text-center mb-4">🥇 Top 3 Campeones</h3>
            <div class="podium-container" id="podiumContainer">
                <!-- Podium will be rendered here -->
            </div>
        </div>

        <!-- User Context Section -->
        <div class="user-context-section" id="userContextSection" style="display: none;">
            <h5><i class="fas fa-user-circle me-2"></i>Tu Posición en el Ranking</h5>
            <div id="userContext"></div>
        </div>

        <!-- Main Leaderboard -->
        <div class="leaderboard-main">
            <div class="leaderboard-header">
                <h3><i class="fas fa-list-ol me-2"></i>Ranking General</h3>
                <div class="refresh-controls">
                    <button class="refresh-btn" onclick="refreshLeaderboard()">
                        <i class="fas fa-sync-alt me-1"></i>Actualizar
                    </button>
                    <div class="auto-refresh-indicator" id="autoRefreshIndicator">
                        Actualización automática en <span id="refreshCountdown">30</span>s
                    </div>
                </div>
            </div>

            <div id="leaderboardContent">
                <div class="loading-spinner">
                    <div class="spinner-border" style="color: #667eea;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3">Cargando leaderboard...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let refreshInterval;
        let countdownInterval;
        let refreshCountdown = 30;
        const currentUserId = <?php echo $currentUser['id']; ?>;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadLeaderboard();
            loadStats();
            loadUserContext();
            loadNotificationCount();
            startAutoRefresh();
        });

        /**
         * Toggle dropdown menu
         */
        function toggleDropdown() {
            const menu = document.getElementById('dropdownMenu');
            menu.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.dropdown');
            if (!dropdown.contains(event.target)) {
                document.getElementById('dropdownMenu').classList.remove('show');
            }
        });

        /**
         * Load leaderboard data
         */
        async function loadLeaderboard(forceRefresh = false) {
            try {
                const refreshParam = forceRefresh ? '&refresh=true' : '';
                const response = await fetch(`../api/leaderboard.php?action=get&limit=50${refreshParam}`);
                const data = await response.json();

                if (data.success) {
                    renderLeaderboard(data.data);
                } else {
                    showError('Error al cargar el leaderboard');
                }
            } catch (error) {
                console.error('Error loading leaderboard:', error);
                showError('Error de conexión al cargar el leaderboard');
            }
        }

        /**
         * Load statistics
         */
        async function loadStats() {
            try {
                const response = await fetch('../api/leaderboard.php?action=stats');
                const data = await response.json();

                if (data.success) {
                    renderStats(data.data);
                } else {
                    console.error('Error loading stats:', data.error);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        /**
         * Load user context
         */
        async function loadUserContext() {
            try {
                const response = await fetch(`../api/leaderboard.php?action=user_context&user_id=${currentUserId}&context_size=5`);
                const data = await response.json();

                if (data.success && data.data) {
                    renderUserContext(data.data);
                }
            } catch (error) {
                console.error('Error loading user context:', error);
            }
        }

        /**
         * Render leaderboard
         */
        function renderLeaderboard(leaderboard) {
            const content = document.getElementById('leaderboardContent');

            if (leaderboard.length === 0) {
                content.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-trophy"></i>
                        <h5>No hay usuarios en el leaderboard</h5>
                        <p>¡Sé el primero en ganar puntos!</p>
                    </div>
                `;
                return;
            }

            // Render podium for top 3
            renderPodium(leaderboard.slice(0, 3));

            // Render full leaderboard
            let html = '';
            leaderboard.forEach((entry, index) => {
                const isCurrentUser = entry.user_id == currentUserId;
                const rankClass = getRankClass(entry.rank);
                const profileImage = entry.profile_image || 'https://picsum.photos/200/200?random=' + entry.user_id;
                console.log(profileImage)
                html += `
                    <div class="leaderboard-entry ${isCurrentUser ? 'current-user' : ''}">
                        <div class="rank-badge ${rankClass}">
                            ${entry.rank <= 3 ? getRankIcon(entry.rank) : entry.rank}
                        </div>
                        <div class="profile-section">
                            <img src="${profileImage}" alt="Profile" class="profile-image">
                            <div class="user-info">
                                <h6>${escapeHtml(entry.nickname)}</h6>
                                <div class="user-status">
                                    ${isCurrentUser ? '¡Eres tú!' : `Posición #${entry.rank}`}
                                </div>
                            </div>
                        </div>
                        <div class="points-badge">
                            ${entry.total_points.toLocaleString()} pts
                        </div>
                    </div>
                `;
            });

            content.innerHTML = html;
        }

        /**
         * Render podium for top 3 users
         */
        function renderPodium(topUsers) {
            const podiumSection = document.getElementById('podiumSection');
            const podiumContainer = document.getElementById('podiumContainer');

            if (topUsers.length === 0) {
                podiumSection.style.display = 'none';
                return;
            }

            // Organize podium positions (2nd, 1st, 3rd for visual layout)
            const podiumOrder = [
                topUsers[1], // 2nd place
                topUsers[0], // 1st place  
                topUsers[2]  // 3rd place
            ].filter(user => user); // Filter out undefined users

            let html = '';
            podiumOrder.forEach((user, index) => {
                if (!user) return;
                
                const position = user.rank;
                const positionClass = position === 1 ? 'first' : position === 2 ? 'second' : 'third';
                const profileImage = user.profile_image || 'https://picsum.photos/200/200?random=' + user.user_id;
                const crown = position === 1 ? '<div class="crown">👑</div>' : '';
                
                html += `
                    <div class="podium-place ${positionClass}">
                        ${crown}
                        <img src="${profileImage}" alt="Profile" class="podium-avatar">
                        <div class="podium-base">
                            <div style="font-size: 1.2rem; font-weight: 800; margin-bottom: 0.5rem;">
                                ${position === 1 ? '🥇' : position === 2 ? '🥈' : '🥉'} #${position}
                            </div>
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">${escapeHtml(user.nickname)}</div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">${user.total_points.toLocaleString()} pts</div>
                        </div>
                    </div>
                `;
            });

            podiumContainer.innerHTML = html;
            podiumSection.style.display = 'block';
        }

        /**
         * Render statistics
         */
        function renderStats(stats) {
            document.getElementById('totalUsers').textContent = stats.total_users.toLocaleString();
            document.getElementById('highestPoints').textContent = stats.highest_points.toLocaleString();
            document.getElementById('averagePoints').textContent = stats.average_points.toLocaleString();

            // Load user rank separately
            loadUserRank();
        }

        /**
         * Load user rank
         */
        async function loadUserRank() {
            try {
                const response = await fetch(`../api/leaderboard.php?action=user_rank&user_id=${currentUserId}`);
                const data = await response.json();

                if (data.success && data.data) {
                    document.getElementById('userRank').textContent = `#${data.data.rank}`;
                } else {
                    document.getElementById('userRank').textContent = 'N/A';
                }
            } catch (error) {
                console.error('Error loading user rank:', error);
                document.getElementById('userRank').textContent = 'Error';
            }
        }

        /**
         * Render user context
         */
        function renderUserContext(contextData) {
            const section = document.getElementById('userContextSection');
            const content = document.getElementById('userContext');

            if (!contextData.context || contextData.context.length === 0) {
                section.style.display = 'none';
                return;
            }

            let html = '';
            contextData.context.forEach(entry => {
                const isCurrentUser = entry.is_current_user;
                const rankClass = getRankClass(entry.rank);
                const profileImage = entry.profile_image || 'https://picsum.photos/40/40?random=' + entry.user_id;

                html += `
                    <div class="leaderboard-entry ${isCurrentUser ? 'current-user' : ''}" style="padding: 1rem;">
                        <div class="rank-badge ${rankClass}" style="width: 35px; height: 35px; font-size: 0.9rem;">
                            ${entry.rank}
                        </div>
                        <div class="profile-section">
                            <img src="${profileImage}" alt="Profile" class="profile-image" style="width: 45px; height: 45px;">
                            <div class="user-info">
                                <h6 style="font-size: 1rem;">${escapeHtml(entry.nickname)}</h6>
                                <div class="user-status">
                                    ${isCurrentUser ? '¡Eres tú!' : `Posición #${entry.rank}`}
                                </div>
                            </div>
                        </div>
                        <div class="points-badge" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            ${entry.total_points.toLocaleString()} pts
                        </div>
                    </div>
                `;
            });

            content.innerHTML = html;
            section.style.display = 'block';
        }

        /**
         * Get rank CSS class
         */
        function getRankClass(rank) {
            if (rank === 1) return 'rank-1';
            if (rank === 2) return 'rank-2';
            if (rank === 3) return 'rank-3';
            return 'rank-other';
        }

        /**
         * Get rank icon for top 3
         */
        function getRankIcon(rank) {
            const icons = {
                1: '<i class="fas fa-crown"></i>',
                2: '<i class="fas fa-medal"></i>',
                3: '<i class="fas fa-award"></i>'
            };
            return icons[rank] || rank;
        }

        /**
         * Escape HTML characters
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Refresh leaderboard
         */
        async function refreshLeaderboard() {
            const refreshBtn = document.querySelector('.refresh-btn');
            const originalContent = refreshBtn.innerHTML;

            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...';
            refreshBtn.disabled = true;

            await loadLeaderboard(true);
            await loadStats();
            await loadUserContext();

            refreshBtn.innerHTML = originalContent;
            refreshBtn.disabled = false;

            // Reset countdown
            refreshCountdown = 30;
        }

        /**
         * Start auto refresh
         */
        function startAutoRefresh() {
            // Refresh every 30 seconds
            refreshInterval = setInterval(() => {
                loadLeaderboard();
                loadStats();
                loadUserContext();
                loadNotificationCount();
                refreshCountdown = 30;
            }, 30000);

            // Update countdown every second
            countdownInterval = setInterval(() => {
                refreshCountdown--;
                document.getElementById('refreshCountdown').textContent = refreshCountdown;

                if (refreshCountdown <= 0) {
                    refreshCountdown = 30;
                }
            }, 1000);
        }

        /**
         * Load notification count
         */
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?action=unread-count');
                const result = await response.json();

                if (result.success) {
                    const badge = document.getElementById('notification-badge');
                    const count = result.unread_count;
                    
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading notification count:', error);
            }
        }

        /**
         * Show error message
         */
        function showError(message) {
            const content = document.getElementById('leaderboardContent');
            content.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }

        // Cleanup intervals when page unloads
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) clearInterval(refreshInterval);
            if (countdownInterval) clearInterval(countdownInterval);
        });
    </script>
</body>

</html>
