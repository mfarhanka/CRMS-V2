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

function rentalPlanOptions() {
    return [
        'daily' => ['label' => 'Daily', 'days' => 1],
        'weekly' => ['label' => 'Weekly', 'days' => 7],
        'monthly' => ['label' => 'Monthly', 'days' => 30],
    ];
}

function rentalPlanLabel($plan) {
    $plans = rentalPlanOptions();
    return $plans[$plan]['label'] ?? 'Daily';
}

function calculateBillingUnits($total_days, $billing_plan) {
    $plans = rentalPlanOptions();
    $days_per_unit = $plans[$billing_plan]['days'] ?? 1;
    return max(1, (int) ceil(max(1, (int) $total_days) / $days_per_unit));
}

function tableColumnExists($conn, $table, $column) {
    $schema = DB_NAME;
    $stmt = $conn->prepare("SELECT COUNT(*) AS count
                            FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_SCHEMA = ?
                              AND TABLE_NAME = ?
                              AND COLUMN_NAME = ?");
    $stmt->bind_param("sss", $schema, $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = intval($result->fetch_assoc()['count'] ?? 0) > 0;
    $stmt->close();
    return $exists;
}

function ensurePricingSchema($conn) {
    if (!tableColumnExists($conn, 'cars', 'weekly_rate')) {
        $conn->query("ALTER TABLE cars ADD COLUMN weekly_rate DECIMAL(10,2) NULL AFTER daily_rate");
    }

    if (!tableColumnExists($conn, 'cars', 'monthly_rate')) {
        $conn->query("ALTER TABLE cars ADD COLUMN monthly_rate DECIMAL(10,2) NULL AFTER weekly_rate");
    }

    if (!tableColumnExists($conn, 'rentals', 'billing_plan')) {
        $conn->query("ALTER TABLE rentals ADD COLUMN billing_plan ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily' AFTER total_days");
    }

    if (!tableColumnExists($conn, 'rentals', 'rate_amount')) {
        $conn->query("ALTER TABLE rentals ADD COLUMN rate_amount DECIMAL(10,2) NULL AFTER daily_rate");
        $conn->query("UPDATE rentals SET rate_amount = daily_rate WHERE rate_amount IS NULL");
    }

    if (!tableColumnExists($conn, 'rentals', 'billing_units')) {
        $conn->query("ALTER TABLE rentals ADD COLUMN billing_units INT NOT NULL DEFAULT 1 AFTER rate_amount");
        $conn->query("UPDATE rentals SET billing_units = total_days WHERE billing_units = 1 AND total_days > 1");
    }
}
?>
