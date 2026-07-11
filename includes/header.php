<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
start_secure_session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unbound</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <nav class="navbar">
        <a href="<?= app_url('index.php') ?>" class="logo">Unbound</a>
        <ul class="nav-links">
            <li><a href="<?= app_url('index.php') ?>">Shop</a></li>
            <li><a href="<?= app_url('about.php') ?>">About</a></li>
            <li><a href="<?= app_url('cart.php') ?>">Cart</a></li>
            <?php if (is_logged_in()): ?>
                <li><a href="<?= app_url('auth/logout.php') ?>">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= app_url('auth/login.php') ?>">Login</a></li>
                <li><a href="<?= app_url('auth/register.php') ?>">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main class="site-main">
