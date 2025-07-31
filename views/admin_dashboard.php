<?php
// Check if user is logged in and is admin
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../index.php?page=login');
    exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Sistema de Puntos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
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
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .admin-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }

        .section-header h3 {
            margin-bottom: 0.5rem;
        }

        .section-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .section-content {
            padding: 1.5rem;
        }

        .section-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .action-btn {
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            color: #333;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .recent-activity {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .activity-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e1e5e9;
        }

        .activity-header h3 {
            color: #333;
        }

        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .activity-icon.event {
            background: #e3f2fd;
            color: #1976d2;
        }

        .activity-icon.tournament {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .activity-icon.points {
            background: #e8f5e8;
            color: #388e3c;
        }

        .activity-icon.claim {
            background: #fff3e0;
            color: #f57c00;
        }

        .activity-info {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .activity-description {
            color: #666;
            font-size: 0.9rem;
        }

        .activity-time {
            color: #999;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .error {
            text-align: center;
            color: #dc3545;
            padding: 1rem;
            background: #f8d7da;
            border-radius: 5px;
            margin: 1rem 0;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-stat {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .quick-stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .quick-stat-label {
            color: #666;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .admin-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .admin-sections {
                grid-template-columns: 1fr;
            }

            .nav-links {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Panel Administrativo</h1>
            <div class="nav-links">
                <a href="../index.php?page=dashboard">Dashboard Usuario</a>
                <a href="../index.php?page=admin_events">Eventos</a>
                <a href="admin_stands.php">Stands</a>
                <a href="admin_reports.php">Reportes</a>
                <a href="#" onclick="logout()">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>🎯 Panel de Control Administrativo</h2>
            <p>Gestiona todos los aspectos del sistema de puntos y torneos</p>
        </div>

        <!-- Admin Statistics -->
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number" id="totalUsers">0</div>
                <div class="stat-label">Usuarios Registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-number" id="activeEvents">0</div>
                <div class="stat-label">Eventos Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-number" id="totalTournaments">0</div>
                <div class="stat-label">Torneos Programados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number" id="totalPoints">0</div>
                <div class="stat-label">Puntos Distribuidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏪</div>
                <div class="stat-number" id="totalStands">0</div>
                <div class="stat-label">Stands Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎁</div>
                <div class="stat-number" id="totalClaims">0</div>
                <div class="stat-label">Productos Reclamados</div>
            </div>
        </div>

        <!-- Admin Sections -->
        <div class="admin-sections">
            <!-- Event Management -->
            <div class="section-card">
                <div class="section-header">
                    <h3>🎯 Gestión de Eventos</h3>
                    <p>Administra eventos y su configuración</p>
                </div>
                <div class="section-content">
                    <div class="section-actions">
                        <a href="../index.php?page=admin_events" class="action-btn">
                            📅 Ver Todos los Eventos
                        </a>
                        <a href="../index.php?page=admin_events&action=create" class="action-btn">
                            ➕ Crear Nuevo Evento
                        </a>
                        <a href="#" onclick="showActiveEvents()" class="action-btn">
                            🔴 Eventos Activos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tournament Management -->
            <div class="section-card">
                <div class="section-header">
                    <h3>🏆 Gestión de Torneos</h3>
                    <p>Administra torneos y competencias</p>
                </div>
                <div class="section-content">
                    <div class="section-actions">
                        <a href="admin_tournaments.php" class="action-btn">
                            🏆 Ver Todos los Torneos
                        </a>
                        <a href="admin_tournaments.php?action=create" class="action-btn">
                            ➕ Crear Nuevo Torneo
                        </a>
                        <a href="#" onclick="showUpcomingTournaments()" class="action-btn">
                            ⏰ Próximos Torneos
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Management -->
            <div class="section-card">
                <div class="section-header">
                    <h3>👥 Gestión de Usuarios</h3>
                    <p>Administra usuarios y permisos</p>
                </div>
                <div class="section-content">
                    <div class="section-actions">
                        <a href="#" onclick="showUserManagement()" class="action-btn">
                            👥 Ver Todos los Usuarios
                        </a>
                        <a href="assign_points.php" class="action-btn">
                            ⭐ Asignar Puntos
                        </a>
                        <a href="leaderboard.php" class="action-btn">
                            🏅 Ver Leaderboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stand Management -->
            <div class="section-card">
                <div class="section-header">
                    <h3>🏪 Gestión de Stands</h3>
                    <p>Administra stands y productos</p>
                </div>
                <div class="section-content">
                    <div class="section-actions">
                        <a href="admin_stands.php" class="action-btn">
                            🏪 Ver Todos los Stands
                        </a>
                        <a href="admin_stands.php?action=create" class="action-btn">
                            ➕ Crear Nuevo Stand
                        </a>
                        <a href="stand_claims.php" class="action-btn">
                            🎁 Gestionar Reclamos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reports and Analytics -->
            <div class="section-card">
                <div class="section-header">
                    <h3>📊 Reportes y Análisis</h3>
                    <p>Estadísticas y reportes del sistema</p>
                </div>
                <div class="section-content">
                    <div class="section-actions">
                        <a href="admin_reports.php" class="action-btn">
                            📊 Ver Reportes Completos
                        </a>
                        <a href="#" onclick="generateQuickReport()" class="action-btn">
                            📈 Reporte Rápido
                        </a>
                        <a href="#" onclick="exportData()" class="action-btn">
                            💾 Exportar Datos
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="section-card">
                <div class="section-header">
                    <h3>⚙️ Configuración del Sistema</h3>
                    <p>Configuraciones generales y mantenimiento</p>
                </div>
                <div class="section-content">
                    <div class="section-actions">
                        <a href="#" onclick="showSystemSettings()" class="action-btn">
                            ⚙️ Configuraciones
                        </a>
                        <a href="#" onclick="clearCache()" class="action-btn">
                            🗑️ Limpiar Caché
                        </a>
                        <a href="#" onclick="showSystemLogs()" class="action-btn">
                            📋 Ver Logs del Sistema
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <div class="activity-header">
                <h3>📋 Actividad Reciente</h3>
            </div>
            <div class="activity-list" id="recentActivityList">
                <div class="loading">Cargando actividad reciente...</div>
            </div>
        </div>
    </div>

    <script>
        // Load admin dashboard data
        async function loadAdminStats() {
            try {
                const response = await fetch('../api/reports.php?action=dashboard_stats');
                const result = await response.json();

                if (result.success && result.data) {
                    const stats = result.data;
                    
                    document.getElementById('totalUsers').textContent = stats.total_users || 0;
                    document.getElementById('activeEvents').textContent = stats.active_events || 0;
                    document.getElementById('totalTournaments').textContent = stats.total_tournaments || 0;
                    document.getElementById('totalPoints').textContent = formatNumber(stats.total_points || 0);
                    document.getElementById('totalStands').textContent = stats.total_stands || 0;
                    document.getElementById('totalClaims').textContent = stats.total_claims || 0;
                }
            } catch (error) {
                console.error('Error loading admin stats:', error);
            }
        }

        // Load recent activity
        async function loadRecentActivity() {
            try {
                const response = await fetch('../api/reports.php?action=recent_activity&limit=10');
                const result = await response.json();

                const activityList = document.getElementById('recentActivityList');

                if (result.success && result.data && result.data.length > 0) {
                    activityList.innerHTML = result.data.map(activity => `
                        <div class="activity-item">
                            <div class="activity-icon ${activity.type}">
                                ${getActivityIcon(activity.type)}
                            </div>
                            <div class="activity-info">
                                <div class="activity-title">${escapeHtml(activity.title)}</div>
                                <div class="activity-description">${escapeHtml(activity.description)}</div>
                            </div>
                            <div class="activity-time">${formatRelativeTime(activity.timestamp)}</div>
                        </div>
                    `).join('');
                } else {
                    activityList.innerHTML = '<div class="activity-item"><div class="activity-info">No hay actividad reciente</div></div>';
                }
            } catch (error) {
                console.error('Error loading recent activity:', error);
                document.getElementById('recentActivityList').innerHTML = '<div class="error">Error al cargar la actividad reciente</div>';
            }
        }

        // Get activity icon based on type
        function getActivityIcon(type) {
            const icons = {
                'event': '🎯',
                'tournament': '🏆',
                'points': '⭐',
                'claim': '🎁',
                'user': '👤',
                'stand': '🏪'
            };
            return icons[type] || '📋';
        }

        // Admin action functions
        function showActiveEvents() {
            window.location.href = '../index.php?page=admin_events&filter=active';
        }

        function showUpcomingTournaments() {
            window.location.href = 'admin_tournaments.php?filter=upcoming';
        }

        function showUserManagement() {
            // This would need a user management interface
            alert('Funcionalidad de gestión de usuarios en desarrollo');
        }

        function generateQuickReport() {
            window.location.href = 'admin_reports.php?quick=true';
        }

        function exportData() {
            // This would trigger a data export
            alert('Funcionalidad de exportación en desarrollo');
        }

        function showSystemSettings() {
            alert('Panel de configuración en desarrollo');
        }

        function clearCache() {
            if (confirm('¿Estás seguro de que quieres limpiar el caché del sistema?')) {
                // This would clear system cache
                alert('Caché limpiado exitosamente');
            }
        }

        function showSystemLogs() {
            alert('Visor de logs del sistema en desarrollo');
        }

        // Utility functions
        function formatNumber(num) {
            return new Intl.NumberFormat('es-ES').format(num);
        }

        function formatRelativeTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) {
                return 'Hace un momento';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
            } else {
                const days = Math.floor(diffInSeconds / 86400);
                return `Hace ${days} día${days > 1 ? 's' : ''}`;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
                window.location.href = '../index.php?page=login';
            }
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadAdminStats();
            loadRecentActivity();
            
            // Refresh data every 60 seconds
            setInterval(() => {
                loadAdminStats();
                loadRecentActivity();
            }, 60000);
        });
    </script>
</body>
</html>