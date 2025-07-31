<?php
// Entry point for the tournament points system
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic routing
$page = $_GET['page'] ?? 'dashboard';

switch ($page) {
    case 'login':
        include 'views/login.php';
        break;
    case 'register':
        include 'views/register.php';
        break;
    case 'dashboard':
        include 'views/dashboard.php';
        break;
    case 'admin_events':
        include 'views/admin_events.php';
        break;
    case 'assign_points':
        include 'views/assign_points.php';
        break;
    default:
        include 'views/dashboard.php';
}
