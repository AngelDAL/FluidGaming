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
    <title>Reportes y Estadísticas - Sistema de Puntos</title>
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
        }

        .page-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }

        .report-header h3 {
            margin-bottom: 0.5rem;
        }

        .report-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .report-content {
            padding: 1.5rem;
        }

        .filters-section {
            margin-bottom: 1.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group select,
        .form-group input {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .report-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
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

        .btn-success {
            background: #28a745;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .results-section {
            margin-top: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .results-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e1e5e9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-content {
            padding: 1.5rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        tr:hover {
            background: #f8f9fa;
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

        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #3c3;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .chart-container {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .reports-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .report-actions {
                flex-direction: column;
            }

            .stats-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Sistema de Puntos - Admin</h1>
            <div class="nav-links">
                <a href="../index.php?page=dashboard">Dashboard</a>
                <a href="../index.php?page=admin_events">Eventos</a>
                <a href="admin_stands.php">Stands</a>
                <a href="admin_reports.php" class="active">Reportes</a>
                <a href="#" onclick="logout()">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Reportes y Estadísticas</h2>
            <p>Analiza el rendimiento del sistema y genera reportes detallados</p>
        </div>

        <div class="reports-grid">
            <!-- Event Statistics Report -->
            <div class="report-card">
                <div class="report-header">
                    <h3>Estadísticas por Evento</h3>
                    <p>Resumen de actividad por evento incluyendo torneos y participación</p>
                </div>
                <div class="report-content">
                    <div class="filters-section">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="eventStats_event">Evento:</label>
                                <select id="eventStats_event">
                                    <option value="">Todos los eventos</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="eventStats_startDate">Fecha Inicio:</label>
                                <input type="date" id="eventStats_startDate">
                            </div>
                            <div class="form-group">
                                <label for="eventStats_endDate">Fecha Fin:</label>
                                <input type="date" id="eventStats_endDate">
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <button class="btn" onclick="generateReport('event_statistics')">Generar Reporte</button>
                        <button class="btn btn-success" onclick="exportReport('event_statistics')">Exportar CSV</button>
                    </div>
                </div>
            </div>

            <!-- Tournament Participation Report -->
            <div class="report-card">
                <div class="report-header">
                    <h3>Participación en Torneos</h3>
                    <p>Análisis detallado de participación y puntos por torneo</p>
                </div>
                <div class="report-content">
                    <div class="filters-section">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="tournamentStats_event">Evento:</label>
                                <select id="tournamentStats_event" onchange="loadTournaments('tournamentStats')">
                                    <option value="">Todos los eventos</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tournamentStats_tournament">Torneo:</label>
                                <select id="tournamentStats_tournament">
                                    <option value="">Todos los torneos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <button class="btn" onclick="generateReport('tournament_participation')">Generar Reporte</button>
                        <button class="btn btn-success" onclick="exportReport('tournament_participation')">Exportar CSV</button>
                    </div>
                </div>
            </div>

            <!-- Claims Trends Report -->
            <div class="report-card">
                <div class="report-header">
                    <h3>Tendencias de Reclamos</h3>
                    <p>Análisis de productos más populares y tendencias de reclamos</p>
                </div>
                <div class="report-content">
                    <div class="filters-section">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="claimsTrends_startDate">Fecha Inicio:</label>
                                <input type="date" id="claimsTrends_startDate">
                            </div>
                            <div class="form-group">
                                <label for="claimsTrends_endDate">Fecha Fin:</label>
                                <input type="date" id="claimsTrends_endDate">
                            </div>
                            <div class="form-group">
                                <label for="claimsTrends_stand">Stand:</label>
                                <select id="claimsTrends_stand">
                                    <option value="">Todos los stands</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <button class="btn" onclick="generateReport('claims_trends')">Generar Reporte</button>
                        <button class="btn btn-success" onclick="exportReport('claims_trends')">Exportar CSV</button>
                    </div>
                </div>
            </div>

            <!-- User Activity Report -->
            <div class="report-card">
                <div class="report-header">
                    <h3>Actividad de Usuarios</h3>
                    <p>Reporte de actividad individual de usuarios</p>
                </div>
                <div class="report-content">
                    <div class="filters-section">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="userActivity_startDate">Fecha Inicio:</label>
                                <input type="date" id="userActivity_startDate">
                            </div>
                            <div class="form-group">
                                <label for="userActivity_endDate">Fecha Fin:</label>
                                <input type="date" id="userActivity_endDate">
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <button class="btn" onclick="generateReport('user_activity')">Generar Reporte</button>
                        <button class="btn btn-success" onclick="exportReport('user_activity')">Exportar CSV</button>
                    </div>
                </div>
            </div>

            <!-- Points Distribution Report -->
            <div class="report-card">
                <div class="report-header">
                    <h3>Distribución de Puntos</h3>
                    <p>Análisis de cómo se distribuyen los puntos por fuente y tiempo</p>
                </div>
                <div class="report-content">
                    <div class="filters-section">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="pointsDistribution_event">Evento:</label>
                                <select id="pointsDistribution_event">
                                    <option value="">Todos los eventos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <button class="btn" onclick="generateReport('points_distribution')">Generar Reporte</button>
                        <button class="btn btn-success" onclick="exportReport('points_distribution')">Exportar CSV</button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Statistics -->
            <div class="report-card">
                <div class="report-header">
                    <h3>Estadísticas Generales</h3>
                    <p>Resumen general del sistema y métricas clave</p>
                </div>
                <div class="report-content">
                    <div class="filters-section">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="dashboardStats_event">Evento:</label>
                                <select id="dashboardStats_event">
                                    <option value="">Todo el sistema</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <button class="btn" onclick="generateReport('dashboard_stats')">Generar Reporte</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="results-section" style="display: none;">
            <div class="results-header">
                <h3 id="resultsTitle">Resultados del Reporte</h3>
                <button class="btn btn-secondary btn-small" onclick="hideResults()">Ocultar</button>
            </div>
            <div class="results-content">
                <div id="resultsContent">
                    <div class="loading">Generando reporte...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentReportType = '';
        let currentReportData = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadFilterData();
        });

        // Load filter data (events, stands, tournaments)
        async function loadFilterData() {
            try {
                // Load events
                const eventsResponse = await fetch('../api/reports.php?action=get_events');
                const eventsResult = await eventsResponse.json();
                
                if (eventsResult.success) {
                    const eventSelects = document.querySelectorAll('select[id$="_event"]');
                    eventSelects.forEach(select => {
                        eventsResult.data.forEach(event => {
                            const option = document.createElement('option');
                            option.value = event.id;
                            option.textContent = `${event.name} (${formatDate(event.start_date)})`;
                            select.appendChild(option);
                        });
                    });
                }

                // Load stands
                const standsResponse = await fetch('../api/reports.php?action=get_stands');
                const standsResult = await standsResponse.json();
                
                if (standsResult.success) {
                    const standSelect = document.getElementById('claimsTrends_stand');
                    standsResult.data.forEach(stand => {
                        const option = document.createElement('option');
                        option.value = stand.id;
                        option.textContent = stand.name;
                        standSelect.appendChild(option);
                    });
                }

            } catch (error) {
                console.error('Error loading filter data:', error);
            }
        }

        // Load tournaments for specific event
        async function loadTournaments(prefix) {
            const eventSelect = document.getElementById(`${prefix}_event`);
            const tournamentSelect = document.getElementById(`${prefix}_tournament`);
            
            // Clear existing options
            tournamentSelect.innerHTML = '<option value="">Todos los torneos</option>';
            
            if (!eventSelect.value) return;

            try {
                const response = await fetch(`../api/reports.php?action=get_tournaments&event_id=${eventSelect.value}`);
                const result = await response.json();
                
                if (result.success) {
                    result.data.forEach(tournament => {
                        const option = document.createElement('option');
                        option.value = tournament.id;
                        option.textContent = `${tournament.name} (${formatDateTime(tournament.scheduled_time)})`;
                        tournamentSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading tournaments:', error);
            }
        }

        // Generate report
        async function generateReport(reportType) {
            currentReportType = reportType;
            
            // Show results section
            document.getElementById('resultsSection').style.display = 'block';
            document.getElementById('resultsTitle').textContent = `Resultados: ${getReportTitle(reportType)}`;
            document.getElementById('resultsContent').innerHTML = '<div class="loading">Generando reporte...</div>';

            // Build filters
            const filters = getFiltersForReport(reportType);
            
            try {
                const url = `../api/reports.php?action=${reportType}${buildQueryString(filters)}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    currentReportData = result.data;
                    displayReportResults(reportType, result.data);
                } else {
                    showError('Error al generar reporte: ' + result.error);
                }
            } catch (error) {
                console.error('Error generating report:', error);
                showError('Error de conexión al generar reporte');
            }
        }

        // Get filters for specific report type
        function getFiltersForReport(reportType) {
            const filters = {};
            
            switch (reportType) {
                case 'event_statistics':
                    const eventId = document.getElementById('eventStats_event').value;
                    const startDate = document.getElementById('eventStats_startDate').value;
                    const endDate = document.getElementById('eventStats_endDate').value;
                    
                    if (eventId) filters.event_id = eventId;
                    if (startDate) filters.start_date = startDate;
                    if (endDate) filters.end_date = endDate;
                    break;

                case 'tournament_participation':
                    const tournamentEventId = document.getElementById('tournamentStats_event').value;
                    const tournamentId = document.getElementById('tournamentStats_tournament').value;
                    
                    if (tournamentEventId) filters.event_id = tournamentEventId;
                    if (tournamentId) filters.tournament_id = tournamentId;
                    break;

                case 'claims_trends':
                    const claimsStartDate = document.getElementById('claimsTrends_startDate').value;
                    const claimsEndDate = document.getElementById('claimsTrends_endDate').value;
                    const standId = document.getElementById('claimsTrends_stand').value;
                    
                    if (claimsStartDate) filters.start_date = claimsStartDate;
                    if (claimsEndDate) filters.end_date = claimsEndDate;
                    if (standId) filters.stand_id = standId;
                    break;

                case 'user_activity':
                    const userStartDate = document.getElementById('userActivity_startDate').value;
                    const userEndDate = document.getElementById('userActivity_endDate').value;
                    
                    if (userStartDate) filters.start_date = userStartDate;
                    if (userEndDate) filters.end_date = userEndDate;
                    break;

                case 'points_distribution':
                    const pointsEventId = document.getElementById('pointsDistribution_event').value;
                    
                    if (pointsEventId) filters.event_id = pointsEventId;
                    break;

                case 'dashboard_stats':
                    const dashboardEventId = document.getElementById('dashboardStats_event').value;
                    
                    if (dashboardEventId) filters.event_id = dashboardEventId;
                    break;
            }
            
            return filters;
        }

        // Build query string from filters
        function buildQueryString(filters) {
            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    params.append(key, filters[key]);
                }
            });
            return params.toString() ? '&' + params.toString() : '';
        }

        // Display report results
        function displayReportResults(reportType, data) {
            const container = document.getElementById('resultsContent');
            
            switch (reportType) {
                case 'event_statistics':
                    displayEventStatistics(container, data);
                    break;
                case 'tournament_participation':
                    displayTournamentParticipation(container, data);
                    break;
                case 'claims_trends':
                    displayClaimsTrends(container, data);
                    break;
                case 'user_activity':
                    displayUserActivity(container, data);
                    break;
                case 'points_distribution':
                    displayPointsDistribution(container, data);
                    break;
                case 'dashboard_stats':
                    displayDashboardStats(container, data);
                    break;
                default:
                    container.innerHTML = '<div class="error-message">Tipo de reporte no soportado</div>';
            }
        }

        // Display event statistics
        function displayEventStatistics(container, data) {
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="empty-state"><h3>No hay datos</h3><p>No se encontraron eventos para los filtros seleccionados.</p></div>';
                return;
            }

            let html = '<div class="table-responsive"><table>';
            html += '<thead><tr>';
            html += '<th>Evento</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Torneos</th>';
            html += '<th>Participantes</th><th>Puntos Distribuidos</th><th>Reclamos</th><th>Puntos Reclamados</th>';
            html += '</tr></thead><tbody>';

            data.forEach(event => {
                html += '<tr>';
                html += `<td><strong>${escapeHtml(event.name)}</strong></td>`;
                html += `<td>${formatDate(event.start_date)}</td>`;
                html += `<td>${formatDate(event.end_date)}</td>`;
                html += `<td>${event.total_tournaments}</td>`;
                html += `<td>${event.unique_participants}</td>`;
                html += `<td>${formatNumber(event.total_points_distributed)}</td>`;
                html += `<td>${event.total_claims}</td>`;
                html += `<td>${formatNumber(event.total_points_claimed)}</td>`;
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Display tournament participation
        function displayTournamentParticipation(container, data) {
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="empty-state"><h3>No hay datos</h3><p>No se encontraron torneos para los filtros seleccionados.</p></div>';
                return;
            }

            let html = '<div class="table-responsive"><table>';
            html += '<thead><tr>';
            html += '<th>Torneo</th><th>Evento</th><th>Fecha</th><th>Estado</th>';
            html += '<th>Participantes</th><th>Puntos Esperados</th><th>Puntos Distribuidos</th><th>Promedio</th>';
            html += '</tr></thead><tbody>';

            data.forEach(tournament => {
                html += '<tr>';
                html += `<td><strong>${escapeHtml(tournament.tournament_name)}</strong></td>`;
                html += `<td>${escapeHtml(tournament.event_name)}</td>`;
                html += `<td>${formatDateTime(tournament.scheduled_time)}</td>`;
                html += `<td><span class="status-badge status-${tournament.status}">${tournament.status}</span></td>`;
                html += `<td>${tournament.participants_count}</td>`;
                html += `<td>${formatNumber(tournament.expected_points)}</td>`;
                html += `<td>${formatNumber(tournament.actual_points_distributed)}</td>`;
                html += `<td>${formatNumber(tournament.avg_points_per_participant)}</td>`;
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Display claims trends
        function displayClaimsTrends(container, data) {
            if (!data) {
                container.innerHTML = '<div class="empty-state"><h3>No hay datos</h3><p>No se encontraron reclamos para los filtros seleccionados.</p></div>';
                return;
            }

            let html = '';

            // Daily trends
            if (data.daily_trends && data.daily_trends.length > 0) {
                html += '<h4>Tendencia Diaria de Reclamos</h4>';
                html += '<div class="table-responsive"><table>';
                html += '<thead><tr><th>Fecha</th><th>Total Reclamos</th><th>Completados</th><th>Pendientes</th><th>Puntos Reclamados</th></tr></thead><tbody>';
                
                data.daily_trends.forEach(day => {
                    html += '<tr>';
                    html += `<td>${formatDate(day.claim_date)}</td>`;
                    html += `<td>${day.total_claims}</td>`;
                    html += `<td>${day.completed_claims}</td>`;
                    html += `<td>${day.pending_claims}</td>`;
                    html += `<td>${formatNumber(day.points_claimed)}</td>`;
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }

            // Popular products
            if (data.popular_products && data.popular_products.length > 0) {
                html += '<h4>Productos Más Populares</h4>';
                html += '<div class="table-responsive"><table>';
                html += '<thead><tr><th>Producto</th><th>Stand</th><th>Puntos Requeridos</th><th>Total Reclamos</th><th>Completados</th><th>Tasa Completado</th></tr></thead><tbody>';
                
                data.popular_products.forEach(product => {
                    html += '<tr>';
                    html += `<td><strong>${escapeHtml(product.product_name)}</strong></td>`;
                    html += `<td>${escapeHtml(product.stand_name)}</td>`;
                    html += `<td>${formatNumber(product.points_required)}</td>`;
                    html += `<td>${product.total_claims}</td>`;
                    html += `<td>${product.completed_claims}</td>`;
                    html += `<td>${parseFloat(product.completion_rate).toFixed(1)}%</td>`;
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }

            container.innerHTML = html || '<div class="empty-state"><h3>No hay datos</h3><p>No se encontraron reclamos para mostrar.</p></div>';
        }

        // Display user activity
        function displayUserActivity(container, data) {
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="empty-state"><h3>No hay datos</h3><p>No se encontró actividad de usuarios para los filtros seleccionados.</p></div>';
                return;
            }

            let html = '<div class="table-responsive"><table>';
            html += '<thead><tr>';
            html += '<th>Usuario</th><th>Puntos Totales</th><th>Transacciones</th><th>Puntos Ganados</th>';
            html += '<th>Torneos</th><th>Reclamos</th><th>Puntos Gastados</th><th>Primera Actividad</th>';
            html += '</tr></thead><tbody>';

            data.forEach(user => {
                html += '<tr>';
                html += `<td><strong>${escapeHtml(user.nickname)}</strong></td>`;
                html += `<td>${formatNumber(user.total_points)}</td>`;
                html += `<td>${user.total_point_transactions}</td>`;
                html += `<td>${formatNumber(user.total_points_earned)}</td>`;
                html += `<td>${user.tournaments_participated}</td>`;
                html += `<td>${user.total_claims} (${user.completed_claims} completados)</td>`;
                html += `<td>${formatNumber(user.points_spent)}</td>`;
                html += `<td>${user.first_activity ? formatDateTime(user.first_activity) : 'N/A'}</td>`;
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Display points distribution
        function displayPointsDistribution(container, data) {
            if (!data) {
                container.innerHTML = '<div class="empty-state"><h3>No hay datos</h3><p>No se encontraron datos de distribución de puntos.</p></div>';
                return;
            }

            let html = '';

            // Points by source
            if (data.points_by_source && data.points_by_source.length > 0) {
                html += '<h4>Distribución por Fuente</h4>';
                html += '<div class="table-responsive"><table>';
                html += '<thead><tr><th>Fuente</th><th>Transacciones</th><th>Total Puntos</th><th>Promedio</th><th>Usuarios Únicos</th></tr></thead><tbody>';
                
                data.points_by_source.forEach(source => {
                    html += '<tr>';
                    html += `<td><strong>${escapeHtml(source.source)}</strong></td>`;
                    html += `<td>${source.transaction_count}</td>`;
                    html += `<td>${formatNumber(source.total_points)}</td>`;
                    html += `<td>${formatNumber(source.avg_points)}</td>`;
                    html += `<td>${source.unique_users}</td>`;
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }

            // Points by tournament
            if (data.points_by_tournament && data.points_by_tournament.length > 0) {
                html += '<h4>Distribución por Torneo</h4>';
                html += '<div class="table-responsive"><table>';
                html += '<thead><tr><th>Torneo</th><th>Puntos Esperados</th><th>Transacciones</th><th>Puntos Distribuidos</th><th>Participantes</th></tr></thead><tbody>';
                
                data.points_by_tournament.forEach(tournament => {
                    html += '<tr>';
                    html += `<td><strong>${escapeHtml(tournament.tournament_name)}</strong></td>`;
                    html += `<td>${formatNumber(tournament.expected_points)}</td>`;
                    html += `<td>${tournament.actual_transactions}</td>`;
                    html += `<td>${formatNumber(tournament.actual_points_distributed)}</td>`;
                    html += `<td>${tournament.participants}</td>`;
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }

            container.innerHTML = html || '<div class="empty-state"><h3>No hay datos</h3><p>No se encontraron datos de distribución.</p></div>';
        }

        // Display dashboard statistics
        function displayDashboardStats(container, data) {
            if (!data) {
                container.innerHTML = '<div class="empty-state"><h3>No hay datos</h3><p>No se pudieron cargar las estadísticas.</p></div>';
                return;
            }

            let html = '';

            // Overall statistics
            if (data.overall) {
                html += '<div class="stats-summary">';
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.overall.total_users)}</div><div class="stat-label">Usuarios</div></div>`;
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.overall.total_events)}</div><div class="stat-label">Eventos</div></div>`;
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.overall.total_tournaments)}</div><div class="stat-label">Torneos</div></div>`;
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.overall.total_stands)}</div><div class="stat-label">Stands</div></div>`;
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.overall.total_products)}</div><div class="stat-label">Productos</div></div>`;
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.overall.total_points_distributed)}</div><div class="stat-label">Puntos Distribuidos</div></div>`;
                html += '</div>';
            }

            // Recent activity
            if (data.recent_activity) {
                html += '<h4>Actividad Reciente (7 días)</h4>';
                html += '<div class="stats-summary">';
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.recent_activity.recent_point_transactions)}</div><div class="stat-label">Transacciones</div></div>`;
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.recent_activity.recent_claims)}</div><div class="stat-label">Reclamos</div></div>`;
                html += `<div class="stat-item"><div class="stat-number">${formatNumber(data.recent_activity.active_users_week)}</div><div class="stat-label">Usuarios Activos</div></div>`;
                html += '</div>';
            }

            // Top performers
            if (data.top_performers && data.top_performers.length > 0) {
                html += '<h4>Top 5 Usuarios</h4>';
                html += '<div class="table-responsive"><table>';
                html += '<thead><tr><th>Usuario</th><th>Puntos</th><th>Torneos</th><th>Productos Reclamados</th></tr></thead><tbody>';
                
                data.top_performers.forEach(user => {
                    html += '<tr>';
                    html += `<td><strong>${escapeHtml(user.nickname)}</strong></td>`;
                    html += `<td>${formatNumber(user.total_points)}</td>`;
                    html += `<td>${user.tournaments_participated}</td>`;
                    html += `<td>${user.products_claimed}</td>`;
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }

            container.innerHTML = html || '<div class="empty-state"><h3>No hay datos</h3><p>No se encontraron estadísticas para mostrar.</p></div>';
        }

        // Export report to CSV
        async function exportReport(reportType) {
            if (!currentReportData) {
                showError('Primero genera el reporte antes de exportar');
                return;
            }

            const filters = getFiltersForReport(reportType);
            const filename = `${reportType}_${new Date().toISOString().split('T')[0]}`;

            try {
                const response = await fetch('../api/reports.php?action=export_csv', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        report_type: reportType,
                        filename: filename,
                        filters: filters
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = '../' + result.download_url;
                    link.download = result.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showSuccess('Reporte exportado exitosamente');
                } else {
                    showError('Error al exportar reporte: ' + result.error);
                }
            } catch (error) {
                console.error('Error exporting report:', error);
                showError('Error de conexión al exportar reporte');
            }
        }

        // Hide results section
        function hideResults() {
            document.getElementById('resultsSection').style.display = 'none';
        }

        // Get report title
        function getReportTitle(reportType) {
            const titles = {
                'event_statistics': 'Estadísticas por Evento',
                'tournament_participation': 'Participación en Torneos',
                'claims_trends': 'Tendencias de Reclamos',
                'user_activity': 'Actividad de Usuarios',
                'points_distribution': 'Distribución de Puntos',
                'dashboard_stats': 'Estadísticas Generales'
            };
            return titles[reportType] || 'Reporte';
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES');
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('es-ES');
        }

        function formatNumber(number) {
            return new Intl.NumberFormat('es-ES').format(number || 0);
        }

        function showError(message) {
            const container = document.getElementById('resultsContent');
            container.innerHTML = `<div class="error-message">${message}</div>`;
        }

        function showSuccess(message) {
            // You can implement a toast notification system here
            alert(message);
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
    </script>
</body>
</html>