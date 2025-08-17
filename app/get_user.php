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
$ret = [];

$stmt = $pdo->prepare(
    "SELECT *
    FROM users 
    WHERE room_id = ?
    AND user_id = ?"
);
$stmt->execute([$room_id, $user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {

    $ret = [
        "success" => true,
        "found" => true,
        "room_id" => intval($room_id),
        "user_id" => $user_id,
        "timestamp_usec" => intval($rows[0]["timestamp_usec"]),
        "is_ref" => (bool)$rows[0]["is_ref"],
        "offset_usec" => intval($rows[0]["offset_usec"]),
        "server" => [
            "user_id" => null,
            "timestamp_usec" => null
        ]
    ];

} else { // New user

    $ret = [
        "success" => true,
        "found" => false,
        "room_id" => intval($room_id),
        "user_id" => $user_id,
        "server" => [
            "user_id" => null,
            "timestamp_usec" => null
        ]
    ];
}

$stmt = $pdo->prepare(
    "SELECT *
    FROM users 
    WHERE room_id = ?
    AND is_ref = true"
);
$stmt->execute([$room_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {

    $ret["server"]["user_id"] = $rows[0]["user_id"];
    $ret["server"]["timestamp_usec"] = intval($rows[0]["timestamp_usec"]);

}

echo json_encode($ret);