<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$sync_id = intval($data['sync_id']);

if (!isset($sync_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$sync_id = intval($sync_id);

$pdo = pdo_connect();
$stmt = $pdo->prepare(
    "SELECT tempo_bpm, start_time_usec 
    FROM metronome 
    WHERE sync_id = ?");
$stmt->execute([$sync_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode([
        "success" => true,
        "tempo_bpm" => $row["tempo_bpm"],
        "start_time_usec" => $row["start_time_usec"]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'No metronome set']);
}
