<?php
/**
 * Admin Stands Management View
 */

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../controllers/StandController.php';

// Check authentication and admin role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('stand_manager'))) {
    header('Location: login.php');
    exit();
}

// Initialize controller
$database = new Database();
$db = $database->getConnection();
$standController = new StandController($db);

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_stand':
                $result = $standController->createStand();
                if ($result['success']) {
                    $message = 'Stand creado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'update_stand':
                $result = $standController->updateStand($_POST['stand_id']);
                if ($result['success']) {
                    $message = 'Stand actualizado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'delete_stand':
                $result = $standController->deleteStand($_POST['stand_id']);
                if ($result['success']) {
                    $message = 'Stand eliminado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'create_product':
                $result = $standController->createProduct();
                if ($result['success']) {
                    $message = 'Producto creado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'update_product':
                $result = $standController->updateProduct($_POST['product_id']);
                if ($result['success']) {
                    $message = 'Producto actualizado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'delete_product':
                $result = $standController->deleteProduct($_POST['product_id']);
                if ($result['success']) {
                    $message = 'Producto eliminado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
                
            case 'toggle_product':
                $result = $standController->toggleProductActive($_POST['product_id']);
                if ($result['success']) {
                    $message = 'Estado del producto actualizado';
                    $messageType = 'success';
                } else {
                    $message = implode('<br>', $result['errors']);
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get data for display
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$event_filter = isset($_GET['event_id']) ? $_GET['event_id'] : '';

$standsData = $standController->getStands($page, 10, $event_filter, $search);
$productsData = $standController->getProducts($page, 10, null, $search);
$availableManagers = $standController->getAvailableManagers();
$activeEvents = $standController->getActiveEvents();
$userStands = $standController->getUserStands();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Stands - Sistema de Puntos</title>
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
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

        .search-filters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-filters input,
        .search-filters select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 10px 15px;
            text-decoration: none;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            color: #495057;
            transition: all 0.3s ease;
        }

        .pagination a:hover,
        .pagination a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
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

        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .search-filters {
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
            <h1>🏪 Gestión de Stands</h1>
            <p>Administra stands y productos del sistema</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('stands')">Stands</button>
            <button class="nav-tab" onclick="showTab('products')">Productos</button>
        </div>

        <!-- Stands Tab -->
        <div id="stands" class="tab-content active">
            <?php if (hasRole('admin')): ?>
            <div class="form-section">
                <h3>Crear Nuevo Stand</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_stand">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stand_name">Nombre del Stand:</label>
                            <input type="text" id="stand_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="manager_id">Encargado:</label>
                            <select id="manager_id" name="manager_id" required>
                                <option value="">Seleccionar encargado</option>
                                <?php foreach ($availableManagers as $manager): ?>
                                    <option value="<?php echo $manager['id']; ?>">
                                        <?php echo htmlspecialchars($manager['nickname']); ?> (<?php echo $manager['role']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="event_id">Evento:</label>
                            <select id="event_id" name="event_id" required>
                                <option value="">Seleccionar evento</option>
                                <?php foreach ($activeEvents as $event): ?>
                                    <option value="<?php echo $event['id']; ?>">
                                        <?php echo htmlspecialchars($event['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Stand</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="search-filters">
                <input type="text" id="stand_search" placeholder="Buscar stands..." value="<?php echo htmlspecialchars($search); ?>">
                <select id="event_filter">
                    <option value="">Todos los eventos</option>
                    <?php foreach ($activeEvents as $event): ?>
                        <option value="<?php echo $event['id']; ?>" <?php echo $event_filter == $event['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button onclick="filterStands()" class="btn btn-primary btn-small">Filtrar</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Encargado</th>
                            <th>Evento</th>
                            <th>Productos</th>
                            <th>Creado</th>
                            <?php if (hasRole('admin')): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($standsData['stands'])): ?>
                            <tr>
                                <td colspan="<?php echo hasRole('admin') ? '7' : '6'; ?>" style="text-align: center; padding: 40px;">
                                    No hay stands registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($standsData['stands'] as $stand): ?>
                                <tr>
                                    <td><?php echo $stand['id']; ?></td>
                                    <td><?php echo htmlspecialchars($stand['name']); ?></td>
                                    <td><?php echo htmlspecialchars($stand['manager_name'] ?? 'Sin asignar'); ?></td>
                                    <td><?php echo htmlspecialchars($stand['event_name'] ?? 'Sin evento'); ?></td>
                                    <td>
                                        <button onclick="viewStandProducts(<?php echo $stand['id']; ?>)" class="btn btn-primary btn-small">
                                            Ver Productos
                                        </button>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($stand['created_at'])); ?></td>
                                    <?php if (hasRole('admin')): ?>
                                    <td>
                                        <button onclick="editStand(<?php echo $stand['id']; ?>)" class="btn btn-warning btn-small">Editar</button>
                                        <button onclick="deleteStand(<?php echo $stand['id']; ?>)" class="btn btn-danger btn-small">Eliminar</button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($standsData['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $standsData['total_pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&event_id=<?php echo $event_filter; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Products Tab -->
        <div id="products" class="tab-content">
            <div class="form-section">
                <h3>Crear Nuevo Producto</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_product">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_name">Nombre del Producto:</label>
                            <input type="text" id="product_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="points_required">Puntos Requeridos:</label>
                            <input type="number" id="points_required" name="points_required" min="1" max="100000" required>
                        </div>
                        <div class="form-group">
                            <label for="product_stand_id">Stand:</label>
                            <select id="product_stand_id" name="stand_id" required>
                                <option value="">Seleccionar stand</option>
                                <?php foreach ($userStands as $stand): ?>
                                    <option value="<?php echo $stand['id']; ?>">
                                        <?php echo htmlspecialchars($stand['name']); ?> - <?php echo htmlspecialchars($stand['event_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_description">Descripción:</label>
                            <textarea id="product_description" name="description" placeholder="Descripción opcional del producto"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="product_image">Imagen del Producto:</label>
                            <input type="file" id="product_image" name="product_image" accept="image/*">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Producto</button>
                </form>
            </div>

            <div class="search-filters">
                <input type="text" id="product_search" placeholder="Buscar productos..." value="<?php echo htmlspecialchars($search); ?>">
                <button onclick="filterProducts()" class="btn btn-primary btn-small">Filtrar</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Puntos</th>
                            <th>Stand</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productsData['products'])): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    No hay productos registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productsData['products'] as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <?php if ($product['image_url']): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 alt="Producto" class="product-image">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                📦
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo number_format($product['points_required']); ?> pts</td>
                                    <td><?php echo htmlspecialchars($product['stand_name'] ?? 'Sin stand'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $product['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="editProduct(<?php echo $product['id']; ?>)" class="btn btn-warning btn-small">Editar</button>
                                        <button onclick="toggleProduct(<?php echo $product['id']; ?>)" class="btn btn-primary btn-small">
                                            <?php echo $product['is_active'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="btn btn-danger btn-small">Eliminar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($productsData['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $productsData['total_pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals will be added here via JavaScript -->

    <script>
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

        function filterStands() {
            const search = document.getElementById('stand_search').value;
            const eventId = document.getElementById('event_filter').value;
            
            let url = '?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (eventId) url += 'event_id=' + eventId + '&';
            
            window.location.href = url.slice(0, -1); // Remove trailing &
        }

        function filterProducts() {
            const search = document.getElementById('product_search').value;
            
            let url = '?';
            if (search) url += 'search=' + encodeURIComponent(search);
            
            window.location.href = url;
        }

        function editStand(id) {
            // Implementation for editing stands
            alert('Función de edición en desarrollo');
        }

        function deleteStand(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este stand?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_stand">
                    <input type="hidden" name="stand_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editProduct(id) {
            // Implementation for editing products
            alert('Función de edición en desarrollo');
        }

        function deleteProduct(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleProduct(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_product">
                <input type="hidden" name="product_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function viewStandProducts(standId) {
            // Switch to products tab and filter by stand
            showTab('products');
            // You could implement additional filtering here
        }

        // Handle Enter key in search inputs
        document.getElementById('stand_search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterStands();
            }
        });

        document.getElementById('product_search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterProducts();
            }
        });
    </script>
</body>
</html>