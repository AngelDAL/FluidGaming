<?php
/**
 * Stand Claims Management View - Interface for stand managers to process claims
 */

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../models/Claim.php';
require_once '../models/Stand.php';
require_once '../models/Product.php';
require_once '../models/User.php';

// Check authentication and stand manager role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('stand_manager'))) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$claim = new Claim($db);
$stand = new Stand($db);
$product = new Product($db);
$user = new User($db);

$current_user = getCurrentUser();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'process_claim':
                $claim_id = $_POST['claim_id'];
                $result = $claim->processClaim($claim_id, $current_user['id']);
                
                if ($result['success']) {
                    $message = 'Reclamo procesado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'create_claim':
                $user_id = $_POST['user_id'];
                $product_id = $_POST['product_id'];
                $stand_id = $_POST['stand_id'];
                
                $result = $claim->create($user_id, $product_id, $stand_id, $current_user['id']);
                
                if ($result['success']) {
                    $message = 'Reclamo creado y procesado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get user's stands
$userStands = [];
if ($current_user['role'] === 'admin') {
    // Admin can see all stands
    $query = "SELECT s.*, e.name as event_name FROM stands s 
              LEFT JOIN events e ON s.event_id = e.id 
              ORDER BY s.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $userStands = $stmt->fetchAll();
} else {
    // Stand managers can only see their own stands
    $query = "SELECT s.*, e.name as event_name FROM stands s 
              LEFT JOIN events e ON s.event_id = e.id 
              WHERE s.manager_id = :manager_id 
              ORDER BY s.name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':manager_id', $current_user['id']);
    $stmt->execute();
    $userStands = $stmt->fetchAll();
}

// Get selected stand
$selectedStandId = isset($_GET['stand_id']) ? $_GET['stand_id'] : (count($userStands) > 0 ? $userStands[0]['id'] : null);

// Get pending claims for selected stand
$pendingClaims = [];
$standProducts = [];
if ($selectedStandId) {
    $pendingClaims = $claim->getByStandId($selectedStandId, 'pending');
    
    // Get products for selected stand
    $query = "SELECT * FROM products WHERE stand_id = :stand_id AND is_active = 1 ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':stand_id', $selectedStandId);
    $stmt->execute();
    $standProducts = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reclamos - Sistema de Puntos</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .nav-tab.active {
            background: white;
            border-bottom: 3px solid #667eea;
            color: #667eea;
            font-weight: bold;
        }

        .nav-tab:hover {
            background: #e9ecef;
        }

        .tab-content {
            display: none;
            padding: 30px;
        }

        .tab-content.active {
            display: block;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stand-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .stand-selector h3 {
            color: #495057;
            margin-bottom: 15px;
        }

        .stand-selector select {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .form-section h3 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.3em;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .user-search {
            position: relative;
        }

        .user-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .user-suggestion {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-suggestion:hover {
            background: #f8f9fa;
        }

        .user-suggestion img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .points-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .points-info.insufficient {
            background: #ffebee;
            border-color: #ffcdd2;
        }

        .points-info.already-claimed {
            background: #fff3e0;
            border-color: #ffcc02;
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
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Gestión de Reclamos</h1>
            <p>Procesa reclamos de productos para usuarios</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($userStands)): ?>
            <div class="tab-content active">
                <div style="text-align: center; padding: 50px;">
                    <h3>No tienes stands asignados</h3>
                    <p>Contacta al administrador para que te asigne un stand.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="stand-selector">
                <h3>Seleccionar Stand</h3>
                <select onchange="changeStand(this.value)">
                    <?php foreach ($userStands as $standOption): ?>
                        <option value="<?php echo $standOption['id']; ?>" 
                                <?php echo $standOption['id'] == $selectedStandId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($standOption['name']); ?> 
                            - <?php echo htmlspecialchars($standOption['event_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="nav-tabs">
                <button class="nav-tab active" onclick="showTab('pending')">Reclamos Pendientes</button>
                <button class="nav-tab" onclick="showTab('verify')">Verificar Usuario</button>
            </div>

            <!-- Pending Claims Tab -->
            <div id="pending" class="tab-content active">
                <h3>Reclamos Pendientes</h3>
                
                <?php if (empty($pendingClaims)): ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 10px;">
                        <h4>No hay reclamos pendientes</h4>
                        <p>Todos los reclamos han sido procesados o no hay reclamos nuevos.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Producto</th>
                                    <th>Puntos Requeridos</th>
                                    <th>Puntos Usuario</th>
                                    <th>Fecha Reclamo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingClaims as $pendingClaim): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pendingClaim['user_nickname']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($pendingClaim['product_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo number_format($pendingClaim['points_required']); ?> pts
                                        </td>
                                        <td>
                                            <span style="color: <?php echo $pendingClaim['user_points'] >= $pendingClaim['points_required'] ? '#28a745' : '#dc3545'; ?>">
                                                <?php echo number_format($pendingClaim['user_points']); ?> pts
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($pendingClaim['timestamp'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($pendingClaim['user_points'] >= $pendingClaim['points_required']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="process_claim">
                                                    <input type="hidden" name="claim_id" value="<?php echo $pendingClaim['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-small" 
                                                            onclick="return confirm('¿Confirmar entrega del producto?')">
                                                        Procesar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="status-badge" style="background: #ffebee; color: #c62828;">
                                                    Puntos Insuficientes
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Verify User Tab -->
            <div id="verify" class="tab-content">
                <div class="form-section">
                    <h3>Verificar Puntos de Usuario</h3>
                    <p>Busca un usuario y producto para verificar si puede realizar el reclamo.</p>
                    
                    <div class="form-row">
                        <div class="form-group user-search">
                            <label for="user_search">Buscar Usuario:</label>
                            <input type="text" id="user_search" placeholder="Escribe el nickname del usuario..." 
                                   oninput="searchUsers(this.value)" autocomplete="off">
                            <div id="user_suggestions" class="user-suggestions"></div>
                        </div>
                        <div class="form-group">
                            <label for="product_select">Producto:</label>
                            <select id="product_select">
                                <option value="">Seleccionar producto</option>
                                <?php foreach ($standProducts as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" 
                                            data-points="<?php echo $product['points_required']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> 
                                        (<?php echo number_format($product['points_required']); ?> pts)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button onclick="verifyUserPoints()" class="btn btn-primary">Verificar Puntos</button>
                </div>

                <div id="verification_result" style="display: none;">
                    <!-- Results will be displayed here -->
                </div>

                <div id="create_claim_form" style="display: none;">
                    <div class="form-section">
                        <h3>Crear Reclamo Directo</h3>
                        <p>El usuario tiene puntos suficientes. Puedes procesar el reclamo inmediatamente.</p>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="create_claim">
                            <input type="hidden" name="user_id" id="selected_user_id">
                            <input type="hidden" name="product_id" id="selected_product_id">
                            <input type="hidden" name="stand_id" value="<?php echo $selectedStandId; ?>">
                            
                            <button type="submit" class="btn btn-success" 
                                    onclick="return confirm('¿Confirmar entrega del producto al usuario?')">
                                Procesar Reclamo Inmediatamente
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let selectedUserId = null;
        let searchTimeout = null;

        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        function changeStand(standId) {
            window.location.href = '?stand_id=' + standId;
        }

        function searchUsers(query) {
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                document.getElementById('user_suggestions').style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`../api/claims.php?action=search_users&search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayUserSuggestions(data.users);
                        }
                    })
                    .catch(error => {
                        console.error('Error searching users:', error);
                    });
            }, 300);
        }

        function displayUserSuggestions(users) {
            const suggestionsDiv = document.getElementById('user_suggestions');
            
            if (users.length === 0) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            let html = '';
            users.forEach(user => {
                html += `
                    <div class="user-suggestion" onclick="selectUser(${user.id}, '${user.nickname}', ${user.total_points})">
                        <div>
                            <strong>${user.nickname}</strong><br>
                            <small>${user.total_points} puntos</small>
                        </div>
                    </div>
                `;
            });

            suggestionsDiv.innerHTML = html;
            suggestionsDiv.style.display = 'block';
        }

        function selectUser(userId, nickname, points) {
            selectedUserId = userId;
            document.getElementById('user_search').value = nickname + ' (' + points + ' pts)';
            document.getElementById('user_suggestions').style.display = 'none';
            document.getElementById('selected_user_id').value = userId;
        }

        function verifyUserPoints() {
            if (!selectedUserId) {
                alert('Por favor selecciona un usuario');
                return;
            }

            const productSelect = document.getElementById('product_select');
            const productId = productSelect.value;
            
            if (!productId) {
                alert('Por favor selecciona un producto');
                return;
            }

            const productName = productSelect.options[productSelect.selectedIndex].text;
            const requiredPoints = productSelect.options[productSelect.selectedIndex].dataset.points;

            fetch(`../api/claims.php?action=verify_points&user_id=${selectedUserId}&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    displayVerificationResult(data, productName, requiredPoints, productId);
                })
                .catch(error => {
                    console.error('Error verifying points:', error);
                    alert('Error al verificar puntos');
                });
        }

        function displayVerificationResult(data, productName, requiredPoints, productId) {
            const resultDiv = document.getElementById('verification_result');
            const createFormDiv = document.getElementById('create_claim_form');
            
            let html = '<div class="form-section"><h3>Resultado de Verificación</h3>';
            
            if (data.success) {
                const pointsInfo = data.points_info;
                const canClaim = pointsInfo.has_sufficient_points;
                
                html += `
                    <div class="points-info ${canClaim ? '' : 'insufficient'}">
                        <h4>Producto: ${productName}</h4>
                        <p><strong>Puntos requeridos:</strong> ${parseInt(requiredPoints).toLocaleString()}</p>
                        <p><strong>Puntos del usuario:</strong> ${pointsInfo.user_points.toLocaleString()}</p>
                        <p><strong>Estado:</strong> 
                            <span style="color: ${canClaim ? '#28a745' : '#dc3545'}">
                                ${canClaim ? '✅ Puede reclamar' : '❌ Puntos insuficientes'}
                            </span>
                        </p>
                    </div>
                `;
                
                if (canClaim) {
                    document.getElementById('selected_product_id').value = productId;
                    createFormDiv.style.display = 'block';
                } else {
                    createFormDiv.style.display = 'none';
                }
            } else {
                if (data.already_claimed) {
                    html += `
                        <div class="points-info already-claimed">
                            <h4>⚠️ Producto ya reclamado</h4>
                            <p>Este usuario ya ha reclamado este producto anteriormente.</p>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="points-info insufficient">
                            <h4>❌ Error</h4>
                            <p>${data.error}</p>
                        </div>
                    `;
                }
                createFormDiv.style.display = 'none';
            }
            
            html += '</div>';
            resultDiv.innerHTML = html;
            resultDiv.style.display = 'block';
        }

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.user-search')) {
                document.getElementById('user_suggestions').style.display = 'none';
            }
        });
    </script>
</body>
</html>