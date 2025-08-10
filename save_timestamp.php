<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$synch_id = $data['synch_id'] ?? null;
$client_id = $data['client_id'] ?? null;
$ts_usec = $data['ts_usec'] ?? null;

if (!isset($client_id) || !isset($ts_usec) || !isset($synch_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();


// Reset flow: Remove previous values
$stmt = $pdo->prepare(
    "DELETE FROM sync_offsets 
    WHERE synch_id = ?");
$stmt->execute([$synch_id]);

// Update ts_usec
$stmt = $pdo->prepare(
    "SELECT id
    FROM sync_timestamps 
    WHERE synch_id = ? 
    AND client_id = ?"
);
$stmt->execute([$synch_id, $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($rows) > 0) {
    $stmt = $pdo->prepare("REPLACE INTO sync_timestamps (id, synch_id, client_id, ts_usec) VALUES (?, ?, ?, ?)");
    $stmt->execute([$rows[0]['id'], $synch_id, $client_id, $ts_usec]);
    echo json_encode([
        "success" => true,
        "message" => "UPDATED {$synch_id} - {$client_id} - {$ts_usec}"
    ]);
} else {
    $stmt = $pdo->prepare("INSERT INTO sync_timestamps (synch_id, client_id, ts_usec) VALUES (?, ?, ?)");
    $stmt->execute([$synch_id, $client_id, $ts_usec]);
    echo json_encode([
        "success" => true,
        "message" => "INSERTED {$synch_id} - {$client_id} - {$ts_usec}"
    ]);
}
