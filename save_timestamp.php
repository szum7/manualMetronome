<?php
require 'config.php';
// API: POST { synchid: int, clientId: string, ts_usec: integer }
// Stores the client's timestamp (microseconds since epoch)

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid json']);
    exit;
}

$synchid = intval($input['synchid'] ?? 0);
$clientId = trim($input['clientId'] ?? '');
$ts_usec = isset($input['ts_usec']) ? (int)$input['ts_usec'] : null;
if ($synchid <= 0 || $clientId === '' || !$ts_usec) {
    http_response_code(400);
    echo json_encode(['error' => 'missing params']);
    exit;
}

try {
    $pdo = pdo_connect();
    $stmt = $pdo->prepare('INSERT INTO sync_timestamps (synchid, client_id, ts_usec) VALUES (?, ?, ?)');
    $stmt->execute([$synchid, $clientId, $ts_usec]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}