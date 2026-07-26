<?php require_once __DIR__ . '/../config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f7fa; }
        .navbar-brand { font-weight: 700; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-card { text-align: center; padding: 1.5rem; }
        .stat-card .stat-number { font-size: 2rem; font-weight: 700; color: #0d6efd; }
        .stat-card .stat-label { color: #6c757d; font-size: 0.85rem; text-transform: uppercase; }
        .badge-status { font-size: 0.8rem; }
        .overdue { border-left: 4px solid #dc3545; }
        .ok { border-left: 4px solid #198754; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/donation-tracker-lite/">
                <i class="bi bi-box-seam"></i> <?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (is_logged_in()): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="/donation-tracker-lite/admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'items.php' ? 'active' : '' ?>" href="/donation-tracker-lite/admin/items.php">
                            <i class="bi bi-laptop"></i> Items
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'locations.php' ? 'active' : '' ?>" href="/donation-tracker-lite/admin/locations.php">
                            <i class="bi bi-geo-alt"></i> Locations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'inspections.php' ? 'active' : '' ?>" href="/donation-tracker-lite/admin/inspections.php">
                            <i class="bi bi-clipboard-check"></i> Inspections
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="bi bi-person-circle"></i> <?= e($_SESSION['full_name']) ?>
                            <span class="badge bg-secondary"><?= e($_SESSION['role']) ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/donation-tracker-lite/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container">
        <?php $flash = flash('success'); if ($flash): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= e($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php $flash = flash('error'); if ($flash): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= e($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
