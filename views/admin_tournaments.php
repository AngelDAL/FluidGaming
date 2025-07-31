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
    <title>Gestión de Torneos - Sistema de Puntos</title>
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

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255, 255, 255, 0.2);
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .search-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            margin-bottom: 1rem;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .tournaments-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        th,
        td {
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

        .status-scheduled {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #e2e3e5;
            color: #383d41;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .game-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
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

        .specs-container {
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        .spec-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }

        .spec-item input {
            flex: 1;
        }

        .spec-item button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 3px;
            cursor: pointer;
        }

        .add-spec-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 0.5rem;
            border-radius: 5px;
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

            .form-row {
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
                <a href="index.php?page=admin_events">Eventos</a>
                <a href="index.php?page=admin_tournaments" class="active">Torneos</a>
                <a href="#" onclick="logout()">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Gestión de Torneos</h2>
            <button class="btn" onclick="openCreateModal()">
                + Crear Torneo
            </button>
        </div>

        <div class="search-filters">
            <div class="search-row">
                <div class="form-group">
                    <label for="searchInput">Buscar torneos:</label>
                    <input type="text" id="searchInput" placeholder="Nombre del torneo...">
                </div>
                <div class="form-group">
                    <label for="eventFilter">Filtrar por evento:</label>
                    <select id="eventFilter">
                        <option value="">Todos los eventos</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="statusFilter">Filtrar por estado:</label>
                    <select id="statusFilter">
                        <option value="">Todos los estados</option>
                        <option value="scheduled">Programado</option>
                        <option value="active">Activo</option>
                        <option value="completed">Completado</option>
                    </select>
                </div>
            </div>
            <div class="search-row">
                <button class="btn btn-secondary" onclick="searchTournaments()">Buscar</button>
                <button class="btn btn-secondary" onclick="clearSearch()">Limpiar</button>
            </div>
        </div>

        <div class="tournaments-table">
            <div class="table-header">
                <h3>Lista de Torneos</h3>
                <span id="tournamentsCount">Cargando...</span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Evento</th>
                            <th>Fecha Programada</th>
                            <th>Puntos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tournamentsTableBody">
                        <tr>
                            <td colspan="7" class="loading">Cargando torneos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>

    <!-- Create/Edit Tournament Modal -->
    <div id="tournamentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Crear Torneo</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>

            <div id="modalMessage"></div>

            <form id="tournamentForm" enctype="multipart/form-data">
                <input type="hidden" id="tournamentId" name="tournament_id">
                <input type="hidden" id="csrfToken" name="csrf_token">

                <div class="form-row">
                    <div class="form-group">
                        <label for="tournamentName">Nombre del Torneo *</label>
                        <input type="text" id="tournamentName" name="name" required maxlength="191">
                    </div>

                    <div class="form-group">
                        <label for="eventSelect">Evento *</label>
                        <select id="eventSelect" name="event_id" required>
                            <option value="">Seleccionar evento...</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="scheduledTime">Fecha y Hora Programada *</label>
                        <input type="datetime-local" id="scheduledTime" name="scheduled_time" required>
                    </div>

                    <div class="form-group">
                        <label for="pointsReward">Puntos de Recompensa *</label>
                        <input type="number" id="pointsReward" name="points_reward" required min="1" max="10000">
                    </div>
                </div>

                <div class="form-group">
                    <label for="gameImage">Imagen del Juego</label>
                    <input type="file" id="gameImage" name="game_image" accept="image/*">
                    <img id="imagePreview" class="image-preview" style="display: none;">
                </div>

                <div class="form-group">
                    <label>Especificaciones del Torneo (Opcional)</label>
                    <div class="specs-container">
                        <div id="specificationsContainer">
                            <!-- Specifications will be added dynamically -->
                        </div>
                        <button type="button" class="add-spec-btn" onclick="addSpecification()">+ Agregar Especificación</button>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn" id="submitBtn">Crear Torneo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentSearch = '';
        let currentEventFilter = '';
        let currentStatusFilter = '';
        let csrfToken = '';
        let activeEvents = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCSRFToken();
            loadActiveEvents();
            loadTournaments();

            // Set minimum date to now
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('scheduledTime').min = now.toISOString().slice(0, 16);
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

        // Load active events for filters and form
        async function loadActiveEvents() {
            try {
                const response = await fetch('api/events.php?action=list&limit=100');
                const result = await response.json();

                if (result.success) {
                    activeEvents = result.data.events;
                    populateEventFilters();
                }
            } catch (error) {
                console.error('Error loading events:', error);
            }
        }

        // Populate event filters and form select
        function populateEventFilters() {
            const eventFilter = document.getElementById('eventFilter');
            const eventSelect = document.getElementById('eventSelect');

            // Clear existing options (except first)
            eventFilter.innerHTML = '<option value="">Todos los eventos</option>';
            eventSelect.innerHTML = '<option value="">Seleccionar evento...</option>';

            activeEvents.forEach(event => {
                const option1 = new Option(event.name, event.id);
                const option2 = new Option(event.name, event.id);
                eventFilter.appendChild(option1);
                eventSelect.appendChild(option2);
            });
        }

        // Load tournaments
        async function loadTournaments(page = 1, search = '', eventId = '', status = '') {
            try {
                let url = `api/tournaments.php?action=list&page=${page}&limit=10`;
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (eventId) url += `&event_id=${eventId}`;
                if (status) url += `&status=${status}`;

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    displayTournaments(result.data.tournaments);
                    displayPagination(result.data);
                    document.getElementById('tournamentsCount').textContent =
                        `${result.data.total} torneo(s) encontrado(s)`;
                } else {
                    showError('Error al cargar torneos: ' + (result.error || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error loading tournaments:', error);
                showError('Error de conexión al cargar torneos');
            }
        }

        // Display tournaments in table
        function displayTournaments(tournaments) {
            const tbody = document.getElementById('tournamentsTableBody');

            if (tournaments.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <h3>No se encontraron torneos</h3>
                            <p>No hay torneos que coincidan con los filtros aplicados.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = tournaments.map(tournament => {
                const scheduledDate = new Date(tournament.scheduled_time).toLocaleString('es-ES');
                const statusClass = `status-${tournament.status}`;
                const statusText = {
                    'scheduled': 'Programado',
                    'active': 'Activo',
                    'completed': 'Completado'
                } [tournament.status] || tournament.status;

                const imageHtml = tournament.game_image ?
                    `<img src="${tournament.game_image}" alt="Imagen del juego" class="game-image">` :
                    '<div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">Sin imagen</div>';

                return `
                    <tr>
                        <td>${imageHtml}</td>
                        <td>
                            <strong>${escapeHtml(tournament.name)}</strong>
                            ${tournament.specifications ? '<br><small>Con especificaciones</small>' : ''}
                        </td>
                        <td>${escapeHtml(tournament.event_name || 'Sin evento')}</td>
                        <td>${scheduledDate}</td>
                        <td><strong>${tournament.points_reward}</strong> puntos</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-small btn-warning" onclick="editTournament(${tournament.id})">
                                    Editar
                                </button>
                                <button class="btn btn-small btn-secondary" onclick="viewTournament(${tournament.id})">
                                    Ver
                                </button>
                                ${tournament.status === 'scheduled' ? `
                                    <button class="btn btn-small btn-success" onclick="updateTournamentStatus(${tournament.id}, 'active')">
                                        Activar
                                    </button>
                                ` : ''}
                                ${tournament.status === 'active' ? `
                                    <button class="btn btn-small btn-secondary" onclick="updateTournamentStatus(${tournament.id}, 'completed')">
                                        Completar
                                    </button>
                                ` : ''}
                                <button class="btn btn-small btn-danger" onclick="deleteTournament(${tournament.id}, '${escapeHtml(tournament.name)}')">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Display pagination
        function displayPagination(data) {
            const pagination = document.getElementById('pagination');

            if (data.total_pages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let paginationHtml = '';

            // Previous button
            if (data.page > 1) {
                paginationHtml += `<button onclick="changePage(${data.page - 1})">Anterior</button>`;
            }

            // Page numbers
            const startPage = Math.max(1, data.page - 2);
            const endPage = Math.min(data.total_pages, data.page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === data.page ? 'active' : '';
                paginationHtml += `<button class="${activeClass}" onclick="changePage(${i})">${i}</button>`;
            }

            // Next button
            if (data.page < data.total_pages) {
                paginationHtml += `<button onclick="changePage(${data.page + 1})">Siguiente</button>`;
            }

            pagination.innerHTML = paginationHtml;
        }

        // Change page
        function changePage(page) {
            currentPage = page;
            loadTournaments(currentPage, currentSearch, currentEventFilter, currentStatusFilter);
        }

        // Search tournaments
        function searchTournaments() {
            currentSearch = document.getElementById('searchInput').value.trim();
            currentEventFilter = document.getElementById('eventFilter').value;
            currentStatusFilter = document.getElementById('statusFilter').value;
            currentPage = 1;

            loadTournaments(currentPage, currentSearch, currentEventFilter, currentStatusFilter);
        }

        // Clear search
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('eventFilter').value = '';
            document.getElementById('statusFilter').value = '';

            currentSearch = '';
            currentEventFilter = '';
            currentStatusFilter = '';
            currentPage = 1;

            loadTournaments();
        }

        // Open create modal
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Crear Torneo';
            document.getElementById('submitBtn').textContent = 'Crear Torneo';
            document.getElementById('tournamentForm').reset();
            document.getElementById('tournamentId').value = '';
            document.getElementById('csrfToken').value = csrfToken;
            document.getElementById('imagePreview').style.display = 'none';

            // Clear specifications
            document.getElementById('specificationsContainer').innerHTML = '';

            // Set minimum date to now
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('scheduledTime').min = now.toISOString().slice(0, 16);

            clearModalMessage();
            document.getElementById('tournamentModal').style.display = 'block';
        }

        // Edit tournament
        async function editTournament(id) {
            try {
                const response = await fetch(`api/tournaments.php?action=get&id=${id}`);
                const result = await response.json();

                if (result.success) {
                    const tournament = result.tournament;

                    document.getElementById('modalTitle').textContent = 'Editar Torneo';
                    document.getElementById('submitBtn').textContent = 'Actualizar Torneo';
                    document.getElementById('tournamentId').value = tournament.id;
                    document.getElementById('tournamentName').value = tournament.name;
                    document.getElementById('eventSelect').value = tournament.event_id;
                    document.getElementById('scheduledTime').value = tournament.scheduled_time.replace(' ', 'T');
                    document.getElementById('pointsReward').value = tournament.points_reward;

                    // Set image preview if exists
                    if (tournament.game_image) {
                        const imagePreview = document.getElementById('imagePreview');
                        imagePreview.src = tournament.game_image;
                        imagePreview.style.display = 'block';
                    }

                    // Load specifications
                    loadSpecifications(tournament.specifications);

                    clearModalMessage();
                    document.getElementById('tournamentModal').style.display = 'block';
                } else {
                    showError('Error al cargar torneo: ' + (result.error || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error loading tournament:', error);
                showError('Error de conexión al cargar torneo');
            }
        }

        // View tournament details
        async function viewTournament(id) {
            try {
                const response = await fetch(`api/tournaments.php?action=get&id=${id}`);
                const result = await response.json();

                if (result.success) {
                    const tournament = result.tournament;

                    let specsHtml = '';
                    if (tournament.specifications && Object.keys(tournament.specifications).length > 0) {
                        specsHtml = '<h4>Especificaciones:</h4><ul>';
                        for (const [key, value] of Object.entries(tournament.specifications)) {
                            specsHtml += `<li><strong>${escapeHtml(key)}:</strong> ${escapeHtml(value)}</li>`;
                        }
                        specsHtml += '</ul>';
                    }

                    const imageHtml = tournament.game_image ?
                        `<img src="${tournament.game_image}" alt="Imagen del juego" style="max-width: 300px; border-radius: 5px; margin: 10px 0;">` :
                        '<p>Sin imagen</p>';

                    const scheduledDate = new Date(tournament.scheduled_time).toLocaleString('es-ES');
                    const statusText = {
                        'scheduled': 'Programado',
                        'active': 'Activo',
                        'completed': 'Completado'
                    } [tournament.status] || tournament.status;

                    const modalContent = `
                        <h3>${escapeHtml(tournament.name)}</h3>
                        <p><strong>Evento:</strong> ${escapeHtml(tournament.event_name || 'Sin evento')}</p>
                        <p><strong>Fecha Programada:</strong> ${scheduledDate}</p>
                        <p><strong>Puntos de Recompensa:</strong> ${tournament.points_reward}</p>
                        <p><strong>Estado:</strong> ${statusText}</p>
                        ${imageHtml}
                        ${specsHtml}
                    `;

                    showModal('Detalles del Torneo', modalContent);
                } else {
                    showError('Error al cargar torneo: ' + (result.error || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error loading tournament:', error);
                showError('Error de conexión al cargar torneo');
            }
        }

        // Delete tournament
        async function deleteTournament(id, name) {
            if (!confirm(`¿Estás seguro de que quieres eliminar el torneo "${name}"?`)) {
                return;
            }

            try {
                const response = await fetch(`api/tournaments.php?action=delete&id=${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `csrf_token=${encodeURIComponent(csrfToken)}`
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(result.message);
                    loadTournaments(currentPage, currentSearch, currentEventFilter, currentStatusFilter);
                } else {
                    showError('Error al eliminar torneo: ' + (result.errors ? result.errors.join(', ') : result.error));
                }
            } catch (error) {
                console.error('Error deleting tournament:', error);
                showError('Error de conexión al eliminar torneo');
            }
        }

        // Update tournament status
        async function updateTournamentStatus(id, status) {
            const statusNames = {
                'active': 'activar',
                'completed': 'completar'
            };

            if (!confirm(`¿Estás seguro de que quieres ${statusNames[status]} este torneo?`)) {
                return;
            }

            try {
                const response = await fetch(`api/tournaments.php?action=update-status&id=${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `status=${encodeURIComponent(status)}&csrf_token=${encodeURIComponent(csrfToken)}`
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(result.message);
                    loadTournaments(currentPage, currentSearch, currentEventFilter, currentStatusFilter);
                } else {
                    showError('Error al actualizar estado: ' + (result.errors ? result.errors.join(', ') : result.error));
                }
            } catch (error) {
                console.error('Error updating tournament status:', error);
                showError('Error de conexión al actualizar estado');
            }
        }

        // Load specifications into form
        function loadSpecifications(specifications) {
            const container = document.getElementById('specificationsContainer');
            container.innerHTML = '';

            if (specifications && typeof specifications === 'object') {
                for (const [key, value] of Object.entries(specifications)) {
                    addSpecification(key, value);
                }
            }
        }

        // Add specification field
        function addSpecification(key = '', value = '') {
            const container = document.getElementById('specificationsContainer');
            const specDiv = document.createElement('div');
            specDiv.className = 'spec-item';

            specDiv.innerHTML = `
                <input type="text" placeholder="Nombre (ej: Modalidad)" value="${escapeHtml(key)}" class="spec-key">
                <input type="text" placeholder="Valor (ej: 1vs1)" value="${escapeHtml(value)}" class="spec-value">
                <button type="button" onclick="removeSpecification(this)">×</button>
            `;

            container.appendChild(specDiv);
        }

        // Remove specification field
        function removeSpecification(button) {
            button.parentElement.remove();
        }

        // Handle form submission
        document.getElementById('tournamentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const tournamentId = document.getElementById('tournamentId').value;

            // Collect specifications
            const specifications = {};
            const specItems = document.querySelectorAll('.spec-item');
            specItems.forEach(item => {
                const key = item.querySelector('.spec-key').value.trim();
                const value = item.querySelector('.spec-value').value.trim();
                if (key && value) {
                    specifications[key] = value;
                }
            });

            if (Object.keys(specifications).length > 0) {
                formData.append('specifications', JSON.stringify(specifications));
            }

            const isEdit = tournamentId !== '';
            const url = isEdit ? `api/tournaments.php?action=update&id=${tournamentId}` : 'api/tournaments.php?action=create';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showModalSuccess(result.message);
                    setTimeout(() => {
                        closeModal();
                        loadTournaments(currentPage, currentSearch, currentEventFilter, currentStatusFilter);
                    }, 1500);
                } else {
                    showModalError(result.errors ? result.errors.join('<br>') : result.error);
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                showModalError('Error de conexión al guardar torneo');
            }
        });

        // Handle image preview
        document.getElementById('gameImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Close modal
        function closeModal() {
            document.getElementById('tournamentModal').style.display = 'none';
        }

        // Show modal with custom content
        function showModal(title, content) {
            const modal = document.getElementById('tournamentModal');
            const modalContent = modal.querySelector('.modal-content');

            modalContent.innerHTML = `
                <div class="modal-header">
                    <h3>${title}</h3>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <div style="padding: 1rem;">
                    ${content}
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cerrar</button>
                </div>
            `;

            modal.style.display = 'block';
        }

        // Clear modal message
        function clearModalMessage() {
            document.getElementById('modalMessage').innerHTML = '';
        }

        // Show modal success message
        function showModalSuccess(message) {
            document.getElementById('modalMessage').innerHTML =
                `<div class="success-message">${message}</div>`;
        }

        // Show modal error message
        function showModalError(message) {
            document.getElementById('modalMessage').innerHTML =
                `<div class="error-message">${message}</div>`;
        }

        // Show success message
        function showSuccess(message) {
            // You can implement a toast notification system here
            alert(message);
        }

        // Show error message
        function showError(message) {
            // You can implement a toast notification system here
            alert(message);
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (typeof text !== 'string') return text;
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        }

        // Logout function
        function logout() {
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                window.location.href = 'index.php?action=logout';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('tournamentModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Handle Enter key in search input
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTournaments();
            }
        });
    </script>
</body>

</html>