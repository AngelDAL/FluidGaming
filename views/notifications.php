<?php
require_once '../includes/auth.php';
require_once '../models/User.php';
require_once '../config/database.php';

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
    <title>Notificaciones - Sistema de Puntos</title>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            align-items: center;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-section h2 {
            color: #333;
            margin: 0;
        }

        .notification-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            font-size: 0.9rem;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .notifications-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .notification-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: background 0.3s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread {
            background: #f0f8ff;
            border-left: 4px solid #667eea;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .notification-icon.tournament {
            background: #e3f2fd;
            color: #1976d2;
        }

        .notification-icon.points {
            background: #e8f5e8;
            color: #388e3c;
        }

        .notification-icon.event {
            background: #fff3e0;
            color: #f57c00;
        }

        .notification-icon.system {
            background: #fce4ec;
            color: #c2185b;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .notification-message {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .notification-time {
            color: #999;
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination button {
            background: white;
            border: 1px solid #ddd;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <h1>Centro de Notificaciones</h1>
            <div class="nav-links">
                <a href="../dashboard.php">Dashboard</a>
                <a href="../views/leaderboard.php">Leaderboard</a>
                <span>¡Hola, <?php echo htmlspecialchars($user['nickname']); ?>!</span>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <div>
                <h2>Mis Notificaciones</h2>
                <p>Mantente al día con las últimas actualizaciones</p>
            </div>
            <div class="notification-actions">
                <button class="btn btn-secondary" onclick="markAllAsRead()">
                    Marcar Todas como Leídas
                </button>
                <button class="btn" onclick="refreshNotifications()">
                    Actualizar
                </button>
            </div>
        </div>

        <div id="error-container"></div>
        
        <div class="notifications-container">
            <div id="loading" class="loading">
                <p>Cargando notificaciones...</p>
            </div>
            <div id="notifications-list"></div>
        </div>

        <div id="pagination" class="pagination" style="display: none;">
            <button id="prev-btn" onclick="loadPreviousPage()">Anterior</button>
            <span id="page-info">Página 1</span>
            <button id="next-btn" onclick="loadNextPage()">Siguiente</button>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let hasMore = false;
        const limit = 20;

        // Load notifications
        async function loadNotifications(page = 1) {
            try {
                document.getElementById('loading').style.display = 'block';
                document.getElementById('error-container').innerHTML = '';

                const response = await fetch(`../api/notifications.php?action=list&page=${page}&limit=${limit}`);
                const result = await response.json();

                document.getElementById('loading').style.display = 'none';

                if (result.success) {
                    displayNotifications(result.notifications);
                    updatePagination(page, result.pagination.has_more);
                } else {
                    showError('Error al cargar notificaciones: ' + result.error);
                }
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                showError('Error de conexión al cargar notificaciones');
                console.error('Error loading notifications:', error);
            }
        }

        // Display notifications
        function displayNotifications(notifications) {
            const container = document.getElementById('notifications-list');
            
            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🔔</div>
                        <h3>No tienes notificaciones</h3>
                        <p>Cuando recibas notificaciones aparecerán aquí</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = notifications.map(notification => `
                <div class="notification-item ${!notification.is_read ? 'unread' : ''}" 
                     onclick="markAsRead(${notification.id})">
                    <div class="notification-icon ${notification.type}">
                        ${getNotificationIcon(notification.type)}
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${escapeHtml(notification.title)}</div>
                        <div class="notification-message">${escapeHtml(notification.message)}</div>
                        <div class="notification-time">${formatTime(notification.created_at)}</div>
                    </div>
                    ${!notification.is_read ? '<div class="unread-badge"></div>' : ''}
                </div>
            `).join('');
        }

        // Get notification icon based on type
        function getNotificationIcon(type) {
            switch (type) {
                case 'tournament':
                    return '🏆';
                case 'points':
                    return '⭐';
                case 'event':
                    return '📅';
                case 'system':
                    return '🔔';
                default:
                    return '📢';
            }
        }

        // Format time
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) {
                return 'Hace un momento';
            } else if (diffMins < 60) {
                return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
            } else if (diffHours < 24) {
                return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
            } else if (diffDays < 7) {
                return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
            } else {
                return date.toLocaleDateString('es-ES');
            }
        }

        // Mark notification as read
        async function markAsRead(notificationId) {
            try {
                const formData = new FormData();
                formData.append('notification_id', notificationId);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/notifications.php?action=mark-read', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Refresh notifications to update UI
                    loadNotifications(currentPage);
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        // Mark all notifications as read
        async function markAllAsRead() {
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/notifications.php?action=mark-all-read', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Refresh notifications to update UI
                    loadNotifications(currentPage);
                    showSuccess('Todas las notificaciones marcadas como leídas');
                } else {
                    showError('Error al marcar notificaciones: ' + result.error);
                }
            } catch (error) {
                showError('Error de conexión');
                console.error('Error marking all as read:', error);
            }
        }

        // Update pagination
        function updatePagination(page, hasMorePages) {
            currentPage = page;
            hasMore = hasMorePages;

            document.getElementById('page-info').textContent = `Página ${page}`;
            document.getElementById('prev-btn').disabled = page <= 1;
            document.getElementById('next-btn').disabled = !hasMorePages;
            document.getElementById('pagination').style.display = 'flex';
        }

        // Load previous page
        function loadPreviousPage() {
            if (currentPage > 1) {
                loadNotifications(currentPage - 1);
            }
        }

        // Load next page
        function loadNextPage() {
            if (hasMore) {
                loadNotifications(currentPage + 1);
            }
        }

        // Refresh notifications
        function refreshNotifications() {
            loadNotifications(currentPage);
        }

        // Show error message
        function showError(message) {
            document.getElementById('error-container').innerHTML = `
                <div class="error">${message}</div>
            `;
        }

        // Show success message
        function showSuccess(message) {
            document.getElementById('error-container').innerHTML = `
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    ${message}
                </div>
            `;
            setTimeout(() => {
                document.getElementById('error-container').innerHTML = '';
            }, 3000);
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load notifications when page loads
        document.addEventListener('DOMContentLoaded', () => {
            loadNotifications(1);
        });
    </script>
</body>

</html>