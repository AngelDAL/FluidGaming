<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and can assign points
if (!isLoggedIn() || !canAssignPoints()) {
    header('Location: ../index.php?page=login');
    exit();
}

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Puntos - Gamersland Arena</title>
    <link rel="stylesheet" href="styles.css">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Estilos específicos para assign_points */
        .assign-points-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .page-header p {
            font-size: 1.2rem;
            color: #94a3b8;
            margin: 0;
        }

        .form-section {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .form-section:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        }

        .form-section {
            animation: fadeInUp 0.6s ease forwards;
        }

        .page-header {
            animation: fadeInUp 0.4s ease forwards;
        }

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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #e2e8f0;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(15, 15, 35, 0.5);
            color: #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            background: rgba(15, 15, 35, 0.8);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .search-container {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-top: none;
            border-radius: 0 0 12px 12px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .search-results::-webkit-scrollbar {
            width: 8px;
        }

        .search-results::-webkit-scrollbar-track {
            background: rgba(15, 15, 35, 0.5);
            border-radius: 4px;
        }

        .search-results::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 4px;
        }

        .search-results::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        .search-result-item {
            padding: 1rem;
            cursor: pointer;
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            color: #e2e8f0;
        }

        .search-result-item:hover {
            background: rgba(102, 126, 234, 0.15);
            transform: translateX(5px);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .user-info {
            flex: 1;
        }

        .user-nickname {
            font-weight: 600;
            color: #e2e8f0;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .user-points {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .selected-user {
            background: rgba(102, 126, 234, 0.15);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: none;
            transition: all 0.3s ease;
        }

        .selected-user .user-info {
            color: #e2e8f0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #94a3b8;
        }

        .spinner {
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            z-index: 2000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(102, 126, 234, 0.3);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .modal-header p {
            color: #94a3b8;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .back-link:hover {
            background: rgba(102, 126, 234, 0.15);
            transform: translateX(-5px);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border: 1px solid;
        }

        .alert-success {
            background: rgba(46, 213, 115, 0.15);
            color: #2ed573;
            border-color: rgba(46, 213, 115, 0.3);
        }

        .alert-error {
            background: rgba(255, 71, 87, 0.15);
            color: #ff4757;
            border-color: rgba(255, 71, 87, 0.3);
        }

        .alert-info {
            background: rgba(102, 126, 234, 0.15);
            color: #667eea;
            border-color: rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            .assign-points-container {
                padding: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .form-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar similar al dashboard -->
    <nav class="navbar">
        <div class="container">
            <h1><i class="fa-solid fa-gamepad"></i> Gamersland Arena</h1>
            <div class="navbar-controls">
                <div class="user-greeting-mini">
                    ¡Hola, <?php echo htmlspecialchars($current_user['nickname']); ?>!
                </div>
            </div>
        </div>
    </nav>

    <div class="container assign-points-container">
        <div class="page-header">
            <h1><i class="fa-solid fa-star"></i> Asignar Puntos</h1>
            <p>Asigna puntos a los usuarios por su participación en torneos y actividades</p>
        </div>

        <a href="../index.php?page=dashboard" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div id="alerts"></div>

        <div class="form-section">
            <form id="assignPointsForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" id="selected_user_id" name="user_id" value="">

                <div class="form-group">
                    <label for="user_search">
                        <i class="fa-solid fa-search"></i> Buscar Usuario por Nickname
                    </label>
                    <div class="search-container">
                        <input type="text"
                            id="user_search"
                            class="form-control"
                            placeholder="Escribe el nickname del usuario..."
                            autocomplete="off">
                        <div id="search_results" class="search-results"></div>
                    </div>
                </div>

                <div id="selected_user_info" class="selected-user">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="user-avatar" id="selected_avatar" style="width: 50px; height: 50px; font-size: 1.5rem;">
                            <!-- Avatar will be populated by JavaScript -->
                        </div>
                        <div class="user-info">
                            <div class="user-nickname" id="selected_nickname" style="font-size: 1.2rem; margin-bottom: 0.25rem;"></div>
                            <div class="user-points" id="selected_points"></div>
                        </div>
                        <div style="margin-left: auto;">
                            <i class="fa-solid fa-check-circle" style="color: #2ed573; font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="points">
                            <i class="fa-solid fa-bolt"></i> Puntos a Asignar
                        </label>
                        <input type="number"
                            id="points"
                            name="points"
                            class="form-control"
                            min="1"
                            max="1000"
                            placeholder="Ej: 50"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="source">
                            <i class="fa-solid fa-tag"></i> Fuente de Puntos
                        </label>
                        <select id="source" name="source" class="form-control" required>
                            <option value="">Seleccionar fuente...</option>
                            <option value="tournament">🏆 Torneo</option>
                            <option value="challenge">⚔️ Challenge/Actividad</option>
                            <option value="bonus">🎁 Bonificación</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="tournament_group" style="display: none;">
                    <label for="tournament_id">
                        <i class="fa-solid fa-trophy"></i> Torneo (Opcional)
                    </label>
                    <select id="tournament_id" name="tournament_id" class="form-control">
                        <option value="">Seleccionar torneo...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">
                        <i class="fa-solid fa-note-sticky"></i> Notas (Opcional)
                    </label>
                    <textarea id="notes"
                        name="notes"
                        class="form-control"
                        rows="3"
                        placeholder="Descripción adicional sobre la asignación de puntos..."></textarea>
                </div>

                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Procesando asignación de puntos...</p>
                </div>

                <button type="submit" class="btn primary" id="submit_btn">
                    <i class="fa-solid fa-star"></i> Asignar Puntos
                </button>
            </form>
        </div>
    </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Asignación de Puntos</h3>
                <p>¿Estás seguro de que deseas asignar estos puntos?</p>
            </div>

            <div id="confirmation_details">
                <!-- Details will be populated by JavaScript -->
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn dark" onclick="closeConfirmationModal()">
                    <i class="fa-solid fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn primary" onclick="confirmAssignment()">
                    <i class="fa-solid fa-check"></i> Confirmar Asignación
                </button>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;
        let selectedUser = null;
        let formData = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadTournaments();
            setupEventListeners();
            checkActiveEvents();
        });

        function setupEventListeners() {
            // User search functionality
            document.getElementById('user_search').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 2) {
                    hideSearchResults();
                    return;
                }

                searchTimeout = setTimeout(() => {
                    searchUsers(query);
                }, 300);
            });

            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-container')) {
                    hideSearchResults();
                }
            });

            // Show/hide tournament selection based on source
            document.getElementById('source').addEventListener('change', function() {
                const tournamentGroup = document.getElementById('tournament_group');
                if (this.value === 'tournament') {
                    tournamentGroup.style.display = 'block';
                } else {
                    tournamentGroup.style.display = 'none';
                    document.getElementById('tournament_id').value = '';
                }
            });

            // Form submission
            document.getElementById('assignPointsForm').addEventListener('submit', function(e) {
                e.preventDefault();
                showConfirmationModal();
            });
        }

        // Precargar usuarios al cargar la página
        let allUsers = [];
        fetch('../api/points.php?action=all-users')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allUsers = data.users;
                }
            });

        function searchUsers(query) {
            // Filtrar usuarios localmente
            const filtered = allUsers.filter(user => user.nickname.toLowerCase().includes(query.toLowerCase()));
            displaySearchResults(filtered);
        }

        function displaySearchResults(users) {
            const resultsContainer = document.getElementById('search_results');
            if (users.length === 0) {
                resultsContainer.innerHTML = '<div class="search-result-item">No se encontraron usuarios</div>';
            } else {
                resultsContainer.innerHTML = users.map(user => `
                    <div class="search-result-item" tabindex="0" onmousedown="selectUser(${user.id}, '${user.nickname.replace(/'/g, "\\'")}', ${user.total_points})">
                        <div class="user-avatar">
                            ${user.profile_image ? `<img src="${user.profile_image}" alt="${user.nickname}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : user.nickname.charAt(0).toUpperCase()}
                        </div>
                        <div class="user-info">
                            <div class="user-nickname">${user.nickname}</div>
                            <div class="user-points">${user.total_points} puntos</div>
                        </div>
                    </div>
                `).join('');
            }
            // Mostrar el dropdown justo debajo del input
            const input = document.getElementById('user_search');
            const rect = input.getBoundingClientRect();
            resultsContainer.style.position = 'absolute';
            resultsContainer.style.top = (input.offsetTop + input.offsetHeight) + 'px';
            resultsContainer.style.left = input.offsetLeft + 'px';
            resultsContainer.style.width = input.offsetWidth + 'px';
            resultsContainer.style.display = 'block';
        }

        function hideSearchResults() {
            document.getElementById('search_results').style.display = 'none';
        }

        function selectUser(userId, nickname, totalPoints) {
            selectedUser = {
                id: userId,
                nickname: nickname,
                total_points: totalPoints
            };

            // Update form
            document.getElementById('selected_user_id').value = userId;
            document.getElementById('user_search').value = nickname;

            // Show selected user info
            document.getElementById('selected_nickname').textContent = nickname;
            document.getElementById('selected_points').textContent = `Puntos actuales: ${totalPoints}`;
            document.getElementById('selected_avatar').textContent = nickname.charAt(0).toUpperCase();
            document.getElementById('selected_user_info').style.display = 'block';

            // Debug: Verify the hidden input is set
            console.log('Selected user ID set to:', userId);
            console.log('Hidden input value:', document.getElementById('selected_user_id').value);

            hideSearchResults();
        }

        function loadTournaments() {
            fetch('../api/points.php?action=tournaments')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('tournament_id');
                        select.innerHTML = '<option value="">Seleccionar torneo...</option>';
                        data.tournaments.forEach(tournament => {
                            const option = document.createElement('option');
                            option.value = tournament.id;
                            option.textContent = `${tournament.name} (${tournament.points_reward} pts) - ${tournament.event_name}`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading tournaments:', error);
                });
        }

        function checkActiveEvents() {
            fetch('../api/points.php?action=validate')
                .then(response => response.json())
                .then(data => {
                    if (data.success && !data.can_assign_points) {
                        showAlert(data.message, 'error');
                        document.getElementById('submit_btn').disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error checking active events:', error);
                });
        }

        function showConfirmationModal() {
            if (!selectedUser) {
                showAlert('Por favor selecciona un usuario', 'error');
                return;
            }

            const points = document.getElementById('points').value;
            const source = document.getElementById('source').value;
            const tournamentId = document.getElementById('tournament_id').value;
            const notes = document.getElementById('notes').value;

            if (!points || !source) {
                showAlert('Por favor completa todos los campos requeridos', 'error');
                return;
            }

            if (parseInt(points) <= 0) {
                showAlert('Los puntos deben ser mayor a 0', 'error');
                return;
            }

            if (!selectedUser.id) {
                showAlert('Error: Usuario no seleccionado correctamente', 'error');
                return;
            }

            // Ensure the hidden input has the correct value
            const hiddenInput = document.getElementById('selected_user_id');
            if (!hiddenInput.value || hiddenInput.value === '0') {
                hiddenInput.value = selectedUser.id;
                console.log('Fixed hidden input value to:', selectedUser.id);
            }

            // Store form data
            formData = new FormData(document.getElementById('assignPointsForm'));

            // Ensure user_id is properly set
            if (!formData.get('user_id') || formData.get('user_id') === '0') {
                formData.set('user_id', selectedUser.id);
            }

            // Debug: Log form data
            console.log('Form data being sent:');
            console.log('user_id:', formData.get('user_id'));
            console.log('points:', formData.get('points'));
            console.log('source:', formData.get('source'));
            console.log('tournament_id:', formData.get('tournament_id'));
            console.log('notes:', formData.get('notes'));
            console.log('csrf_token:', formData.get('csrf_token'));

            // Update confirmation details
            const tournamentText = tournamentId ?
                ` (Torneo: ${document.getElementById('tournament_id').selectedOptions[0].textContent})` : '';

            document.getElementById('confirmation_details').innerHTML = `
                <div class="alert alert-info">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div class="user-avatar" style="width: 50px; height: 50px; font-size: 1.5rem;">
                            ${selectedUser.nickname.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 1.2rem;">${selectedUser.nickname} (ID: ${selectedUser.id})</div>
                            <div style="color: #94a3b8;">Puntos actuales: ${selectedUser.total_points}</div>
                        </div>
                    </div>
                    <div style="display: grid; gap: 0.5rem;">
                        <div><i class="fa-solid fa-bolt" style="color: #667eea; margin-right: 0.5rem;"></i><strong>Puntos a asignar:</strong> ${points}</div>
                        <div><i class="fa-solid fa-tag" style="color: #667eea; margin-right: 0.5rem;"></i><strong>Fuente:</strong> ${source}${tournamentText}</div>
                        <div><i class="fa-solid fa-arrow-up" style="color: #2ed573; margin-right: 0.5rem;"></i><strong>Puntos después:</strong> ${parseInt(selectedUser.total_points) + parseInt(points)}</div>
                        ${notes ? `<div><i class="fa-solid fa-note-sticky" style="color: #667eea; margin-right: 0.5rem;"></i><strong>Notas:</strong> ${notes}</div>` : ''}
                    </div>
                </div>
            `;

            document.getElementById('confirmationModal').style.display = 'block';
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
            formData = null;
        }

        function confirmAssignment() {
            if (!formData) {
                showAlert('❌ Error: No hay datos del formulario', 'error');
                return;
            }

            // Store a reference to formData before closing modal
            const dataToSend = formData;

            // Debug: Log what we're sending
            console.log('Sending assignment request...');
            for (let [key, value] of dataToSend.entries()) {
                console.log(key, value);
            }

            closeConfirmationModal();

            // Show loading
            document.getElementById('loading').style.display = 'block';
            document.getElementById('submit_btn').disabled = true;

            fetch('../api/points.php?action=assign', {
                    method: 'POST',
                    body: dataToSend
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);

                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('submit_btn').disabled = false;

                    if (data.success) {
                        showAlert(`🎉 ¡Puntos asignados exitosamente! ${selectedUser.nickname} ahora tiene ${data.user_total_points} puntos.`, 'success');

                        // Reset form
                        document.getElementById('assignPointsForm').reset();
                        document.getElementById('selected_user_info').style.display = 'none';
                        document.getElementById('tournament_group').style.display = 'none';
                        selectedUser = null;

                        // Regenerate CSRF token
                        document.querySelector('input[name="csrf_token"]').value = '<?php echo generateCSRFToken(); ?>';
                    } else {
                        if (data.errors && Array.isArray(data.errors)) {
                            showAlert('❌ Errores: ' + data.errors.join(', '), 'error');
                        } else {
                            showAlert('❌ Error: ' + (data.error || 'Error desconocido'), 'error');
                        }
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('submit_btn').disabled = false;
                    console.error('Network error:', error);
                    showAlert('❌ Error de conexión al asignar puntos', 'error');
                });
        }

        function showAlert(message, type) {
            const alertsContainer = document.getElementById('alerts');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;

            alertsContainer.innerHTML = '';
            alertsContainer.appendChild(alertDiv);

            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }

            // Scroll to top to show alert
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>