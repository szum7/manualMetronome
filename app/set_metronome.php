<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$room_id = $data['room_id'] ?? null;
$user_id = $data['user_id'] ?? null;
$bpm = $data['bpm'] ?? null;
$absolute_start_timestamp_usec = $data['absolute_start_timestamp_usec'] ?? null;
// $room_id = 78567;
// $user_id = "c_si68ku17";
// $bpm = 120;

if (!isset($user_id) || !isset($room_id) || !isset($bpm) || !isset($absolute_start_timestamp_usec)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare(
    "DELETE FROM metronomes WHERE room_id = ?"
);
$stmt->execute([$room_id]);

$stmt = $pdo->prepare(
    "INSERT INTO metronomes (room_id, user_id, bpm, absolute_start_timestamp_usec) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$room_id, $user_id, $bpm, $absolute_start_timestamp_usec]);

$ret = [
    "success" => true
];

echo json_encode($ret);