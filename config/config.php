<?php
// Include database class
require_once __DIR__ . '/database.php';

// Application configuration
define('APP_NAME', 'Dilan Maskapai');
define('APP_URL', 'http://localhost/dilanapi/');
define('UPLOAD_PATH', 'uploads/');
define('TICKET_PATH', 'tickets/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session
session_start();

// Helper functions
function generateBookingCode() {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

function generateTicketNumber() {
    return 'TKT' . date('Y') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Ubah nilai datetime-local (YYYY-MM-DDTHH:mm) ke format MySQL DATETIME.
 */
function normalizeDateTimeForDb($input) {
    if ($input === null || $input === '') {
        return '';
    }
    $s = trim(str_replace('T', ' ', $input));
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $s);
    if ($dt instanceof DateTime) {
        return $dt->format('Y-m-d H:i:s');
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i', $s);
    if ($dt instanceof DateTime) {
        return $dt->format('Y-m-d H:i:s');
    }
    $ts = strtotime($s);
    return $ts ? date('Y-m-d H:i:s', $ts) : $s;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($input) {
    if ($input === null) {
        return '';
    }
    return htmlspecialchars(trim((string) $input), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
