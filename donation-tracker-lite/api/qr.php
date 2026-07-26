<?php
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? null;
$size = (int)($_GET['size'] ?? 300);

if (!$token) {
    http_response_code(400);
    exit('Missing token');
}

$item = db()->prepare("SELECT id, qr_token FROM items WHERE qr_token = ?");
$item->execute([$token]);
$item = $item->fetch();

if (!$item) {
    http_response_code(404);
    exit('Item not found');
}

$url = APP_URL . '/inspect.php?token=' . urlencode($token);
$api_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($url) . '&format=svg';

header('Location: ' . $api_url);
exit;
