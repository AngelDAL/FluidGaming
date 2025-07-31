<?php
// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php?page=login');
    exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Eventos - Sistema de Puntos</title>
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

        .nav-links a:hover {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h2 {
            color: #333;
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

        .btn-danger {
            background: #dc3545;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .search-row {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .events-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e1e5e9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-upcoming {
            background: #fff3cd;
            color: #856404;
        }

        .status-ended {
            background: #e2e3e5;
            color: #383d41;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }

        .modal-header h3 {
            color: #333;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #3c3;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #e1e5e9;
            background: white;
            cursor: pointer;
            border-radius: 5px;
        }

        .pagination button:hover {
            background: #f8f9fa;
        }

        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .search-row {
                flex-direction: column;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Sistema de Puntos - Admin</h1>
            <div class="nav-links">
                <a href="index.php?page=dashboard">Dashboard</a>
                <a href="index.php?page=admin_events" class="active">Eventos</a>
                <a href="#" onclick="logout()">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Gestión de Eventos</h2>
            <button class="btn" onclick="openCreateModal()">
                + Crear Evento
            </button>
        </div>

        <div class="search-filters">
            <div class="search-row">
                <div class="form-group">
                    <label for="searchInput">Buscar eventos:</label>
                    <input type="text" id="searchInput" placeholder="Nombre o descripción...">
                </div>
                <button class="btn btn-secondary" onclick="searchEvents()">Buscar</button>
                <button class="btn btn-secondary" onclick="clearSearch()">Limpiar</button>
            </div>
        </div>

        <div class="events-table">
            <div class="table-header">
                <h3>Lista de Eventos</h3>
                <span id="eventsCount">Cargando...</span>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                            <th>Creado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="eventsTableBody">
                        <tr>
                            <td colspan="7" class="loading">Cargando eventos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>

    <!-- Create/Edit Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Crear Evento</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <div id="modalMessage"></div>
            
            <form id="eventForm">
                <input type="hidden" id="eventId" name="event_id">
                <input type="hidden" id="csrfToken" name="csrf_token">
                
                <div class="form-group">
                    <label for="eventName">Nombre del Evento *</label>
                    <input type="text" id="eventName" name="name" required maxlength="191">
                </div>
                
                <div class="form-group">
                    <label for="eventDescription">Descripción</label>
                    <textarea id="eventDescription" name="description" rows="3" style="width: 100%; padding: 0.75rem; border: 2px solid #e1e5e9; border-radius: 5px; font-family: inherit; resize: vertical;"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="startDate">Fecha y Hora de Inicio *</label>
                        <input type="datetime-local" id="startDate" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="endDate">Fecha y Hora de Fin *</label>
                        <input type="datetime-local" id="endDate" name="end_date" required>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn" id="submitBtn">Crear Evento</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentSearch = '';
        let csrfToken = '';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCSRFToken();
            loadEvents();
            
            // Set minimum date to now
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('startDate').min = now.toISOString().slice(0, 16);
            document.getElementById('endDate').min = now.toISOString().slice(0, 16);
        });

        // Load CSRF token
        async function loadCSRFToken() {
            try {
                const response = await fetch('api/users.php?action=session');
                const result = await response.json();
                if (result.success && result.csrf_token) {
                    csrfToken = result.csrf_token;
                    document.getElementById('csrfToken').value = csrfToken;
                }
            } catch (error) {
                console.error('Error loading CSRF token:', error);
            }
        }

        // Load events
        async function loadEvents(page = 1, search = '') {
            try {
                const url = `api/events.php?action=list&page=${page}&limit=10&search=${encodeURIComponent(search)}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    displayEvents(result.data.events);
                    displayPagination(result.data);
                    document.getElementById('eventsCount').textContent = 
                        `${result.data.total} evento(s) encontrado(s)`;
                } else {
                    showError('Error al cargar eventos: ' + (result.error || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error loading events:', error);
                showError('Error de conexión al cargar eventos');
            }
        }

        // Display events in table
        function displayEvents(events) {
            const tbody = document.getElementById('eventsTableBody');
            
            if (events.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <h3>No hay eventos</h3>
                            <p>No se encontraron eventos. Crea el primer evento.</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = events.map(event => {
                const status = getEventStatus(event);
                return `
                    <tr>
                        <td><strong>${escapeHtml(event.name)}</strong></td>
                        <td>${escapeHtml(event.description || 'Sin descripción')}</td>
                        <td>${formatDateTime(event.start_date)}</td>
                        <td>${formatDateTime(event.end_date)}</td>
                        <td><span class="status-badge status-${status.class}">${status.text}</span></td>
                        <td>${escapeHtml(event.created_by_name || 'Desconocido')}</td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-small btn-secondary" onclick="editEvent(${event.id})">
                                    Editar
                                </button>
                                <button class="btn btn-small ${event.is_active ? 'btn-secondary' : 'btn-success'}" 
                                        onclick="toggleEventStatus(${event.id}, ${event.is_active})">
                                    ${event.is_active ? 'Desactivar' : 'Activar'}
                                </button>
                                <button class="btn btn-small btn-danger" onclick="deleteEvent(${event.id}, '${escapeHtml(event.name)}')">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Get event status
        function getEventStatus(event) {
            const now = new Date();
            const start = new Date(event.start_date);
            const end = new Date(event.end_date);
            
            if (!event.is_active) {
                return { class: 'inactive', text: 'Inactivo' };
            }
            
            if (now < start) {
                return { class: 'upcoming', text: 'Próximo' };
            } else if (now >= start && now <= end) {
                return { class: 'active', text: 'Activo' };
            } else {
                return { class: 'ended', text: 'Finalizado' };
            }
        }

        // Display pagination
        function displayPagination(data) {
            const pagination = document.getElementById('pagination');
            
            if (data.total_pages <= 1) {
                pagination.innerHTML = '';
                return;
            }
            
            let html = '';
            
            // Previous button
            html += `<button ${data.page <= 1 ? 'disabled' : ''} onclick="changePage(${data.page - 1})">Anterior</button>`;
            
            // Page numbers
            for (let i = Math.max(1, data.page - 2); i <= Math.min(data.total_pages, data.page + 2); i++) {
                html += `<button class="${i === data.page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
            }
            
            // Next button
            html += `<button ${data.page >= data.total_pages ? 'disabled' : ''} onclick="changePage(${data.page + 1})">Siguiente</button>`;
            
            pagination.innerHTML = html;
        }

        // Change page
        function changePage(page) {
            currentPage = page;
            loadEvents(page, currentSearch);
        }

        // Search events
        function searchEvents() {
            currentSearch = document.getElementById('searchInput').value;
            currentPage = 1;
            loadEvents(1, currentSearch);
        }

        // Clear search
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            currentSearch = '';
            currentPage = 1;
            loadEvents(1, '');
        }

        // Open create modal
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Crear Evento';
            document.getElementById('submitBtn').textContent = 'Crear Evento';
            document.getElementById('eventForm').reset();
            document.getElementById('eventId').value = '';
            document.getElementById('csrfToken').value = csrfToken;
            clearModalMessage();
            
            // Set minimum dates
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('startDate').min = now.toISOString().slice(0, 16);
            document.getElementById('endDate').min = now.toISOString().slice(0, 16);
            
            document.getElementById('eventModal').style.display = 'block';
        }

        // Edit event
        async function editEvent(eventId) {
            try {
                const response = await fetch(`api/events.php?action=get&id=${eventId}`);
                const result = await response.json();
                
                if (result.success) {
                    const event = result.event;
                    
                    document.getElementById('modalTitle').textContent = 'Editar Evento';
                    document.getElementById('submitBtn').textContent = 'Actualizar Evento';
                    document.getElementById('eventId').value = event.id;
                    document.getElementById('eventName').value = event.name;
                    document.getElementById('eventDescription').value = event.description || '';
                    document.getElementById('startDate').value = formatDateTimeForInput(event.start_date);
                    document.getElementById('endDate').value = formatDateTimeForInput(event.end_date);
                    document.getElementById('csrfToken').value = csrfToken;
                    clearModalMessage();
                    
                    document.getElementById('eventModal').style.display = 'block';
                } else {
                    showError('Error al cargar evento: ' + (result.error || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error loading event:', error);
                showError('Error de conexión al cargar evento');
            }
        }

        // Delete event
        async function deleteEvent(eventId, eventName) {
            if (!confirm(`¿Estás seguro de que quieres eliminar el evento "${eventName}"?`)) {
                return;
            }
            
            try {
                const response = await fetch(`api/events.php?action=delete&id=${eventId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        csrf_token: csrfToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    loadEvents(currentPage, currentSearch);
                } else {
                    showError('Error al eliminar evento: ' + (result.errors ? result.errors.join(', ') : result.error));
                }
            } catch (error) {
                console.error('Error deleting event:', error);
                showError('Error de conexión al eliminar evento');
            }
        }

        // Toggle event status
        async function toggleEventStatus(eventId, currentStatus) {
            const action = currentStatus ? 'desactivar' : 'activar';
            
            if (!confirm(`¿Estás seguro de que quieres ${action} este evento?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch(`api/events.php?action=toggle-active&id=${eventId}`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    loadEvents(currentPage, currentSearch);
                } else {
                    showError('Error al cambiar estado: ' + (result.errors ? result.errors.join(', ') : result.error));
                }
            } catch (error) {
                console.error('Error toggling event status:', error);
                showError('Error de conexión al cambiar estado');
            }
        }

        // Handle form submission
        document.getElementById('eventForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const eventId = document.getElementById('eventId').value;
            const isEdit = eventId !== '';
            
            // Client-side validation
            if (!validateEventForm()) {
                return;
            }
            
            try {
                const url = isEdit ? `api/events.php?action=update&id=${eventId}` : 'api/events.php?action=create';
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showModalSuccess(result.message);
                    setTimeout(() => {
                        closeModal();
                        loadEvents(currentPage, currentSearch);
                    }, 1500);
                } else {
                    showModalError('Error: ' + (result.errors ? result.errors.join(', ') : result.error));
                }
            } catch (error) {
                console.error('Error saving event:', error);
                showModalError('Error de conexión al guardar evento');
            }
        });

        // Validate event form
        function validateEventForm() {
            const name = document.getElementById('eventName').value.trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!name) {
                showModalError('El nombre del evento es requerido');
                return false;
            }
            
            if (!startDate || !endDate) {
                showModalError('Las fechas de inicio y fin son requeridas');
                return false;
            }
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            const now = new Date();
            
            if (start < now) {
                showModalError('La fecha de inicio debe ser posterior a la fecha actual');
                return false;
            }
            
            if (end <= start) {
                showModalError('La fecha de fin debe ser posterior a la fecha de inicio');
                return false;
            }
            
            // Check minimum duration (1 hour)
            const diffMs = end.getTime() - start.getTime();
            const diffHours = diffMs / (1000 * 60 * 60);
            if (diffHours < 1) {
                showModalError('El evento debe durar al menos 1 hora');
                return false;
            }
            
            return true;
        }

        // Update end date minimum when start date changes
        document.getElementById('startDate').addEventListener('change', function() {
            const startDate = this.value;
            if (startDate) {
                const start = new Date(startDate);
                start.setHours(start.getHours() + 1); // Minimum 1 hour duration
                start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
                document.getElementById('endDate').min = start.toISOString().slice(0, 16);
            }
        });

        // Close modal
        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
            clearModalMessage();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('eventModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatDateTimeForInput(dateString) {
            const date = new Date(dateString);
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            return date.toISOString().slice(0, 16);
        }

        function showError(message) {
            // You can implement a toast notification system here
            alert('Error: ' + message);
        }

        function showSuccess(message) {
            // You can implement a toast notification system here
            alert('Éxito: ' + message);
        }

        function showModalError(message) {
            document.getElementById('modalMessage').innerHTML = `
                <div class="error-message">${message}</div>
            `;
        }

        function showModalSuccess(message) {
            document.getElementById('modalMessage').innerHTML = `
                <div class="success-message">${message}</div>
            `;
        }

        function clearModalMessage() {
            document.getElementById('modalMessage').innerHTML = '';
        }

        // Logout function
        async function logout() {
            try {
                const response = await fetch('api/users.php?action=logout', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'index.php?page=login';
                } else {
                    alert('Error al cerrar sesión');
                }
            } catch (error) {
                console.error('Error during logout:', error);
                window.location.href = 'index.php?page=login';
            }
        }

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchEvents();
            }
        });
    </script>
</body>
</html>