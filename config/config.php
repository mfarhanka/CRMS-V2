<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'Car Rental Management System');

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
);

if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
    if ($forwardedProto === 'http' || $forwardedProto === 'https') {
        $isHttps = $forwardedProto === 'https';
    }
}

$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

define('SITE_URL', $scheme . '://' . $host . $basePath . '/');

// Include database connection
require_once __DIR__ . '/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
}

// Check if user is superadmin
function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . 'dashboard.php');
        exit();
    }
}

// Redirect if not superadmin
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: ' . SITE_URL . 'dashboard.php');
        exit();
    }
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format currency
function formatCurrency($amount) {
    return 'RM ' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>
