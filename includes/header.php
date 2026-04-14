<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-brand">
                    <a href="<?php echo APP_URL; ?>">
                        <h1><?php echo APP_NAME; ?></h1>
                    </a>
                </div>
                <div class="nav-menu">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="<?php echo APP_URL; ?>admin/dashboard.php" class="nav-link">Dashboard</a>
                            <a href="<?php echo APP_URL; ?>admin/flights.php" class="nav-link">Penerbangan</a>
                            <a href="<?php echo APP_URL; ?>admin/bookings.php" class="nav-link">Pesanan</a>
                        <?php else: ?>
                            <a href="<?php echo APP_URL; ?>user/flights.php" class="nav-link">Jadwal Penerbangan</a>
                            <a href="<?php echo APP_URL; ?>user/bookings.php" class="nav-link">Pesanan Saya</a>
                            <a href="<?php echo APP_URL; ?>user/profile.php" class="nav-link">Profil</a>
                        <?php endif; ?>
                        <div class="nav-dropdown">
                            <button type="button" class="nav-dropdown-btn" aria-expanded="false" aria-haspopup="true">
                                <?php echo sanitizeInput($_SESSION['full_name'] ?? ''); ?>
                                <span class="nav-dropdown-chevron" aria-hidden="true">&#9662;</span>
                            </button>
                            <div class="nav-dropdown-content">
                                <a href="<?php echo APP_URL; ?>auth/logout.php">Keluar</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo APP_URL; ?>auth/login.php" class="nav-link">Masuk</a>
                        <a href="<?php echo APP_URL; ?>auth/register.php" class="btn btn-primary">Daftar</a>
                    <?php endif; ?>
                </div>
                <button class="nav-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <main class="main">
        <?php $message = getMessage(); ?>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>
