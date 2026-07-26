<?php
function load_env($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"' \t\n\r\0\x0B");
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

load_env(__DIR__ . '/../.env');

if (!defined('DB_PATH')) define('DB_PATH', __DIR__ . '/../data/donation_tracker.db');
if (!defined('APP_NAME')) define('APP_NAME', 'Donation Tracker');
if (!defined('APP_URL')) define('APP_URL', 'http://localhost/donation-tracker-lite');
if (!defined('INSPECTION_INTERVAL_MONTHS')) define('INSPECTION_INTERVAL_MONTHS', 3);
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads/');

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'sqlite:' . DB_PATH;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/donation-tracker-lite/login.php');
    }
}

function require_admin() {
    require_login();
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function flash($key, $message = '') {
    if (!empty($message)) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = isset($_SESSION['flash'][$key]) ? $_SESSION['flash'][$key] : null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    return isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
