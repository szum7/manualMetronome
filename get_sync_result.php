<?php
require 'config.php';
// Clients poll this endpoint to get their computed offset and blink start
// GET params: synchid, clientId

$synchid = intval($_GET['synchid'] ?? 0);
$clientId = trim($_GET['clientId'] ?? '');
if ($synchid <= 0 || $clientId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing params']);
    exit;
}

try {
    $pdo = pdo_connect();
    // find offset
    $stmt = $pdo->prepare('SELECT offset_usec, ref_client_id FROM sync_offsets WHERE synchid = ? AND client_id = ? LIMIT 1');
    $stmt->execute([$synchid, $clientId]);
    $off = $stmt->fetch();

    // find blink start
    $stmt2 = $pdo->prepare('SELECT blink_start_usec FROM sync_session WHERE synchid = ? LIMIT 1');
    $stmt2->execute([$synchid]);
    $s = $stmt2->fetch();

    if (!$off) {
        echo json_encode(['ready' => false]);
        exit;
    }

    $offset_usec = (int)$off['offset_usec'];
    $ref_client_id = $off['ref_client_id'];
    $blink_start = $s ? (int)$s['blink_start_usec'] : null;

    echo json_encode(['ready' => true, 'offset_usec' => $offset_usec, 'ref_client_id' => $ref_client_id, 'blink_start_usec' => $blink_start]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}