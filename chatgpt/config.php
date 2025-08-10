<?php
$production = false;

if ($production == true) {
    require_once 'secret.php';
} else {
    $db_host = '127.0.0.1';
    $db_name = 'clock_sync';
    $db_user = 'root';
    $db_pass = '';
}
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

// Create PDO helper
function pdo_connect() {
    global $dsn, $db_user, $db_pass;
    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    return new PDO($dsn, $db_user, $db_pass, $opt);
}

// Utility: current server microseconds since epoch
function now_usec() {
    return (int) round(microtime(true) * 1000000.0);
}

// Allow simple CORS for development (adjust in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);