<?php
require_once __DIR__ . '/../config/config.php';

// Destroy all session data
session_destroy();
session_unset();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

setMessage('success', 'Anda telah berhasil keluar.');
redirect(APP_URL . 'auth/login.php');
?>
