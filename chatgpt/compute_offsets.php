<?php
require 'config.php';
// Admin/automation endpoint to compute offsets for a given synchid.
// POST { synchid: int, ref_client_id?: string }

$input = json_decode(file_get_contents('php://input'), true);
$synchid = intval($input['synchid'] ?? 0);
$ref_client_id = isset($input['ref_client_id']) ? trim($input['ref_client_id']) : null;
if ($synchid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'missing synchid']);
    exit;
}

try {
    $pdo = pdo_connect();
    // fetch all timestamps for this synchid
    $stmt = $pdo->prepare('SELECT client_id, ts_usec FROM sync_timestamps WHERE synchid = ?');
    $stmt->execute([$synchid]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        echo json_encode(['error' => 'no timestamps found']);
        exit;
    }

    // choose reference
    if ($ref_client_id) {
        // ensure exists
        $found = false;
        foreach ($rows as $r) if ($r['client_id'] === $ref_client_id) $found = true;
        if (!$found) {
            http_response_code(400);
            echo json_encode(['error' => 'ref_client_id not among submissions']);
            exit;
        }
    } else {
        // default: choose the client with the earliest ts_usec (first press) as reference
        usort($rows, function($a,$b){return $a['ts_usec'] <=> $b['ts_usec'];});
        $ref_client_id = $rows[0]['client_id'];
    }

    // get ref ts
    $ref_ts = null;
    foreach ($rows as $r) if ($r['client_id'] === $ref_client_id) { $ref_ts = (int)$r['ts_usec']; break; }
    if ($ref_ts === null) throw new Exception('ref timestamp not found');

    // compute offsets and store
    $pdo->beginTransaction();
    $stmtIns = $pdo->prepare('INSERT INTO sync_offsets (synchid, client_id, offset_usec, ref_client_id) VALUES (?, ?, ?, ?)');
    foreach ($rows as $r) {
        $client = $r['client_id'];
        $ts = (int)$r['ts_usec'];
        $offset = $ts - $ref_ts; // signed: positive means client clock ahead
        $stmtIns->execute([$synchid, $client, $offset, $ref_client_id]);
    }

    // compute blink start time: choose ~5 seconds into future from server now
    $blink_start = now_usec() + 5000000; // 5,000,000 usec = 5 sec

    // save session entry
    $stmtS = $pdo->prepare('INSERT INTO sync_session (synchid, ref_client_id, blink_start_usec) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE blink_start_usec = VALUES(blink_start_usec), ref_client_id = VALUES(ref_client_id)');
    $stmtS->execute([$synchid, $ref_client_id, $blink_start]);

    $pdo->commit();

    echo json_encode(['ok' => true, 'ref_client_id' => $ref_client_id, 'blink_start_usec' => $blink_start]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}