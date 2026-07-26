<?php
session_start();
require_once __DIR__ . '/config/database.php';

function is_installed() {
    if (!file_exists(DB_PATH)) {
        return false;
    }
    try {
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        return in_array('users', $tables);
    } catch (PDOException $e) {
        return false;
    }
}

if (!is_installed()) {
    redirect('/donation-tracker-lite/install.php');
}

if (is_logged_in()) {
    redirect('/donation-tracker-lite/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f7fa; }
        .hero { padding: 5rem 0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand"><i class="bi bi-box-seam"></i> Donation Tracker</span>
        </div>
    </nav>

    <div class="container hero text-center">
        <h1 class="display-4 mb-4"><i class="bi bi-box-seam"></i> Donation Tracker</h1>
        <p class="lead text-muted mb-4">Track donated laptops, routers, and other equipment.<br>Verify their status and ensure they are still in use.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="/donation-tracker-lite/check.php" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-search"></i> Check My Donated Items
            </a>
            <a href="/donation-tracker-lite/login.php" class="btn btn-primary btn-lg">
                <i class="bi bi-person-lock"></i> Staff Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
