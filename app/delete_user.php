<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Test
$room_id = 333;
$user_id = "c_si68ku17";

$room_id = $data['room_id'] ?? null;
$user_id = $data['user_id'] ?? null;

if (!isset($user_id) || !isset($room_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();

$stmt = $pdo->prepare(
    "DELETE FROM users WHERE room_id = ? AND user_id = ?"
);
$stmt->execute([$room_id, $user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true]);