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
    <link rel="stylesheet" href="../views/styles.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <nav class="navbar" style="padding: 0.7rem 0;">
        <div class="container" style="display: flex; align-items: center; justify-content: flex-start; gap: 1.2rem;">
            <a href="../" class="btn dark" style="display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; min-width: 120px; justify-content: center;">
                <i class="fa-solid fa-arrow-left"></i> Regresar
            </a>
            <span style="font-size: 1.2rem; font-weight: 700; color: #e2e8f0; letter-spacing: 0.5px;">Notificaciones</span>
        </div>
    </nav>

    <div class="container" style="max-width: 800px; margin: 0 auto;">
        <!-- Encabezado de la sección -->
        <div class="section-card" style="margin-bottom: 2rem; padding: 1.5rem 2rem 1.2rem 2rem; display: flex; flex-direction: column; gap: 0.5rem; align-items: center;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-bell" style="font-size: 1.3rem; color: #fff;"></i>
                </div>
                <h2 style="margin: 0; font-size: 1.2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; letter-spacing: 0.5px;">Notificaciones</h2>
            </div>
            <div class="notification-actions" style="margin-top: 0.5rem; display: flex; gap: 1rem; justify-content: center;">
                <button class="btn dark" style="min-width: 44px; justify-content: center;" onclick="markAllAsRead()" title="Marcar todas como leídas">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </button>
                <button class="btn primary" style="min-width: 44px; justify-content: center;" onclick="refreshNotifications()" title="Actualizar">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <!-- Mensajes de error o éxito -->
        <div id="error-container" style="margin-bottom: 1.2rem;"></div>

        <!-- Lista de notificaciones -->
        <div class="section-card" style="padding:0; background: rgba(15, 15, 35, 0.7); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
            <div id="loading" class="loading-message">
                <p>Cargando notificaciones...</p>
            </div>
            <div id="notifications-list"></div>
        </div>

        <!-- Paginación -->
        <div id="pagination" class="pagination" style="display: none; margin-top: 2rem;">
            <button id="prev-btn" class="btn dark" onclick="loadPreviousPage()"><i class="fa-solid fa-chevron-left"></i> Anterior</button>
            <span id="page-info" style="font-weight: 600; color: #667eea;">Página 1</span>
            <button id="next-btn" class="btn dark" onclick="loadNextPage()">Siguiente <i class="fa-solid fa-chevron-right"></i></button>
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
                        <i class="fa-regular fa-bell-slash empty-icon"></i>
                        <div class="empty-title">No tienes notificaciones</div>
                        <div class="empty-desc">Cuando recibas notificaciones aparecerán aquí</div>
                    </div>
                `;
                return;
            }
            container.innerHTML = notifications.map(notification => `
                <div class="event-card notification-item${!notification.is_read ? ' unread' : ''}"
                     style="display: flex; align-items: flex-start; gap: 1.5rem; cursor: pointer; border-left: ${!notification.is_read ? '4px solid #667eea' : 'none'}; background: ${!notification.is_read ? 'rgba(102,126,234,0.07)' : 'rgba(15,15,35,0.6)'};"
                     onclick="markAsRead(${notification.id})">
                    <div class="notification-icon" style="font-size:2rem; min-width:48px; min-height:48px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:rgba(102,126,234,0.12);">
                        ${getNotificationIcon(notification.type)}
                    </div>
                    <div class="notification-content" style="flex:1;">
                        <div class="notification-title" style="font-weight:700; color:#e2e8f0; margin-bottom:0.25rem; font-size:1.1rem;">${escapeHtml(notification.title)}</div>
                        <div class="notification-message" style="color:#94a3b8; font-size:0.98rem; margin-bottom:0.25rem;">${escapeHtml(notification.message)}</div>
                        <div class="notification-time" style="color:#667eea; font-size:0.85rem;"><i class="fa-regular fa-clock"></i> ${formatTime(notification.created_at)}</div>
                    </div>
                    ${!notification.is_read ? '<div class="unread-badge" style="background:#ff4757; color:white; width:20px; height:20px; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:0.8rem; margin-left:0.5rem;"><i class=\'fa-solid fa-circle\'></i></div>' : ''}
                </div>
            `).join('');
        }

        // Get notification icon based on type (FontAwesome)
        function getNotificationIcon(type) {
            switch (type) {
                case 'tournament':
                    return '<i class="fa-solid fa-trophy" style="color:#ffd700;"></i>';
                case 'points':
                    return '<i class="fa-solid fa-star" style="color:#fbbf24;"></i>';
                case 'event':
                    return '<i class="fa-solid fa-calendar-check" style="color:#38bdf8;"></i>';
                case 'system':
                    return '<i class="fa-solid fa-bell" style="color:#667eea;"></i>';
                default:
                    return '<i class="fa-solid fa-bullhorn" style="color:#764ba2;"></i>';
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