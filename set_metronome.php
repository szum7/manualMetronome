<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$tempo = $data['tempo'] ?? null;
$sync_id = $data['sync_id'] ?? null;

if (!isset($tempo) || !isset($sync_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$tempo = intval($tempo);
$sync_id = intval($sync_id);

// Reference start time: a few seconds in the future to give all clients time to join
$startTimeUsec = (int) round((microtime(true) + 5) * 1_000_000);

$pdo = pdo_connect();
$stmt = $pdo->prepare(
    "REPLACE INTO metronome (sync_id, tempo_bpm, start_time_usec) VALUES (?, ?, ?)");
$stmt->execute([$sync_id, $tempo, $startTimeUsec]);

echo json_encode([
    "success" => true,
    "tempo" => $tempo,
    "sync_id" => $sync_id,
    "start_time_usec" => $startTimeUsec
]);
