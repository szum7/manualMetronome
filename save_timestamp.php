<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$sync_id = $data['sync_id'] ?? null;
$client_id = $data['client_id'] ?? null;
$same_time_ts_usec = $data['same_time_ts_usec'] ?? null;

if (!isset($client_id) || !isset($same_time_ts_usec) || !isset($sync_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();


// Reset flow: Remove previous values
$stmt = $pdo->prepare(
    "DELETE FROM sync_offsets 
    WHERE sync_id = ?");
$stmt->execute([$sync_id]);

// Update same_time_ts_usec
$stmt = $pdo->prepare(
    "SELECT id
    FROM sync_timestamps 
    WHERE sync_id = ? 
    AND client_id = ?"
);
$stmt->execute([$sync_id, $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($rows) > 0) {
    $stmt = $pdo->prepare("REPLACE INTO sync_timestamps (id, sync_id, client_id, same_time_ts_usec) VALUES (?, ?, ?, ?)");
    $stmt->execute([$rows[0]['id'], $sync_id, $client_id, $same_time_ts_usec]);
    echo json_encode([
        "success" => true,
        "message" => "UPDATED {$sync_id} - {$client_id} - {$same_time_ts_usec}"
    ]);
} else {
    $stmt = $pdo->prepare("INSERT INTO sync_timestamps (sync_id, client_id, same_time_ts_usec) VALUES (?, ?, ?)");
    $stmt->execute([$sync_id, $client_id, $same_time_ts_usec]);
    echo json_encode([
        "success" => true,
        "message" => "INSERTED {$sync_id} - {$client_id} - {$same_time_ts_usec}"
    ]);
}
