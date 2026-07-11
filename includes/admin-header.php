<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth-check.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unbound Admin</title>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body class="admin-body">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2 class="admin-logo">Unbound Admin</h2>
        <ul class="admin-nav">
            <li><a href="<?= app_url('admin/dashboard.php') ?>">Dashboard</a></li>
            <li><a href="<?= app_url('admin/inventory.php') ?>">Inventory</a></li>
            <li><a href="<?= app_url('admin/manage-users.php') ?>">Manage Users</a></li>
            <li><a href="<?= app_url('admin/audit-log.php') ?>">Audit Log</a></li>
            <li><a href="<?= app_url('auth/logout.php') ?>">Logout</a></li>
        </ul>
    </aside>
    <main class="admin-main">
