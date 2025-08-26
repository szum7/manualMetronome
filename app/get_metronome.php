<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$room_id = $data['room_id'] ?? null;
$user_id = $data['user_id'] ?? null;
// $room_id = 78567;
// $user_id = "tz4dfg56";

if (!isset($user_id) || !isset($room_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();
$ret = [
    "success" => false,
    "found1" => false,
    "found2" => false,
    "found3" => false,
    "bpm" => null,
    "ref_start_usec" => null,
    "offset" => null
];

$stmt = $pdo->prepare(
    "SELECT * 
    FROM metronomes 
    WHERE room_id = ?"
);
$stmt->execute([$room_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    
    $ret["found1"] = true;
    $ret["bpm"] = intval($rows[0]["bpm"]);
    $ret["absolute_start_timestamp_usec"] = intval($rows[0]["absolute_start_timestamp_usec"]);

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
    
    $ret["found2"] = true;
    $ret["ref_start_usec"] = intval($rows[0]["timestamp_usec"]);

}

$stmt = $pdo->prepare(
    "SELECT * 
    FROM users 
    WHERE room_id = ? 
    AND user_id = ?"
);
$stmt->execute([$room_id, $user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {    
    
    $ret["found3"] = true;
    $ret["offset"] = intval($rows[0]["offset_usec"]);

}

$ret["success"] = true;

echo json_encode($ret);