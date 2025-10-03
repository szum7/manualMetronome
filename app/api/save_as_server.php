<?php
require '../config.php';

$data = json_decode(file_get_contents("php://input"), true);

$room_id = $data['room_id'] ?? null;
$user_id = $data['user_id'] ?? null;
$timestamp_usec = $data['timestamp_usec'] ?? null;
// $room_id = 78567;
// $user_id = "c_si68ku17";
// $timestamp_usec = 34535;

if (!isset($user_id) || !isset($room_id) || !isset($timestamp_usec)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare(
    "DELETE FROM users WHERE room_id = ?"
);
$stmt->execute([$room_id]);

$stmt = $pdo->prepare(
    "INSERT INTO users (room_id, user_id, is_ref, timestamp_usec, offset_usec) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$room_id, $user_id, true, $timestamp_usec, 0]);

$ret = [
    "success" => true
];

echo json_encode($ret);