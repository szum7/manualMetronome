<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$room_id = $data['room_id'] ?? null;
$user_id = $data['user_id'] ?? null;
$offset_usec = $data['offset_usec'] ?? null;
// $room_id = 78567;
// $user_id = "c_si68ku17";
// $offset_usec = 34535;

if (!isset($user_id) || !isset($room_id) || !isset($offset_usec)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare(
    "DELETE FROM users WHERE room_id = ? AND user_id = ?"
);
$stmt->execute([$room_id, $user_id]);

$stmt = $pdo->prepare(
    "INSERT INTO users (room_id, user_id, is_ref, offset_usec) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$room_id, $user_id, false, $offset_usec]);

$ret = [
    "success" => true
];

echo json_encode($ret);