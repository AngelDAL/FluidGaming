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
    <title>Asignar Puntos - Sistema de Torneos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            border-left: 5px solid #4facfe;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        .search-container {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e1e5e9;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-result-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-result-item:hover {
            background: #f8f9fa;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #4facfe;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-info {
            flex: 1;
        }

        .user-nickname {
            font-weight: 600;
            color: #333;
        }

        .user-points {
            font-size: 0.9em;
            color: #666;
        }

        .selected-user {
            background: #e8f4fd;
            border: 2px solid #4facfe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #cce7ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4facfe;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4facfe;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Asignar Puntos</h1>
            <p>Asigna puntos a los usuarios por su participación en torneos y actividades</p>
        </div>

        <div class="content">
            <a href="index.php?page=dashboard" class="back-link">← Volver al Dashboard</a>

            <div id="alerts"></div>

            <div class="form-section">
                <form id="assignPointsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" id="selected_user_id" name="user_id" value="">

                    <div class="form-group">
                        <label for="user_search">Buscar Usuario por Nickname</label>
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
                        <div class="user-info">
                            <div class="user-nickname" id="selected_nickname"></div>
                            <div class="user-points" id="selected_points"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="points">Puntos a Asignar</label>
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
                            <label for="source">Fuente de Puntos</label>
                            <select id="source" name="source" class="form-control" required>
                                <option value="">Seleccionar fuente...</option>
                                <option value="tournament">Torneo</option>
                                <option value="challenge">Challenge/Actividad</option>
                                <option value="bonus">Bonificación</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="tournament_group" style="display: none;">
                        <label for="tournament_id">Torneo (Opcional)</label>
                        <select id="tournament_id" name="tournament_id" class="form-control">
                            <option value="">Seleccionar torneo...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notas (Opcional)</label>
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

                    <button type="submit" class="btn btn-primary" id="submit_btn">
                        Asignar Puntos
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
                <button type="button" class="btn btn-secondary" onclick="closeConfirmationModal()">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmAssignment()">
                    Confirmar Asignación
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

        function searchUsers(query) {
            fetch(`api/points.php?action=search-users&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySearchResults(data.users);
                    } else {
                        showAlert('Error al buscar usuarios: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error de conexión al buscar usuarios', 'error');
                });
        }

        function displaySearchResults(users) {
            const resultsContainer = document.getElementById('search_results');
            
            if (users.length === 0) {
                resultsContainer.innerHTML = '<div class="search-result-item">No se encontraron usuarios</div>';
            } else {
                resultsContainer.innerHTML = users.map(user => `
                    <div class="search-result-item" onclick="selectUser(${user.id}, '${user.nickname}', ${user.total_points})">
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
            
            resultsContainer.style.display = 'block';
        }

        function hideSearchResults() {
            document.getElementById('search_results').style.display = 'none';
        }

        function selectUser(userId, nickname, totalPoints) {
            selectedUser = { id: userId, nickname: nickname, total_points: totalPoints };
            
            // Update form
            document.getElementById('selected_user_id').value = userId;
            document.getElementById('user_search').value = nickname;
            
            // Show selected user info
            document.getElementById('selected_nickname').textContent = nickname;
            document.getElementById('selected_points').textContent = `Puntos actuales: ${totalPoints}`;
            document.getElementById('selected_user_info').style.display = 'block';
            
            hideSearchResults();
        }

        function loadTournaments() {
            fetch('api/points.php?action=tournaments')
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
            fetch('api/points.php?action=validate')
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

            // Store form data
            formData = new FormData(document.getElementById('assignPointsForm'));

            // Update confirmation details
            const tournamentText = tournamentId ? 
                ` (Torneo: ${document.getElementById('tournament_id').selectedOptions[0].textContent})` : '';
            
            document.getElementById('confirmation_details').innerHTML = `
                <div class="alert alert-info">
                    <strong>Usuario:</strong> ${selectedUser.nickname}<br>
                    <strong>Puntos a asignar:</strong> ${points}<br>
                    <strong>Fuente:</strong> ${source}${tournamentText}<br>
                    <strong>Puntos actuales:</strong> ${selectedUser.total_points}<br>
                    <strong>Puntos después:</strong> ${parseInt(selectedUser.total_points) + parseInt(points)}
                    ${notes ? `<br><strong>Notas:</strong> ${notes}` : ''}
                </div>
            `;

            document.getElementById('confirmationModal').style.display = 'block';
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
            formData = null;
        }

        function confirmAssignment() {
            if (!formData) return;

            closeConfirmationModal();
            
            // Show loading
            document.getElementById('loading').style.display = 'block';
            document.getElementById('submit_btn').disabled = true;

            fetch('api/points.php?action=assign', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('submit_btn').disabled = false;

                if (data.success) {
                    showAlert(`¡Puntos asignados exitosamente! ${selectedUser.nickname} ahora tiene ${data.user_total_points} puntos.`, 'success');
                    
                    // Reset form
                    document.getElementById('assignPointsForm').reset();
                    document.getElementById('selected_user_info').style.display = 'none';
                    document.getElementById('tournament_group').style.display = 'none';
                    selectedUser = null;
                    
                    // Regenerate CSRF token
                    document.querySelector('input[name="csrf_token"]').value = '<?php echo generateCSRFToken(); ?>';
                } else {
                    if (data.errors && Array.isArray(data.errors)) {
                        showAlert('Errores: ' + data.errors.join(', '), 'error');
                    } else {
                        showAlert('Error: ' + (data.error || 'Error desconocido'), 'error');
                    }
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('submit_btn').disabled = false;
                console.error('Error:', error);
                showAlert('Error de conexión al asignar puntos', 'error');
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
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>