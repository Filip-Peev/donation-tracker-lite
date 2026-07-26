<?php
session_start();
require_once __DIR__ . '/config/database.php';
session_destroy();
header('Location: /donation-tracker-lite/login.php');
exit;
