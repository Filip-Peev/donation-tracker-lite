<?php
session_start();
require_once __DIR__ . '/config/database.php';

function is_installed() {
    $env = __DIR__ . '/.env';
    if (!file_exists($env)) {
        return false;
    }
    $values = [];
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $values[trim($k)] = trim(trim($v), "\"' \t\n\r\0\x0B");
    }
    $dbPath = $values['DB_PATH'] ?? __DIR__ . '/data/donation_tracker.db';
    if (!file_exists($dbPath)) {
        return false;
    }
    try {
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        return in_array('users', $tables);
    } catch (PDOException $e) {
        return false;
    }
}

if (is_installed()) {
    header('Location: /donation-tracker-lite/');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbPath = trim($_POST['db_path'] ?? __DIR__ . '/data/donation_tracker.db');
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminName = trim($_POST['admin_name'] ?? 'Administrator');

    try {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $sql = file_get_contents(__DIR__ . '/donation_tracker.sql');
        $pdo->exec($sql);

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$adminUser]);
        if ($stmt->fetch()) {
            $update = $pdo->prepare("UPDATE users SET password_hash = ?, full_name = ? WHERE username = ?");
            $update->execute([$hash, $adminName, $adminUser]);
        } else {
            $insert = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, 'admin')");
            $insert->execute([$adminUser, $hash, $adminName]);
        }

        $env = "DB_PATH=$dbPath\n" .
               "\n" .
               "APP_NAME=Donation Tracker\n" .
               "APP_URL=http://localhost/donation-tracker-lite\n" .
               "INSPECTION_INTERVAL_MONTHS=3\n";

        file_put_contents(__DIR__ . '/.env', $env);

        $message = "Installation complete! You can now log in with the admin account you created. <a href='login.php'>Go to Login</a>";
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Donation Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width: 600px;">
        <h1 class="mb-4"><i class="bi bi-box-seam"></i> Donation Tracker - Installer</h1>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (empty($message)): ?>
        <form method="POST">
            <h4 class="mb-3">Database Configuration</h4>
            <div class="mb-3">
                <label class="form-label">SQLite Database Path</label>
                <input type="text" name="db_path" class="form-control" value="<?= e($dbPath ?? __DIR__ . '/data/donation_tracker.db') ?>">
                <small class="text-muted">Full path to the .db file. The <code>data/</code> directory will be created if needed.</small>
            </div>

            <h4 class="mb-3 mt-4">Admin Account</h4>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="admin_user" class="form-control" value="admin">
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="admin_name" class="form-control" value="Administrator">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="admin_pass" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">Install</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
